<?php
/**
 * 学生端只读视图
 * 
 * 学生无需注册/登录，凭"班级号 + 学号"即可查看：
 *   - 个人发言次数（本周）
 *   - 所在分组信息
 * 
 * 访问方式：
 *   - 首页表单 POST 提交（推荐，学号不进 URL）
 *   - 也可在 URL 中带 ?class=XXXXXX 预填班级号
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../db/init.php';

$db = getDB();

// 获取班级号（POST 优先，其次 GET/URL 参数）
$classCode = trim($_POST['class'] ?? $_GET['class'] ?? '');
$studentId = trim($_POST['student'] ?? '');

$error = '';
$student = null;
$speakCount = 0;
$groupInfo = null;

// ========== 处理查询 ==========
if ($classCode !== '' && $studentId !== '') {
  // 验证班级号
  $stmt = $db->prepare("SELECT id, class_name, class_code FROM classes WHERE class_code = ?");
  $stmt->execute([$classCode]);
  $class = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$class) {
    $error = '班级号未找到，请确认后重新输入';
  } else {
    // 验证学号
    $stmt = $db->prepare("SELECT * FROM students WHERE class_id = ? AND student_id = ?");
    $stmt->execute([$class['id'], $studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
      $error = '该班级中未找到此学号，请确认后重新输入';
    } else {
      // ===== 查询发言次数（本周） =====
      $weekStart = (new DateTime())->modify('-' . ((int)(new DateTime())->format('N') - 1) . ' days')->format('Y-m-d');
      $sr = $db->prepare("SELECT COALESCE(count, 0) AS cnt FROM speaking_records WHERE student_id = ? AND class_id = ? AND week_start = ? AND is_archived = 0");
      $sr->execute([$student['id'], $class['id'], $weekStart]);
      $speakCount = intval($sr->fetchColumn() ?: 0);

      // ===== 查询分组信息 =====
      $gr = $db->prepare("
        SELECT gr.group_number, s2.name, s2.student_id, s2.gender
        FROM group_members gm
        JOIN groups_result gr ON gr.id = gm.group_id
        JOIN group_members gm2 ON gm2.group_id = gr.id
        JOIN students s2 ON s2.id = gm2.student_id
        WHERE gm.student_id = ?
          AND gr.class_id = ?
        ORDER BY gr.group_number, s2.student_id
      ");
      $gr->execute([$student['id'], $class['id']]);
      $groupMembers = $gr->fetchAll(PDO::FETCH_ASSOC);

      if (!empty($groupMembers)) {
        $groupNumber = $groupMembers[0]['group_number'];
        $groupInfo = [
          'group_number' => $groupNumber,
          'members' => $groupMembers
        ];
      }
    }
  }
}

$pageTitle = '学生视图';
$prefillClass = $_GET['class'] ?? $classCode;  // URL 预填班级号
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle; ?></title>
  <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/css/style.css">
  <style>
    /* 学生端专用样式：简洁大方，适合手机查看 */
    .student-container { max-width: 500px; margin: 0 auto; }
    .student-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 12px; padding: 24px; margin-bottom: 20px; }
    .info-card { border-radius: 12px; border: none; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
    .info-card .card-body { padding: 20px; }
    .speak-badge { font-size: 2.5rem; font-weight: 700; color: #4A90D9; }
    .group-member-tag { display: inline-block; background: #f0f0f0; padding: 4px 10px; border-radius: 20px; margin: 3px; font-size: 0.9rem; }
    .group-member-tag.me { background: #e6f7ff; border: 2px solid #4A90D9; font-weight: 600; }
  </style>
</head>
<body style="background: #f5f7fa;">

<div class="student-container py-4 px-3">

  <!-- 已登录则显示返回链接 -->
  <?php if ($student): ?>
    <div class="text-center mb-3">
      <a href="/index.php" class="btn btn-outline-secondary btn-sm">← 返回首页</a>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <!-- ===== 错误提示 ===== -->
    <div class="text-center">
      <div class="fs-1 mb-3">😕</div>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
      <a href="/index.php<?php echo $classCode ? '?class=' . urlencode($classCode) : ''; ?>" class="btn btn-primary">
        🔙 返回重新输入
      </a>
    </div>

  <?php elseif ($student): ?>
    <!-- ===== 学生信息头部 ===== -->
    <div class="student-header text-center">
      <div class="fs-1 mb-2">🎒</div>
      <h4 class="mb-1"><?php echo htmlspecialchars($student['name']); ?></h4>
      <p class="mb-0 opacity-75">
        学号：<?php echo htmlspecialchars($student['student_id']); ?> · 
        班级号：<?php echo htmlspecialchars($classCode); ?>
      </p>
    </div>

    <!-- ===== 我的发言记录 ===== -->
    <div class="card info-card mb-3">
      <div class="card-body text-center">
        <h6 class="text-muted mb-2">🗣️ 本周发言次数</h6>
        <div class="speak-badge"><?php echo $speakCount; ?></div>
        <small class="text-muted"><?php echo $speakCount > 0 ? '继续加油！' : '还没有发言记录'; ?></small>
      </div>
    </div>

    <!-- ===== 我的分组 ===== -->
    <div class="card info-card">
      <div class="card-body">
        <h6 class="text-muted mb-3">👥 我的分组</h6>
        <?php if ($groupInfo): ?>
          <p class="mb-2">
            你属于 <strong>第 <?php echo $groupInfo['group_number']; ?> 组</strong>
            （共 <?php echo count($groupInfo['members']); ?> 人）
          </p>
          <div>
            <?php foreach ($groupInfo['members'] as $m): ?>
              <span class="group-member-tag <?php echo $m['student_id'] === $student['student_id'] ? 'me' : ''; ?>">
                <?php echo htmlspecialchars($m['name']); ?>
                <?php if ($m['gender']): ?>
                  <small class="text-muted">(<?php echo $m['gender']; ?>)</small>
                <?php endif; ?>
              </span>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-muted mb-0">尚未分组，请等待老师完成分组</p>
        <?php endif; ?>
      </div>
    </div>

  <?php else: ?>
    <!-- ===== 未输入：显示入口表单 ===== -->
    <div class="text-center mb-4">
      <div class="fs-1 mb-2">🎒</div>
      <h4>学生入口</h4>
      <p class="text-muted">请输入老师提供的班级号和你的学号</p>
    </div>

    <div class="card info-card">
      <div class="card-body">
        <form method="POST" action="view.php">
          <div class="mb-3">
            <label class="form-label">班级号</label>
            <input type="text" class="form-control form-control-lg" name="class"
              placeholder="6位数字，如 482951" maxlength="6" pattern="[0-9]{6}"
              value="<?php echo htmlspecialchars($prefillClass); ?>" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">学号</label>
            <input type="text" class="form-control form-control-lg" name="student"
              placeholder="请输入你的学号" required>
          </div>
          <button type="submit" class="btn btn-primary btn-lg w-100">进入查看</button>
        </form>
      </div>
    </div>
  <?php endif; ?>

</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
