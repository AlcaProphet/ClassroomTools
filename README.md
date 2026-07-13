# 📚 课堂管理助手 (ClassroomTools)

> ⚠️ **此项目仅作为非专业开发人员的 AI 编程学习项目，请勿用于生产力用途。**
> ⚠️ **如想学习请自行 FORK 引用到自己的 Github 中，提交 PULL REQUEST 或 ISSUE 不一定会同意并处理**

---

## 🎯 这是什么？

**课堂管理助手** 是一个面向教师的 Web 辅助工具，集成多项课堂管理功能。它同时也是专为 **Vibe Coding 初学者** 设计的开源协作项目 —— 你不需要有编程经验，用自然语言描述需求，AI 帮你写代码。

### 包含的功能模块

| 模块 | 解决什么问题 |
|------|-------------|
| 🗣️ **发言均衡热力图** | 追踪学生发言次数，用颜色可视化参与度，识别被忽略或过度提问的学生 |
| 👥 **智能分层分组器** | 按性别 + 成绩等级自动均匀分组，告别手动排座位的痛苦 |

> 📖 完整的功能细节、数据模型、交互逻辑见 **[DesignGuide.md](./DesignGuide.md)**。

---

## 🏗️ 技术全景

| 层面 | 技术选择 | 为什么这样选 |
|------|---------|-------------|
| 🖥️ 前端 | HTML + CSS + JavaScript | 零门槛，浏览器就能跑 |
| 🎨 UI | Bootstrap（离线引入） | 不用手写样式，快速出界面 |
| 🔧 后端 | PHP | 语法简单，部署方便，适合新手 |
| 💾 存储 | localStorage + 后端数据库 | 前端临时缓存 + 服务端持久化 |
| 🔐 运行环境 | NGINX + WAF | 已有安全防护，无需在代码层面过度考虑 |

### 设计原则

- 🧒 **极简至上**：代码能跑就行，不求工业级优雅
- 🚫 **不防御性编程**：不过度校验、不处理极端边界
- 🔓 **不纠结安全**：运行环境已有 WAF + NGINX 防护
- 👥 **不过度考虑并发**：面向单个班级使用场景

---

## 🔑 核心设计概念

### 账号体系

每位教师拥有独立账号，数据按账号隔离。

### 多班级 & 房间号

类似 Kahoot! 的房间机制：
- 教师创建一个"班级"，系统生成 **6 位数字房间号**
- 将房间号告诉学生，学生即可加入该班级
- 不同班级之间数据完全隔离

```
教师创建班级 → 生成房间号 482951 → 分享给学生 → 学生凭房间号加入
```

---

## 🧭 你为什么要参与？

这个项目的真正目标 **不是做出一个完美的软件**，而是：

| 序号 | 你要学什么 | 怎么学 |
|------|-----------|--------|
| 1 | **Vibe Coding** | 用自然语言向 Copilot 描述需求，AI 生成代码，你验证调优 |
| 2 | **Git & GitHub** | 从 Fork 到 Pull Request，走一遍开源协作全流程 |
| 3 | **前后端协作** | 理解浏览器 ↔ 服务器是如何配合工作的 |
| 4 | **看懂设计文档** | `DesignGuide.md` 就是你的"产品需求文档" |

> 💡 **Vibe Coding** = 用对话的方式编程。你告诉 AI 要什么，AI 写代码，你负责测试和把关。

---

## 🚀 快速开始

### 你只需要

- 一台电脑（Windows / macOS / Linux 都行）
- [VS Code](https://code.visualstudio.com/)（推荐，带 Copilot 更好）
- 一个 GitHub 账号
- PHP 运行环境（[XAMPP](https://www.apachefriends.org/) 或 [MAMP](https://www.mamp.info/) 一键安装）

### 三步上手

```bash
# 1. 克隆项目
git clone https://github.com/YOUR_USERNAME/ClassroomTools.git
cd ClassroomTools

# 2. 阅读设计文档（从这里开始！）
# 打开 DesignGuide.md

# 3. 挑选任务，开始 Vibe Coding！
# 浏览 Issues 页面，找 good first issue 标签
```

---

## 📁 项目结构（设计阶段）

```
ClassroomTools/
├── DesignGuide.md          # 🎯 完整设计文档 — 所有开发的起点
├── CONTRIBUTING.md         # 🤝 贡献指南 + Vibe Coding 实操教程
├── CODE_OF_CONDUCT.md      # 📜 社区行为准则
├── README.md               # 📖 本文件
├── LICENSE                 # ⚖️ MIT 开源协议
└── .github/                # 🔧 Issue/PR 模板
    ├── ISSUE_TEMPLATE/     #    Bug / 功能 / 文档 / 任务 四种模板
    └── PULL_REQUEST_TEMPLATE.md
```

> 💡 **当前阶段**：设计大纲阶段。`DesignGuide.md` 是蓝图，目录结构将随开发推进逐步生长。

---

## 👥 适合谁？

| 你的情况 | 收获 |
|----------|------|
| 🐣 零编程基础 | 第一次用 AI 写出能跑的代码，理解 HTML/CSS/JS/PHP 基础 |
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

---

## 📄 许可证

MIT License — 详见 [LICENSE](./LICENSE)。

---

> ⭐ 如果这个项目帮到了你，点个 Star 让更多人看到吧！

