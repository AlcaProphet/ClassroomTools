# 📚 课堂管理助手 (ClassroomTools)

> ⚠️ **此项目仅作为非专业开发人员的 AI 编程学习项目，请勿用于生产力用途。**
---
> ⚠️ **如想学习请自行 FORK 引用到自己的 Github 中，提交 PULL REQUEST 或 ISSUE 不一定会同意并处理**

---

## 🎯 这是什么？

**课堂管理助手** 是一个面向教师的 Web 辅助工具，集成多项课堂管理功能。它同时也是专为 **Vibe Coding 初学者** 设计的开源协作项目 —— 你不需要有编程经验，用自然语言描述需求，AI 帮你写代码。

### 包含的功能模块

| 模块 | 解决什么问题 |
|------|-------------|
| 🗣️ **发言均衡热力图** | 追踪学生发言次数，用热力图颜色可视化参与度，快速识别被忽略或过度提问的学生，支持每周自动归档与历史回顾 |
| 👥 **智能分层分组器** | 按性别 + 成绩等级（A/B/C 可调阈值）自动均匀分组，每组在性别比例和成绩分布上尽量均衡 |

### 两种使用角色

| 角色 | 如何使用 | 能做什么 |
|------|---------|---------|
| 🧑‍🏫 **教师端** | 注册/登录账号 | 创建班级、导入学生名单、发言计数、查看热力图与历史记录、智能分组 |
| 🎒 **学生端** | 无需注册，凭"班级号 + 学号"访问 | 只读查看个人发言次数和所在分组信息 |

> 📖 完整的功能细节、交互逻辑、验收标准见 **[DesignGuide.md](./DesignGuide.md)**。

---

## 🏗️ 技术全景

| 层面 | 技术选择 | 为什么这样选 |
|------|---------|-------------|
| 🖥️ 前端 | HTML + CSS + JavaScript（ES6+ 原生） | 零门槛，不引入框架，浏览器就能跑 |
| 🎨 UI | Bootstrap（离线引入） | 不用手写样式，快速出界面 |
| 🔧 后端 | PHP（面向过程，无框架） | 语法简单，部署方便，适合新手 |
| 💾 数据库 | SQLite（单文件，零配置） | 无需安装数据库服务，数据持久化 |
| 📦 前端缓存 | localStorage | 缓存学生名单等基础数据，支持有限的离线功能 |
| 🔐 运行环境 | NGINX + WAF | 已有基础安全防护 |

### 代码风格

- **PHP**：面向过程，直接操作 SQLite，不使用框架
- **JavaScript**：ES6+ 原生语法，可引入 jQuery 等基础库，不使用复杂框架
- **CSS**：以 Bootstrap 为基础，自定义样式覆盖
- **注释**：关键逻辑添加中文注释，面向零基础新手

### 设计原则

- 🧒 **极简至上**：代码能跑就行，不求工业级优雅
- 🚫 **不防御性编程**：不过度校验、不处理极端边界
- 🔓 **不纠结安全**：运行环境已有 WAF + NGINX 防护
- 👥 **不考虑并发**：面向单班级、单教师使用场景
- 📖 **中文注释**：运行逻辑加中文注释，方便新手理解

---

## 🔑 核心设计

### 账号体系

- **仅教师需注册账号**，学生无需注册
- 教师自行注册（用户名 + 密码），**首位注册者自动成为管理员**
- 登录状态由服务端 Session 维持
- 教师仅可见和管理自己创建的班级，不同教师之间数据互不可见
- 预留 OIDC 第三方登录接口（未来可接入微信等）

### 多班级 & 房间号

类似 Kahoot! 的房间机制：
- 教师创建班级 → 系统自动生成 **6 位数字班级号**（如 `482951`）
- 教师将班级号告知学生 → 学生凭"班级号 + 学号"即可访问
- 每个班级的学生名单独立，数据完全隔离

