/**
 * 课堂管理助手 - 前端交互逻辑
 * 使用 ES6+ 原生语法，不依赖复杂框架
 */

// ---------- DOM 就绪后执行 ----------
document.addEventListener('DOMContentLoaded', () => {
  initToastContainer();
  initGlobalListeners();
});

// ========== Toast 消息系统 ==========

/**
 * 初始化 Toast 容器（页面中只存在一个）
 */
function initToastContainer() {
  if (!document.querySelector('.toast-container')) {
    const container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
}

/**
 * 显示一条 Toast 消息
 * @param {string} message - 消息文本
 * @param {'success'|'error'|'info'} type - 消息类型
 * @param {number} duration - 显示时长（毫秒），默认 3000
 */
function showToast(message, type = 'info', duration = 3000) {
  const container = document.querySelector('.toast-container');
  if (!container) return;

  const toast = document.createElement('div');
  toast.className = `custom-toast toast-${type}`;
  toast.textContent = message;
  container.appendChild(toast);

  // 自动消失
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// ========== 全局事件监听 ==========

function initGlobalListeners() {
  // 为所有带有 data-confirm 属性的按钮绑定确认弹窗
  document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const message = btn.dataset.confirm || '确认执行此操作？';
      // 要求用户输入指定文字确认（如"删除"）
      const requiredText = btn.dataset.confirmText || '';
      if (requiredText) {
        const input = prompt(`${message}\n请输入「${requiredText}」以确认：`);
        if (input !== requiredText) {
          e.preventDefault();
          showToast('操作已取消', 'info');
          return;
        }
      } else {
        if (!confirm(message)) {
          e.preventDefault();
          return;
        }
      }
    });
  });
}

// ========== 工具函数 ==========

/**
 * 发送 AJAX POST 请求
 * @param {string} url - 请求地址
 * @param {object} data - 请求数据
 * @returns {Promise<object>} 响应 JSON
 */
async function apiPost(url, data = {}) {
  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await response.json();
    return result;
  } catch (error) {
    console.error('API 请求失败:', error);
    showToast('网络请求失败，请稍后重试', 'error');
    throw error;
  }
}

/**
 * 发送 AJAX GET 请求
 * @param {string} url - 请求地址
 * @returns {Promise<object>} 响应 JSON
 */
async function apiGet(url) {
  try {
    const response = await fetch(url);
    const result = await response.json();
    return result;
  } catch (error) {
    console.error('API 请求失败:', error);
    showToast('网络请求失败，请稍后重试', 'error');
    throw error;
  }
}

/**
 * 从 URL 中获取查询参数
 * @param {string} name - 参数名
 * @returns {string|null}
 */
function getQueryParam(name) {
  const params = new URLSearchParams(window.location.search);
  return params.get(name);
}

// ========== 导出到全局（方便在其他页面中使用） ==========
window.showToast = showToast;
window.apiPost = apiPost;
window.apiGet = apiGet;
window.getQueryParam = getQueryParam;
