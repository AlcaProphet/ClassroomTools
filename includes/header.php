<?php
/**
 * 公共头部组件
 * 包含 HTML 文档开头、Bootstrap 引入、导航栏
 * 
 * 参数：
 *   $pageTitle - 页面标题（可选）
 *   $activeTab - 当前激活的 Tab（可选）
 */
$pageTitle = $pageTitle ?? '课堂管理助手';
$activeTab = $activeTab ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>

  <!-- Bootstrap 5 CSS（离线引入） -->
  <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">

  <!-- 自定义样式 -->
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<!-- ========== 顶部导航栏 ========== -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
  <div class="container">
    <!-- 品牌标题 -->
    <a class="navbar-brand fw-bold" href="/index.php">
      📚 课堂管理助手
    </a>

    <!-- 右侧操作区 -->
    <div class="d-flex align-items-center gap-3">
      <?php if (isTeacherLoggedIn()): ?>
        <!-- 教师已登录时显示姓名和退出按钮 -->
        <span class="text-muted">
          👤 <?php echo htmlspecialchars(getCurrentTeacherName()); ?>
        </span>
        <a href="/pages/teacher/logout.php" class="btn btn-outline-secondary btn-sm">
          退出登录
        </a>
      <?php else: ?>
        <!-- 未登录时显示登录/注册入口 -->
        <a href="/pages/teacher/login.php" class="btn btn-outline-primary btn-sm">
          教师登录
        </a>
        <a href="/pages/teacher/register.php" class="btn btn-primary btn-sm">
          教师注册
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- ========== 主内容容器 ========== -->
<main class="container">