```
教师创建班级 → 生成房间号 482951 → 分享给学生 → 学生凭房间号 + 学号加入
```

### 学生端访问

学生无需登录，两种方式进入：

| 方式 | 说明 |
|------|------|
| 🔗 **带参链接直达** | `?class=482951&student=20240101`，直接进入本人视图 |
| ⌨️ **入口页输入** | 打开无参数地址，手动输入班级号 + 学号后进入 |

### 数据存储

- **服务端为主**：数据存储在 SQLite 数据库中，刷新页面或换设备登录后数据不丢失
- **前端为辅**：localStorage 缓存学生名单等基础数据，支持随机点名等有限的离线功能；每次页面加载时自动从服务端同步最新数据覆盖本地缓存
- 发言数据按**自然周自动归档**（每周一零点），历史记录可查看但不可修改

---

## 🧭 你为什么要参与？

这个项目的真正目标 **不是做出一个完美的软件**，而是：

| 序号 | 你要学什么 | 怎么学 |
|------|-----------|--------|
| 1 | **Vibe Coding** | 用自然语言向 Copilot 描述需求，AI 生成代码，你验证调优 |
| 2 | **Git & GitHub** | 从 Fork 到 Pull Request，走一遍开源协作全流程 |
| 3 | **前后端协作** | 理解浏览器 ↔ 服务器（PHP + SQLite）是如何配合工作的 |
| 4 | **看懂设计文档** | `DesignGuide.md` 就是你的"产品需求文档" |

> 💡 **Vibe Coding** = 用对话的方式编程。你告诉 AI 要什么，AI 写代码，你负责测试和把关。

---

## 🚀 快速开始

### 你只需要

