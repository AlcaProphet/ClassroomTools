<?php
/**
 * 班级管理页面
 * 
 * 功能：
 *   - 查看当前教师的所有班级
 *   - 创建新班级（自动生成 6 位班级号）
 *   - 删除班级（需输入班级号确认）
 */

require_once __DIR__ . '/../../includes/config.php';

// 未登录用户跳转登录页
if (!isTeacherLoggedIn()) {
  redirect('login.php');
}

$db = getDB();
$teacherId = getCurrentTeacherId();

// 验证 Session 中的教师 ID 在数据库中仍然有效（防止教师被清理后 Session 残留）
$stmt = $db->prepare("SELECT id FROM teachers WHERE id = ?");
$stmt->execute([$teacherId]);
if (!$stmt->fetch()) {
  // Session 中的教师已不存在，清除 Session 并重新登录
  session_destroy();
  redirect('login.php');
}

$message = '';
$messageType = 'info';  // success | danger | info

// ========== 处理创建班级 ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  
  // --- 创建班级 ---
  if ($_POST['action'] === 'create') {
    $className = trim($_POST['class_name'] ?? '');
    
    if ($className === '') {
      $message = '班级名称不能为空';
      $messageType = 'danger';
    } else {
      // 生成唯一 6 位班级号（避免与已有班级号冲突）
      $code = generateClassCode();
      $maxRetry = 10;
      while ($maxRetry > 0) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM classes WHERE class_code = ?");
        $stmt->execute([$code]);
        if ($stmt->fetchColumn() == 0) break;
        $code = generateClassCode();
        $maxRetry--;
      }

      // 写入数据库（使用事务保证原子性）
      $db->beginTransaction();
      try {
        $stmt = $db->prepare("INSERT INTO classes (teacher_id, class_code, class_name) VALUES (?, ?, ?)");
        $stmt->execute([$teacherId, $code, $className]);
        $classId = $db->lastInsertId();

        if ($classId <= 0) {
          throw new Exception('班级创建失败');
        }

        // 清理可能残留的孤儿配置记录（安全起见）
        $db->prepare("DELETE FROM grouping_config WHERE class_id = ?")->execute([$classId]);
        // 初始化该班级的分组配置
        $db->prepare("INSERT INTO grouping_config (class_id) VALUES (?)")->execute([$classId]);

        $db->commit();
        $message = "班级「{$className}」创建成功！班级号：<strong>{$code}</strong>";
        $messageType = 'success';
      } catch (Exception $e) {
        $db->rollBack();
        $message = '创建失败：' . $e->getMessage();
        $messageType = 'danger';
      }
    }
  }

  // --- 删除班级 ---
  if ($_POST['action'] === 'delete') {
    $classId = intval($_POST['class_id'] ?? 0);
    $confirmCode = trim($_POST['confirm_code'] ?? '');

    // 查询班级信息
    $stmt = $db->prepare("SELECT * FROM classes WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$classId, $teacherId]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$class) {
      $message = '班级不存在或无权操作';
      $messageType = 'danger';
    } elseif ($confirmCode !== $class['class_code']) {
      $message = '班级号输入不正确，删除已取消';
      $messageType = 'danger';
    } else {
      // 级联删除：学生 → 发言记录 → 分组成员 → 分组结果 → 分组配置 → 班级
      $db->beginTransaction();
      try {
        // 删除该班级下所有学生的发言记录
        $db->prepare("DELETE FROM speaking_records WHERE class_id = ?")->execute([$classId]);
        // 删除分组成员
        $db->prepare("DELETE FROM group_members WHERE group_id IN (SELECT id FROM groups_result WHERE class_id = ?)")->execute([$classId]);
        // 删除分组结果
        $db->prepare("DELETE FROM groups_result WHERE class_id = ?")->execute([$classId]);
        // 删除分组配置
        $db->prepare("DELETE FROM grouping_config WHERE class_id = ?")->execute([$classId]);
        // 删除学生
        $db->prepare("DELETE FROM students WHERE class_id = ?")->execute([$classId]);
        // 删除班级
        $db->prepare("DELETE FROM classes WHERE id = ?")->execute([$classId]);
        $db->commit();
        $message = "班级「{$class['class_name']}」及其所有数据已删除";
        $messageType = 'success';
      } catch (Exception $e) {
        $db->rollBack();
        $message = '删除失败，请稍后重试';
        $messageType = 'danger';
      }
    }
  }
}

