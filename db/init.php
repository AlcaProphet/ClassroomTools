<?php
/**
 * 数据库初始化脚本
 * 首次运行时自动创建所有必需的数据表
 * 
 * 用法：在 index.php 或其他入口文件中 require 本文件即可
 */

require_once __DIR__ . '/../includes/config.php';

// ---------- 创建数据表 ----------

$db = getDB();

// 教师表：存储注册教师信息
$db->exec("
  CREATE TABLE IF NOT EXISTS teachers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,   -- 用户名，用于登录
    password_hash TEXT NOT NULL,     -- 密码哈希值（password_hash 加密）
    is_admin INTEGER DEFAULT 0,     -- 是否管理员（首位注册者自动为 1）
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  )
");

// 班级表：每个班级由一位教师创建，自动生成 6 位班级号
$db->exec("
  CREATE TABLE IF NOT EXISTS classes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teacher_id INTEGER NOT NULL,     -- 创建该班级的教师 ID
    class_code TEXT NOT NULL UNIQUE,  -- 6 位数字班级号，学生凭此加入
    class_name TEXT NOT NULL,        -- 班级名称（如三年级一班）
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id)
  )
");

// 学生表：通过 CSV 导入，学号为唯一标识
$db->exec("
  CREATE TABLE IF NOT EXISTS students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    class_id INTEGER NOT NULL,       -- 所属班级 ID
    student_id TEXT NOT NULL,        -- 学号（在班级内唯一）
    name TEXT NOT NULL,              -- 姓名
    gender TEXT DEFAULT '',          -- 性别（男/女，仅分组用）
    score REAL DEFAULT 0,            -- 分数（用于成绩等级计算）
    grade_level TEXT DEFAULT '',     -- 成绩等级（A/B/C，由系统自动计算）
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    UNIQUE(class_id, student_id)     -- 同一班级内学号唯一
  )
");

// 发言记录表：按周存储每位学生的发言次数
$db->exec("
  CREATE TABLE IF NOT EXISTS speaking_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,     -- 学生 ID
    class_id INTEGER NOT NULL,       -- 班级 ID
    week_start TEXT NOT NULL,        -- 本周起始日期（周一）
    count INTEGER DEFAULT 0,         -- 本周发言次数
    is_archived INTEGER DEFAULT 0,   -- 是否已归档（0=当前周，1=历史归档）
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
  )
");

// 分组表：每次"开始分组"覆盖并保存最近一次结果
$db->exec("
  CREATE TABLE IF NOT EXISTS groups_result (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    class_id INTEGER NOT NULL,       -- 班级 ID
    group_number INTEGER NOT NULL,   -- 组号
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id)
  )
");

// 分组成员表：记录每组包含哪些学生
$db->exec("
  CREATE TABLE IF NOT EXISTS group_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,       -- 所属分组 ID
    student_id INTEGER NOT NULL,     -- 学生 ID
    FOREIGN KEY (group_id) REFERENCES groups_result(id),
    FOREIGN KEY (student_id) REFERENCES students(id)
  )
");

// 分组参数配置表：存储教师设置的分组参数
$db->exec("
  CREATE TABLE IF NOT EXISTS grouping_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    class_id INTEGER NOT NULL,       -- 班级 ID
    group_size INTEGER DEFAULT 4,    -- 每组人数，默认 4
    threshold_a REAL DEFAULT 85,     -- A 等级分数线，默认 ≥85
    threshold_b REAL DEFAULT 75,     -- B 等级分数线，默认 ≥75
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    UNIQUE(class_id)
  )
");

// ---------- 初始化完成 ----------
// 创建完成后无输出，仅确保表结构就绪
