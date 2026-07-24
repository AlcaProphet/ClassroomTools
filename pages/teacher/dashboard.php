<?php
/**
 * 教师仪表盘
 * 
 * 功能：
 *   - 欢迎信息 + 管理面板入口
 *   - 显示教师身份（管理员/普通教师）
 *   - 快速入口：班级管理、发言均衡、智能分组
 *   - 基础统计概览
 */

require_once __DIR__ . '/../../includes/config.php';

// 未登录用户跳转登录页
if (!isTeacherLoggedIn()) {
  redirect('login.php');
}

$db = getDB();
$teacherId = getCurrentTeacherId();

// ---------- 查询统计数据 ----------
$classCount = $db->prepare("SELECT COUNT(*) FROM classes WHERE teacher_id = ?");
$classCount->execute([$teacherId]);
$classCount = $classCount->fetchColumn();

$studentCount = $db->prepare("
  SELECT COUNT(*) FROM students s
  JOIN classes c ON s.class_id = c.id
  WHERE c.teacher_id = ?
");
$studentCount->execute([$teacherId]);
$studentCount = $studentCount->fetchColumn();

$pageTitle = '教师仪表盘';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- ========== 欢迎区域 ========== -->
<div class="row mb-4">
  <div class="col-12">
    <div class="d-flex align-items-center justify-content-between">
      <div>
        <h3 class="mb-1">
          欢迎回来，<?php echo htmlspecialchars(getCurrentTeacherName()); ?> 👋
        </h3>
        <p class="text-muted mb-0">
          <?php if ($_SESSION['is_admin'] ?? false): ?>
            <span class="badge bg-warning text-dark me-2">👑 管理员</span>
          <?php endif; ?>
          一切准备就绪，开始今天的课堂互动吧
        </p>
      </div>
    </div>
  </div>
</div>

<!-- ========== 统计卡片 ========== -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card text-center p-3">
      <div class="fs-2 mb-1">🏫</div>
      <div class="fs-3 fw-bold"><?php echo $classCount; ?></div>
      <div class="text-muted">我的班级</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-center p-3">
      <div class="fs-2 mb-1">🎒</div>
      <div class="fs-3 fw-bold"><?php echo $studentCount; ?></div>
      <div class="text-muted">学生总数</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-center p-3">
      <div class="fs-2 mb-1">📅</div>
      <div class="fs-5 fw-bold"><?php echo date('Y-m-d'); ?></div>
      <div class="text-muted">今天</div>
    </div>
  </div>
</div>

<!-- ========== 功能入口 ========== -->
<h5 class="mb-3">📋 功能入口</h5>
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <a href="classes.php" class="text-decoration-none">
      <div class="card cursor-pointer h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="fs-1">🏫</div>
          <div>
            <h6 class="mb-1">班级管理</h6>
            <small class="text-muted">创建班级 · 导入名单 · 复制班级链接</small>
          </div>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-6">
    <a href="classes.php" class="text-decoration-none">
      <div class="card cursor-pointer h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="fs-1">🗣️</div>
          <div>
            <h6 class="mb-1">发言均衡</h6>
            <small class="text-muted">选择班级 → 开始课堂互动</small>
          </div>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-6">
    <a href="../../index.php" class="text-decoration-none">
      <div class="card cursor-pointer h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="fs-1">📊</div>
          <div>
            <h6 class="mb-1">历史记录</h6>
            <small class="text-muted">在发言页面中查看</small>
          </div>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-6">
    <a href="../../index.php" class="text-decoration-none">
      <div class="card cursor-pointer h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="fs-1">👥</div>
          <div>
            <h6 class="mb-1">智能分组</h6>
            <small class="text-muted">Phase 5 即将实现</small>
          </div>
        </div>
      </div>
    </a>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
