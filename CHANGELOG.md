# 📝 变更追踪文档

> 记录项目每次修改的内容、原因、影响范围。
> 格式：日期 + 简短描述 + 涉及文件 + 状态。

---

## 2026-07-24

### 🔧 修复：`str_getcsv()` PHP 8.4+ 兼容性

- **问题**：导入 CSV 时出现大量 Deprecated 警告
- **原因**：PHP 8.4 起 `str_getcsv()` 的 `$escape` 参数必须显式提供
- **修改**：两处调用补全四参数 `(',', '"', '\\')`
- **文件**：`pages/teacher/class_detail.php` (L64, L80)
- **状态**：✅ 已修复

---

### 🔧 修复：创建班级外键约束错误

- **问题**：`FOREIGN KEY constraint failed`
- **原因**：Session 中的 `teacher_id` 指向了已被删除的教师记录
- **修改**：
  - `includes/config.php`：`isTeacherLoggedIn()` 增加数据库验证，Session 失效时自动清除
  - `pages/teacher/classes.php`：班级创建改为事务包裹，插入前清理残留配置
- **文件**：`includes/config.php`, `pages/teacher/classes.php`
- **状态**：✅ 已修复

---

### ✨ Phase 7：项目收尾

- **内容**：README 更新、回归测试、代码清理
- **文件**：`README.md`
- **状态**：✅ 已完成

---

### ✨ Phase 6：学生端只读体验

- **内容**：学生视图（班级号+学号验证、发言次数、分组信息）
- **文件**：`pages/student/view.php`, `index.php`
- **状态**：✅ 已完成

---

### ✨ Phase 5：智能分组模块

- **内容**：分层轮询分组算法、阈值调整、结果导出
- **文件**：`pages/teacher/group.php`, `pages/teacher/group_api.php`, `pages/teacher/classes.php`, `pages/teacher/dashboard.php`
- **状态**：✅ 已完成

---

### ✨ Phase 4：发言均衡热力图

- **内容**：发言按钮计数、热力图可视化、随机点名、周归档
- **文件**：`pages/teacher/speak.php`, `pages/teacher/speak_api.php`, `pages/teacher/classes.php`, `pages/teacher/dashboard.php`
- **状态**：✅ 已完成

---

### ✨ Phase 3：班级与名单管理

- **内容**：班级创建/删除、6 位班级号、CSV 导入、学号更新逻辑
- **文件**：`pages/teacher/classes.php`, `pages/teacher/class_detail.php`
- **状态**：✅ 已完成

---

### ✨ Phase 2：教师账号体系

- **内容**：教师注册/登录/登出、首位管理员
- **文件**：`pages/teacher/register.php`, `pages/teacher/login.php`, `pages/teacher/logout.php`, `pages/teacher/dashboard.php`
- **状态**：✅ 已完成

---

### ✨ Phase 1：基础骨架

- **内容**：目录结构、入口页面、数据库初始化、Bootstrap 引入
- **文件**：`index.php`, `db/init.php`, `includes/*`, `assets/*`
- **状态**：✅ 已完成

---

### 🏁 Phase 0：环境准备

- **内容**：PHP 安装、Git 配置、`.gitignore` 完善
- **状态**：✅ 已完成

---

## 修改模板

新增修改时复制以下模板：

```markdown
### 🔧/✨/🐛 标题

- **问题/需求**：
- **原因**：
- **修改**：
- **文件**：
- **状态**：⏳ 进行中 / ✅ 已完成 / ❌ 已回退
```
