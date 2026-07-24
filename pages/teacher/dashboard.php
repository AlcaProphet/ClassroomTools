<?php
/**
 * 教师仪表盘（占位 - Phase 3 实现）
 */
require_once __DIR__ . '/../../includes/config.php';
if (!isTeacherLoggedIn()) { redirect('login.php'); }
$pageTitle = '教师仪表盘';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0">📋 教师仪表盘</h5>
  </div>
  <div class="card-body">
    <p class="text-muted">此功能将在 Phase 3 实现。</p>
    <a href="../../index.php" class="btn btn-outline-secondary">返回首页</a>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
