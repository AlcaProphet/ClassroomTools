<?php
/**
 * 课堂管理助手 - 主入口页面
 * 
 * 功能：
 *   - 首次访问时自动初始化数据库
 *   - 学生端入口：输入班级号 + 学号
 *   - 教师端入口：登录/注册/仪表盘链接
 *   - 支持 URL 参数 ?class=XXXXXX 预填班级号
 */

require_once __DIR__ . '/db/init.php';
require_once __DIR__ . '/includes/config.php';

// ---------- 页面标题 ----------
$pageTitle = '📚 课堂管理助手 - 首页';
$prefillClass = $_GET['class'] ?? '';  // URL 参数预填班级号

require_once __DIR__ . '/includes/header.php';
?>

<!-- ========== 欢迎区域 ========== -->
<div class="row justify-content-center mb-5">
  <div class="col-lg-8 text-center">
    <h1 class="display-4 fw-bold mb-3">📚 课堂管理助手</h1>
    <p class="lead text-muted">
      帮助教师实现发言均衡分析与智能分组 — 让每一堂课都更公平、更高效
    </p>
  </div>
</div>

<div class="row g-4">
  <!-- ========== 左栏：学生入口 ========== -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">🎒 学生入口</h5>
      </div>
      <div class="card-body">
        <p class="text-muted mb-4">
          无需注册，输入老师给的班级号和你的学号即可查看信息
        </p>

        <!-- 学生进入表单 -->
        <form id="studentEntryForm" action="pages/student/view.php" method="POST">
          <div class="mb-3">
            <label for="classCode" class="form-label">班级号</label>
            <input
              type="text"
              class="form-control form-control-lg"
              id="classCode"
              name="class"
              placeholder="请输入 6 位班级号，如 482951"
              maxlength="6"
              pattern="[0-9]{6}"
              value="<?php echo htmlspecialchars($prefillClass); ?>"
              required
            >
            <div class="form-text">老师会告诉你班级号</div>
          </div>

          <div class="mb-4">
            <label for="studentId" class="form-label">学号</label>
            <input
              type="text"
              class="form-control form-control-lg"
              id="studentId"
              name="student"
              placeholder="请输入你的学号"
              required
            >
          </div>

          <button type="submit" class="btn btn-primary btn-lg w-100">
            进入查看
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- ========== 右栏：教师入口 ========== -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">🧑‍🏫 教师入口</h5>
      </div>
      <div class="card-body">
        <?php if (isTeacherLoggedIn()): ?>
          <!-- 教师已登录：显示快捷入口 -->
          <div class="mb-3">
            <p class="text-muted">
              欢迎回来，<strong><?php echo htmlspecialchars(getCurrentTeacherName()); ?></strong>！
            </p>
          </div>

          <a href="pages/teacher/dashboard.php" class="btn btn-primary btn-lg w-100 mb-3">
            进入管理面板
          </a>

          <a href="pages/teacher/classes.php" class="btn btn-outline-primary w-100 mb-3">
            管理我的班级
          </a>

        <?php else: ?>
          <!-- 教师未登录：显示登录/注册入口 -->
          <p class="text-muted mb-4">
            教师请先登录或注册账号，然后创建班级、导入名单、开始课堂互动
          </p>

          <a href="pages/teacher/login.php" class="btn btn-primary btn-lg w-100 mb-3">
            教师登录
          </a>

          <a href="pages/teacher/register.php" class="btn btn-outline-primary btn-lg w-100">
            教师注册（新用户）
          </a>
        <?php endif; ?>

        <!-- 功能概览 -->
        <hr class="my-4">
        <div class="row text-center g-2">
          <div class="col-4">
            <div class="p-2">
              <div class="fs-4 mb-1">🗣️</div>
              <small class="text-muted">发言均衡</small>
            </div>
          </div>
          <div class="col-4">
            <div class="p-2">
              <div class="fs-4 mb-1">📊</div>
              <small class="text-muted">历史记录</small>
            </div>
          </div>
          <div class="col-4">
            <div class="p-2">
              <div class="fs-4 mb-1">👥</div>
              <small class="text-muted">智能分组</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
