# 🤝 贡献指南

感谢你对 **课堂管理助手 (ClassroomTools)** 的关注！这是一个面向初学者的友好项目，我们欢迎各种形式的贡献。

---

## 📋 目录

- [行为准则](#行为准则)
- [如何贡献](#如何贡献)
  - [报告 Bug](#报告-bug)
  - [提出新功能建议](#提出新功能建议)
  - [贡献代码](#贡献代码)
  - [改进文档](#改进文档)
- [开发流程](#开发流程)
- [代码风格](#代码风格)
- [Vibe Coding 指南](#vibe-coding-指南)
- [沟通渠道](#沟通渠道)

---

## 行为准则

请遵守我们的 [行为准则](./CODE_OF_CONDUCT.md)，共同维护一个友好、包容的社区环境。

---

## 如何贡献

### 报告 Bug 🐛

如果你发现了 Bug，请通过 [GitHub Issues](../../issues/new?template=bug_report.md) 提交，包含以下信息：

- **描述问题**：发生了什么？预期应该发生什么？
- **复现步骤**：怎样操作可以复现这个问题？
- **环境信息**：操作系统、浏览器版本
- **截图**（可选）：如果方便，附上截图

### 提出新功能建议 💡

有好的想法？请通过 [GitHub Issues](../../issues/new?template=feature_request.md) 提出，包含：

- **功能描述**：你想添加什么功能？
- **使用场景**：这个功能解决什么问题？
- **实现思路**（可选）：你对实现方式有什么想法？

### 贡献代码 💻

#### 对于 Vibe Coding 初学者

如果你是第一次用 AI 辅助编程，别担心！这个项目就是为你设计的。

1. **挑选任务**：查看 [`good first issue`](../../issues?q=is%3Aissue+is%3Aopen+label%3A"good+first+issue") 标签的 Issue
2. **理解需求**：阅读 Issue 描述和相关设计文档
3. **用 AI 写代码**：在 Issue 下留言说 "我来做这个"，然后：
   - 打开 VS Code + GitHub Copilot
   - 将 Issue 描述复制给 Copilot
   - 根据 Copilot 生成的代码进行测试和调整
   - 不懂的地方大胆提问！
4. **提交 PR**：参考下方 [PR 流程](#发起-pull-request)

#### PR 流程

1. **Fork 仓库**：点击右上角 Fork 按钮
2. **克隆到本地**：
   ```bash
   git clone https://github.com/YOUR_USERNAME/ClassroomTools.git
   cd ClassroomTools
   ```
3. **创建分支**：
   ```bash
   git checkout -b feature/你的功能名
   # 分支命名规范：
   # feature/xxx  — 新功能
   # fix/xxx      — 修复 Bug
   # docs/xxx     — 文档改进
   ```
4. **编写代码**
5. **在浏览器中测试**：确认功能正常运行
6. **提交代码**：
   ```bash
   git add .
   git commit -m "feat: 添加了某某功能"
   # Commit 信息规范：
   # feat: 新功能
   # fix: 修复 Bug
   # docs: 文档更新
   # style: 代码格式调整
   # refactor: 代码重构
   ```
7. **推送到你的仓库**：
   ```bash
   git push origin feature/你的功能名
   ```
8. **发起 Pull Request**：在 GitHub 上点击 "Compare & pull request"，填写 PR 描述

### 改进文档 📝

文档的改进同样重要！如果你发现文档有不清楚、有错误的地方，欢迎修改。

---

## 开发流程

### 当前阶段：设计大纲

项目目前处于 **设计大纲阶段**，核心文档为 `DesignGuide.md`。代码实现尚未开始——这正是你参与的好时机！

### 技术选型（已确定）

按照设计文档，项目将采用以下技术：

- **HTML5** — 页面结构
- **CSS3** — 样式与动画
- **Vanilla JavaScript (ES6+)** — 交互逻辑（不使用框架）
- **localStorage** — 数据持久化

### 设计原则
- 🚫 **不使用任何框架或库**：保持零依赖，降低学习门槛
- 💾 **数据必须持久化**：使用 `localStorage`，刷新页面数据不丢失
- 📱 **不要求移动端适配**：教师主要在电脑上使用
- 📄 **单页 HTML 应用**：一个 HTML 文件包含所有功能

---

## 代码风格

- 使用 **2 空格缩进**
- HTML 标签使用语义化标签
- CSS 类名使用 `kebab-case`
- JavaScript 变量/函数使用 `camelCase`
- 添加必要的注释，尤其是复杂逻辑部分
- 函数尽量保持单一职责

---

## Vibe Coding 指南

> 这个项目的一个重要目标是让新手体验 **Vibe Coding** — 用自然语言驱动 AI 编程。

### 什么是 Vibe Coding？

Vibe Coding 是一种编程范式，你不需要记住所有语法细节，而是：
1. 用自然语言描述你想要的功能
2. AI（如 GitHub Copilot）生成代码
3. 你验证、测试、调整
4. 重复直到满意

### 实践建议

| 阶段 | 做法 |
|------|------|
| 📖 **理解需求** | 先读 `DesignGuide.md`，理解要做什么 |
| 🗣️ **描述需求** | 用清晰的自然语言告诉 Copilot 你的意图 |
| 🔍 **审查代码** | 不要盲目接受 AI 代码，先理解再确认 |
| 🧪 **测试验证** | 在浏览器中测试，确保功能正确 |
| 🔄 **迭代优化** | 不满意就继续跟 AI 沟通改进 |
| 📝 **记录心得** | 你学到了什么？踩了什么坑？分享给其他人 |

### 示例 Prompt

当你使用 Copilot 时，可以这样描述需求：

```
// ✅ 好的 Prompt
"请在 speaking.js 中实现一个函数，接收一个学生姓名数组，返回按发言次数降序排列的数组"

// ❌ 不好的 Prompt
"写代码"
```

---

## 沟通渠道

- 📮 **GitHub Issues**：Bug 报告、功能建议
- 💬 **GitHub Discussions**（如已开启）：一般性讨论、问题求助
- 🔀 **Pull Request 评论区**：代码相关讨论

---

> 🌟 **记住**：没有人一开始就是专家。每个大佬都是从第一个 PR 开始的。大胆提交你的代码吧！
