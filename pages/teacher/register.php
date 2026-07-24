<?php
/**
 * 教师注册页面
 * 
 * 功能：
 *   - 用户名 + 密码注册
 *   - 首位注册者自动成为管理员
 *   - 密码使用 password_hash 加密存储
 *   - 注册成功后自动登录并跳转仪表盘
 */

require_once __DIR__ . '/../../includes/config.php';

// 已登录用户直接跳转仪表盘
if (isTeacherLoggedIn()) {
  redirect('../teacher/dashboard.php');
}

$error = '';
$success = '';

// ---------- 处理注册表单提交 ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $passwordConfirm = $_POST['password_confirm'] ?? '';

  // 基础校验
  if ($username === '' || $password === '') {
    $error = '用户名和密码不能为空';
  } elseif (mb_strlen($username) < 2) {
    $error = '用户名至少需要 2 个字符';
  } elseif (mb_strlen($username) > 20) {
    $error = '用户名不能超过 20 个字符';
  } elseif (mb_strlen($password) < 4) {
    $error = '密码至少需要 4 个字符';
  } elseif ($password !== $passwordConfirm) {
    $error = '两次输入的密码不一致';
  } else {
    // 连接数据库，检查用户名是否已被占用
    $db = getDB();

    $stmt = $db->prepare("SELECT COUNT(*) FROM teachers WHERE username = ?");
    $stmt->execute([$username]);
    $exists = $stmt->fetchColumn() > 0;

    if ($exists) {
      $error = '该用户名已被注册，请换一个试试';
    } else {
      // 判断是否为首位注册者（自动成为管理员）
      $count = $db->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
      $isAdmin = ($count === 0) ? 1 : 0;

      // 密码加密存储
      $passwordHash = password_hash($password, PASSWORD_DEFAULT);

      // 写入数据库
      $stmt = $db->prepare("INSERT INTO teachers (username, password_hash, is_admin) VALUES (?, ?, ?)");
      $stmt->execute([$username, $passwordHash, $isAdmin]);

      // 注册成功 → 自动登录
      $teacherId = $db->lastInsertId();
      $_SESSION['teacher_id'] = $teacherId;
      $_SESSION['teacher_name'] = $username;
      $_SESSION['is_admin'] = $isAdmin;

      // 跳转到仪表盘
      redirect('../teacher/dashboard.php');
    }
  }
}

$pageTitle = '教师注册';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">📝 教师注册</h5>
      </div>
      <div class="card-body">

        <!-- 错误提示 -->
        <?php if ($error): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- 注册表单 -->
        <form method="POST" action="register.php" novalidate>
          <div class="mb-3">
            <label for="username" class="form-label">用户名</label>
            <input
              type="text"
              class="form-control form-control-lg"
              id="username"
              name="username"
              placeholder="请输入用户名（2-20个字符）"
              maxlength="20"
              value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
              required
              autofocus
            >
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">密码</label>
            <input
              type="password"
              class="form-control form-control-lg"
              id="password"
              name="password"
              placeholder="请输入密码（至少4个字符）"
              minlength="4"
              required
            >
          </div>

          <div class="mb-4">
            <label for="password_confirm" class="form-label">确认密码</label>
            <input
              type="password"
              class="form-control form-control-lg"
              id="password_confirm"
              name="password_confirm"
              placeholder="请再次输入密码"
              minlength="4"
              required
            >
          </div>

          <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
            注册
          </button>
        </form>

        <div class="text-center">
          <span class="text-muted">已有账号？</span>
          <a href="login.php">立即登录</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
