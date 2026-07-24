<?php
/**
 * 教师登录页面
 * 
 * 功能：
 *   - 用户名 + 密码登录
 *   - 验证成功后写入 Session
 *   - 登录后跳转仪表盘
 */

require_once __DIR__ . '/../../includes/config.php';

// 已登录用户直接跳转仪表盘
if (isTeacherLoggedIn()) {
  redirect('../teacher/dashboard.php');
}

$error = '';

// ---------- 处理登录表单提交 ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  // 基础校验
  if ($username === '' || $password === '') {
    $error = '请输入用户名和密码';
  } else {
    $db = getDB();

    // 查找用户
    $stmt = $db->prepare("SELECT id, username, password_hash, is_admin FROM teachers WHERE username = ?");
    $stmt->execute([$username]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
      $error = '用户名不存在，请先注册';
    } elseif (!password_verify($password, $teacher['password_hash'])) {
      $error = '密码错误，请重试';
    } else {
      // 登录成功 → 写入 Session
      $_SESSION['teacher_id'] = $teacher['id'];
      $_SESSION['teacher_name'] = $teacher['username'];
      $_SESSION['is_admin'] = $teacher['is_admin'];

      // 跳转到仪表盘
      redirect('../teacher/dashboard.php');
    }
  }
}

$pageTitle = '教师登录';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">🔐 教师登录</h5>
      </div>
      <div class="card-body">

        <!-- 错误提示 -->
        <?php if ($error): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- 登录表单 -->
        <form method="POST" action="login.php" novalidate>
          <div class="mb-3">
            <label for="username" class="form-label">用户名</label>
            <input
              type="text"
              class="form-control form-control-lg"
              id="username"
              name="username"
              placeholder="请输入用户名"
              value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
              required
              autofocus
            >
          </div>

          <div class="mb-4">
            <label for="password" class="form-label">密码</label>
            <input
              type="password"
              class="form-control form-control-lg"
              id="password"
              name="password"
              placeholder="请输入密码"
              required
            >
          </div>

          <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
            登录
          </button>
        </form>

        <div class="text-center">
          <span class="text-muted">还没有账号？</span>
          <a href="register.php">立即注册</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