- 一台电脑（Windows / macOS / Linux 都行）
- [VS Code](https://code.visualstudio.com/)（推荐，带 Copilot 更好）
- 一个 GitHub 账号
- PHP 运行环境（[XAMPP](https://www.apachefriends.org/) 或 [MAMP](https://www.mamp.info/) 一键安装，内置 SQLite 支持）

### 三步上手

```bash
# 1. 克隆项目
git clone https://github.com/YOUR_USERNAME/ClassroomTools.git
cd ClassroomTools

# 2. 下载 Bootstrap 离线文件（首次运行必需）
curl -o assets/bootstrap/bootstrap.min.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css
curl -o assets/bootstrap/bootstrap.bundle.min.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js

# 3. 启动本地开发服务器
php -S localhost:8888

# 4. 打开浏览器访问
# http://localhost:8888
```

### 可用页面一览

| 角色 | 页面 | 地址 |
|------|------|------|
| 🧑‍🏫 教师 | 首页 | `/index.php` |
| 🧑‍🏫 教师 | 注册 | `/pages/teacher/register.php` |
| 🧑‍🏫 教师 | 登录 | `/pages/teacher/login.php` |
| 🧑‍🏫 教师 | 仪表盘 | `/pages/teacher/dashboard.php` |
| 🧑‍🏫 教师 | 班级管理 | `/pages/teacher/classes.php` |
| 🧑‍🏫 教师 | 发言管理 | `/pages/teacher/speak.php?class_id=1` |
| 🧑‍🏫 教师 | 智能分组 | `/pages/teacher/group.php?class_id=1` |
| 🎒 学生 | 入口 | `/pages/student/view.php?class=430602` |

---

## 📁 项目结构

```
ClassroomTools/
├── 📄 index.php                  # 主入口（教师/学生双入口）
├── 📁 assets/                    # 静态资源
│   ├── bootstrap/                # Bootstrap 5 离线文件
│   ├── css/style.css             # 自定义样式
│   └── js/app.js                 # 前端工具函数（Toast/AJAX）
├── 📁 db/
│   └── init.php                  # SQLite 数据库初始化（7张表）
├── 📁 includes/
│   ├── config.php                # 全局配置 + 工具函数
│   ├── header.php                # 公共头部（导航栏）
│   └── footer.php                # 公共底部
├── 📁 pages/
│   ├── teacher/
│   │   ├── login.php             # 教师登录
│   │   ├── register.php          # 教师注册（首位管理员）
│   │   ├── logout.php            # 登出
│   │   ├── dashboard.php         # 仪表盘（统计+快捷入口）
│   │   ├── classes.php           # 班级管理（创建/删除）
│   │   ├── class_detail.php      # 班级详情（CSV导入名单）
│   │   ├── speak.php             # 发言均衡热力图
│   │   ├── speak_api.php         # 发言API（+1/-1/随机点名/归档）
│   │   ├── group.php             # 智能分组页面
│   │   └── group_api.php         # 分组API（分层轮询算法）
│   └── student/
│       └── view.php              # 学生只读视图
├── 📁 data/                      # SQLite 数据库文件（自动生成）
├── 📄 DesignGuide.md             # 🎯 完整设计文档
├── 📄 BUILD_PLAN.md              # 🏗️ 分阶段构建计划
├── 📄 AGENTS.md                  # 🤖 智能体工作规则
├── 📄 CONTRIBUTING.md            # 🤝 贡献指南
├── 📄 README.md                  # 📖 本文件
├── 📄 LICENSE                    # ⚖️ MIT 协议
└── 📁 .github/                   # 🔧 Issue/PR 模板
```

> ✅ **当前阶段**：核心功能已完成。所有 7 个 Phase 均已实现，具备完整的教师端+学生端闭环。

---

## 👥 适合谁？

| 你的情况 | 收获 |
|----------|------|
| 🐣 零编程基础 | 第一次用 AI 写出能跑的代码，理解 HTML/CSS/JS/PHP/SQLite 基础 |
| 🐥 写过一点代码 | 模块化思维、简单算法、前后端数据交互 |
| 🐔 想学团队协作 | Git 工作流、Issue/PR/Code Review 完整实践 |
| 🦊 想提升 AI 编程效率 | Prompt 工程技巧、AI 辅助调试策略 |

---

## 🤝 参与方式

1. 🍴 **Fork** 本仓库
2. 🔍 找一个 [`good first issue`](../../issues?q=is%3Aissue+is%3Aopen+label%3A"good+first+issue")
3. 💬 在 Issue 下留言 "我来做这个"
4. 🌿 创建分支 → 用 Copilot 写代码 → 浏览器测试 → 提交 PR
5. 🎉 等待 Review，合并后你就正式成为贡献者了！

详细流程见 **[CONTRIBUTING.md](./CONTRIBUTING.md)**。

---

## 🗺️ Vibe Coding 学习路径

```
阅读 DesignGuide.md       挑选 good first issue      用 Copilot 写代码
      ↓                          ↓                        ↓
  理解要做什么              认领一个小任务           自然语言描述需求
                                                           ↓
      ┌───────────────────────────────────────────────────┐
      │           🔄  迭代循环：写 → 测 → 改 → 再测       │
      └───────────────────────────────────────────────────┘
                           ↓
                    提交 Pull Request
                           ↓
                    Code Review & 合并 🎉
```

---

## ⚠️ 重要声明

- ❌ **请勿用于生产环境**：这是一个学习项目，代码质量、安全性、稳定性均未达到生产标准。
- 🧪 **实验性质**：项目运行在专门的实验虚拟服务器上，由 NGINX + WAF 提供基础防护。
- 🎓 **学习优先**：代码的可读性和简单性优先于性能和安全性。
- 🔓 **安全原则**：密码加密存储、防 SQL 注入、防跨账号数据访问；不过度处理 CSRF、Rate Limiting、并发锁等。

---

## 📄 许可证

MIT License — 详见 [LICENSE](./LICENSE)。

---

> ⭐ 如果这个项目帮到了你，点个 Star 让更多人看到吧！

