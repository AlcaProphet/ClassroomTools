<?php
/**
 * 班级详情页
 * 
 * 功能：
 *   - 显示班级信息（名称/班级号/学生人数）
 *   - CSV 导入学生名单（姓名/学号/性别/分数）
 *   - 学生列表展示
 *   - 复制班级链接
 *   - 学号唯一识别：已存在→更新，新学号→添加，缺失→移除
 */

require_once __DIR__ . '/../../includes/config.php';

// 未登录用户跳转登录页
if (!isTeacherLoggedIn()) {
  redirect('login.php');
}

$db = getDB();
$teacherId = getCurrentTeacherId();

// 获取班级 ID
$classId = intval($_GET['id'] ?? 0);
if ($classId <= 0) {
  redirect('classes.php');
}

// 验证班级归属
$stmt = $db->prepare("SELECT * FROM classes WHERE id = ? AND teacher_id = ?");
$stmt->execute([$classId, $teacherId]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
  // 班级不存在或不属于当前教师
  redirect('classes.php');
}

$message = '';
$messageType = 'info';

// ========== 处理 CSV 导入 ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
  $file = $_FILES['csv_file'];

  // 检查上传是否成功
  if ($file['error'] !== UPLOAD_ERR_OK) {
    $message = '文件上传失败，请重试';
    $messageType = 'danger';
  } else {
    // 读取文件内容（尝试 UTF-8 和 GBK 编码）
    $content = file_get_contents($file['tmp_name']);
    
    // 检测并转换编码（学校常用 Excel 导出为 GBK）
    $encoding = mb_detect_encoding($content, ['UTF-8', 'GBK', 'GB2312'], true);
    if ($encoding && $encoding !== 'UTF-8') {
      $content = mb_convert_encoding($content, 'UTF-8', $encoding);
    }

    // 按行解析，兼容 \n 和 \r\n
    $lines = preg_split('/\r\n|\r|\n/', trim($content));
    
    // 第一行是标题行，跳过
    $header = str_getcsv(array_shift($lines) ?? '');
    
    // 统计
    $imported = 0;
    $updated = 0;
    $skipped = 0;
    $errors = [];
    $newStudentIds = []; // 记录本次导入的学号，用于后续清理

    // 开始事务
    $db->beginTransaction();
    try {
      foreach ($lines as $lineNum => $line) {
        $line = trim($line);
        if ($line === '') continue;

        $row = str_getcsv($line);
        if (count($row) < 2) {
          $skipped++;
          continue; // 跳过空行或格式不对的行
        }

        // CSV 列：姓名, 学号, 性别, 分数
        $name = trim($row[0] ?? '');
        $studentId = trim($row[1] ?? '');
        $gender = trim($row[2] ?? '');
        $score = floatval($row[3] ?? 0);

        // 姓名和学号为必填
        if ($name === '' || $studentId === '') {
          $skipped++;
          continue;
        }

        // 规范化性别
        if (!in_array($gender, ['男', '女'])) {
          $gender = ''; // 不是男/女就留空
        }

        $newStudentIds[] = $studentId;

        // 查找该学号是否已存在于此班级
        $check = $db->prepare("SELECT id FROM students WHERE class_id = ? AND student_id = ?");
        $check->execute([$classId, $studentId]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
          // 已存在 → 更新姓名、性别、分数（保留发言数据）
          $update = $db->prepare("UPDATE students SET name = ?, gender = ?, score = ? WHERE id = ?");
          $update->execute([$name, $gender, $score, $existing['id']]);
          $updated++;
        } else {
          // 新学号 → 添加
          $insert = $db->prepare("INSERT INTO students (class_id, student_id, name, gender, score) VALUES (?, ?, ?, ?, ?)");
          $insert->execute([$classId, $studentId, $name, $gender, $score]);
          $imported++;
        }
      }

      // 删除不在新 CSV 中的学生（但发言记录保留在 speaking_records 表中）
      if (!empty($newStudentIds)) {
        // 构建占位符
        $placeholders = implode(',', array_fill(0, count($newStudentIds), '?'));
        $deleteStmt = $db->prepare("DELETE FROM students WHERE class_id = ? AND student_id NOT IN ($placeholders)");
        $deleteStmt->execute(array_merge([$classId], $newStudentIds));
        $removed = $deleteStmt->rowCount();
      }

      $db->commit();

      // 更新成绩等级（根据当前阈值重新计算）
      updateGradeLevels($db, $classId);

      $message = "导入完成！新增 {$imported} 人，更新 {$updated} 人" . (($removed ?? 0) > 0 ? "，移除 {$removed} 人" : "") . "，跳过 {$skipped} 行";
      $messageType = 'success';
    } catch (Exception $e) {
      $db->rollBack();
      $message = '导入失败：' . $e->getMessage();
      $messageType = 'danger';
    }
  }
}