// ========== 查询所有班级 ==========
$classes = $db->prepare("
  SELECT c.*,
    (SELECT COUNT(*) FROM students WHERE class_id = c.id) AS student_count
  FROM classes c
  WHERE c.teacher_id = ?
  ORDER BY c.created_at DESC
");
$classes->execute([$teacherId]);
$classes = $classes->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = '班级管理';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- ========== 页面标题 ========== -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">🏫 班级管理</h3>
  <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">← 返回仪表盘</a>
</div>

<!-- ========== 消息提示 ========== -->
<?php if ($message): ?>
  <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="row g-4">
  <!-- ========== 左栏：创建班级 ========== -->
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">➕ 创建新班级</h5>
      </div>
      <div class="card-body">
        <form method="POST" action="classes.php">
          <input type="hidden" name="action" value="create">
          <div class="mb-3">
            <label for="class_name" class="form-label">班级名称</label>
            <input
              type="text"
              class="form-control form-control-lg"
              id="class_name"
              name="class_name"
              placeholder="例如：三年级一班"
              required
              autofocus
            >
            <div class="form-text">系统将自动生成 6 位数字班级号</div>
          </div>
          <button type="submit" class="btn btn-primary btn-lg w-100">
            创建班级
          </button>
        </form>
      </div>
    </div>

    <!-- 使用提示 -->
    <div class="card mt-4">
      <div class="card-body">
        <h6 class="mb-2">💡 班级号说明</h6>
        <p class="text-muted small mb-0">
          每个班级都有一个 6 位数字班级号（如 <code>482951</code>），
          类似 Kahoot! 的房间码。将班级号告知学生后，学生即可凭"班级号 + 学号"加入课堂。
        </p>
      </div>
    </div>
  </div>

  <!-- ========== 右栏：班级列表 ========== -->
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">📋 我的班级</h5>
        <span class="badge bg-primary">共 <?php echo count($classes); ?> 个</span>
      </div>
      <div class="card-body">
        <?php if (empty($classes)): ?>
          <!-- 空状态 -->
          <div class="text-center py-5">
            <div class="fs-1 mb-3">📭</div>
            <p class="text-muted">还没有创建任何班级</p>
            <p class="text-muted small">在左侧创建你的第一个班级吧！</p>
          </div>
        <?php else: ?>
          <!-- 班级列表 -->
          <div class="list-group">
            <?php foreach ($classes as $class): ?>
              <div class="list-group-item border-0 mb-2 rounded-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="mb-1">
                      <?php echo htmlspecialchars($class['class_name']); ?>
                    </h6>
                    <div class="d-flex gap-3">
                      <small class="text-muted">
                        🎫 班级号：<code class="fs-6"><?php echo htmlspecialchars($class['class_code']); ?></code>
                      </small>
                      <small class="text-muted">
                        🎒 <?php echo $class['student_count']; ?> 名学生
                      </small>
                    </div>
                  </div>
                  <div class="d-flex gap-2">
                    <!-- 发言管理 -->
                    <a href="speak.php?class_id=<?php echo $class['id']; ?>" class="btn btn-outline-success btn-sm">
                      🗣️ 发言
                    </a>
                    <!-- 智能分组 -->
                    <a href="group.php?class_id=<?php echo $class['id']; ?>" class="btn btn-outline-info btn-sm">
                      👥 分组
                    </a>
                    <!-- 进入班级 -->
                    <a href="class_detail.php?id=<?php echo $class['id']; ?>" class="btn btn-outline-primary btn-sm">
                      名单
                    </a>
                    <!-- 删除班级（模态框触发） -->
                    <button
                      type="button"
                      class="btn btn-outline-danger btn-sm"
                      data-bs-toggle="modal"
                      data-bs-target="#deleteModal<?php echo $class['id']; ?>"
                    >
                      删除
                    </button>
                  </div>
                </div>
              </div>

              <!-- 删除确认模态框 -->
              <div class="modal fade" id="deleteModal<?php echo $class['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <form method="POST" action="classes.php">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                      <div class="modal-header bg-danger text-white">
                        <h6 class="modal-title">⚠️ 确认删除班级</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <p>即将删除班级：<strong><?php echo htmlspecialchars($class['class_name']); ?></strong></p>
                        <p class="text-danger small">此操作将连同学生名单、发言记录、分组数据一并删除，不可恢复！</p>
                        <div class="mb-3">
                          <label class="form-label">请输入班级号 <code><?php echo htmlspecialchars($class['class_code']); ?></code> 确认：</label>
                          <input
                            type="text"
                            class="form-control"
                            name="confirm_code"
                            placeholder="请输入班级号"
                            required
                          >
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-danger">确认删除</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
