# Bootstrap 离线文件

本项目使用 Bootstrap 5 离线引入，不依赖 CDN。

## 需要的文件

请将以下 Bootstrap 5 文件放入本目录：

1. `bootstrap.min.css` — Bootstrap 核心样式文件
2. `bootstrap.bundle.min.js` — Bootstrap JS（含 Popper）

## 获取方式

### 方式一：官网下载
1. 访问 [Bootstrap 下载页](https://getbootstrap.com/docs/5.3/getting-started/download/)
2. 下载 "Compiled CSS and JS" 压缩包
3. 解压后，将 `css/bootstrap.min.css` 和 `js/bootstrap.bundle.min.js` 放入本目录

### 方式二：npm 安装后复制
```bash
npm install bootstrap@5
cp node_modules/bootstrap/dist/css/bootstrap.min.css assets/bootstrap/
cp node_modules/bootstrap/dist/js/bootstrap.bundle.min.js assets/bootstrap/
```

### 方式三：curl 直接下载
```bash
curl -o assets/bootstrap/bootstrap.min.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css
curl -o assets/bootstrap/bootstrap.bundle.min.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js
```

> ⚠️ 缺少这两个文件时，页面布局会不正常。请优先放置它们。