// ========== 查询学生列表 ==========
$students = $db->prepare("
  SELECT s.*,
    COALESCE(sr.count, 0) AS speak_count
  FROM students s
  LEFT JOIN speaking_records sr ON sr.student_id = s.id
    AND sr.class_id = s.class_id
    AND sr.is_archived = 0
  WHERE s.class_id = ?
  ORDER BY s.student_id ASC
");
$students->execute([$classId]);
$students = $students->fetchAll(PDO::FETCH_ASSOC);

/**
 * 根据班级配置的成绩阈值，更新所有学生的等级
 */
function updateGradeLevels(PDO $db, int $classId): void {
  // 获取阈值配置
  $config = $db->prepare("SELECT threshold_a, threshold_b FROM grouping_config WHERE class_id = ?");
  $config->execute([$classId]);
  $cfg = $config->fetch(PDO::FETCH_ASSOC);
  $thresholdA = $cfg['threshold_a'] ?? 85;
  $thresholdB = $cfg['threshold_b'] ?? 75;

  // 更新所有学生等级
  $update = $db->prepare("
    UPDATE students SET grade_level = CASE
      WHEN score >= ? THEN 'A'
      WHEN score >= ? THEN 'B'
      ELSE 'C'
    END
    WHERE class_id = ?
  ");
  $update->execute([$thresholdA, $thresholdB, $classId]);
}

// 获取分组配置
$configStmt = $db->prepare("SELECT * FROM grouping_config WHERE class_id = ?");
$configStmt->execute([$classId]);
$groupConfig = $configStmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = $class['class_name'] . ' - 班级详情';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- ========== 页面标题与操作 ========== -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h3 class="mb-1"><?php echo htmlspecialchars($class['class_name']); ?></h3>
    <span class="text-muted">
      🎫 班级号：<code class="fs-5"><?php echo htmlspecialchars($class['class_code']); ?></code>
      · 🎒 <?php echo count($students); ?> 名学生
    </span>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-success btn-sm" onclick="copyClassLink()">
      📋 复制班级链接
    </button>
    <a href="classes.php" class="btn btn-outline-secondary btn-sm">← 返回班级列表</a>
  </div>
</div>

<!-- 班级链接（隐藏，用于复制） -->
<input type="hidden" id="classLink" value="http://localhost:8888/?class=<?php echo htmlspecialchars($class['class_code']); ?>">

<!-- ========== 消息提示 ========== -->
<?php if ($message): ?>
  <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="row g-4">
  <!-- ========== 左栏：CSV 导入 ========== -->
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">📥 导入学生名单</h5>
      </div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" action="class_detail.php?id=<?php echo $classId; ?>">
          <div class="mb-3">
            <label for="csv_file" class="form-label">选择 CSV 文件</label>
            <input
              type="file"
              class="form-control"
              id="csv_file"
              name="csv_file"
              accept=".csv"
              required
            >
            <div class="form-text">
              文件格式：<strong>姓名, 学号, 性别, 分数</strong>（第一行为标题行，自动跳过）
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 mb-2">
            导入名单
          </button>
        </form>

        <!-- CSV 格式示例 -->
        <div class="mt-3">
          <h6 class="mb-2">📋 CSV 格式示例</h6>
          <pre class="bg-light p-2 rounded small mb-0" style="font-size: 0.8rem;">姓名,学号,性别,分数
张三,20240101,男,92
李四,20240102,女,78
王五,20240103,男,65</pre>
          <small class="text-muted">
            ⚠️ 姓名和学号为<strong>必填</strong>；性别和分数仅分组模块使用，可不填
          </small>
        </div>

        <!-- 导入规则说明 -->
        <div class="alert alert-light mt-3 mb-0 small">
          <strong>🔄 导入规则：</strong>
          <ul class="mb-0 ps-3">
            <li>学号已存在 → 更新姓名/性别/分数，<strong>保留发言记录</strong></li>
            <li>新学号 → 自动添加</li>
            <li>未出现在新 CSV 中 → 从名单移除（历史发言仍保留）</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- ========== 右栏：学生列表 ========== -->
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">🎒 学生名单</h5>
        <?php if (!empty($students)): ?>
          <span class="badge bg-primary"><?php echo count($students); ?> 人</span>
        <?php endif; ?>
      </div>
      <div class="card-body p-0">
        <?php if (empty($students)): ?>
          <!-- 空状态 -->
          <div class="text-center py-5">
            <div class="fs-1 mb-3">📭</div>
            <p class="text-muted">还没有导入学生名单</p>
            <p class="text-muted small">请在左侧上传 CSV 文件导入学生数据</p>
          </div>
        <?php else: ?>
          <!-- 学生表格 -->
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>学号</th>
                  <th>姓名</th>
                  <th>性别</th>
                  <th>分数</th>
                  <th>等级</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($students as $s): ?>
                  <tr>
                    <td><code><?php echo htmlspecialchars($s['student_id']); ?></code></td>
                    <td><?php echo htmlspecialchars($s['name']); ?></td>
                    <td><?php echo $s['gender'] ?: '-'; ?></td>
                    <td><?php echo $s['score'] > 0 ? $s['score'] : '-'; ?></td>
                    <td>
                      <?php if ($s['grade_level']): ?>
                        <span class="badge badge-level-<?php echo strtolower($s['grade_level']); ?>">
                          <?php echo htmlspecialchars($s['grade_level']); ?>
                        </span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ========== 复制班级链接的 JS ========== -->
<script>
function copyClassLink() {
  const link = document.getElementById('classLink').value;
  navigator.clipboard.writeText(link).then(() => {
    showToast('班级链接已复制到剪贴板！', 'success');
  }).catch(() => {
    // 回退方案
    const input = document.createElement('input');
    input.value = link;
    document.body.appendChild(input);
    input.select();
    document.execCommand('copy');
    document.body.removeChild(input);
    showToast('班级链接已复制！', 'success');
  });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>