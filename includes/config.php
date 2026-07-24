<?php
/**
 * 项目配置文件
 * 包含数据库连接、路径常量、基础设置
 */

// ---------- 启动 Session ----------
// 用于教师登录状态保持
session_start();

// ---------- 路径常量 ----------
define('ROOT_DIR', __DIR__ . '/..');           // 项目根目录
define('DB_DIR', ROOT_DIR . '/data');          // 数据库文件存放目录
define('DB_PATH', DB_DIR . '/classroom.db');   // SQLite 数据库文件路径
define('INCLUDES_DIR', ROOT_DIR . '/includes');// 公共组件目录
define('ASSETS_DIR', ROOT_DIR . '/assets');    // 静态资源目录

// ---------- 数据库连接 ----------
/**
 * 获取 SQLite 数据库连接（PDO）
 * 数据库文件不存在时自动创建
 */
function getDB(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    // 确保 data 目录存在
    if (!is_dir(DB_DIR)) {
      mkdir(DB_DIR, 0755, true);
    }
    $pdo = new PDO('sqlite:' . DB_PATH);
    // 设置错误模式为异常，方便调试
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // 开启外键约束
    $pdo->exec('PRAGMA foreign_keys = ON');
  }
  return $pdo;
}

// ---------- 基础工具函数 ----------

/**
 * 生成 6 位随机数字班级号
 * 如 482951，用于学生凭班级号加入
 */
function generateClassCode(): string {
  // 生成 100000 ~ 999999 之间的随机整数
  return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * 安全跳转，防止 Header 注入
 */
function redirect(string $url): void {
  header('Location: ' . $url);
  exit;
}

/**
 * 检查教师是否已登录
 */
function isTeacherLoggedIn(): bool {
  return isset($_SESSION['teacher_id']);
}

/**
 * 获取当前登录教师 ID
 */
function getCurrentTeacherId(): ?int {
  return $_SESSION['teacher_id'] ?? null;
}

/**
 * 获取当前登录教师姓名
 */
function getCurrentTeacherName(): ?string {
  return $_SESSION['teacher_name'] ?? null;
}

/**
 * 简单输出调试信息（仅在开发阶段使用）
 */
function dd($var): void {
  echo '<pre>';
  var_dump($var);
  echo '</pre>';
  exit;
}
