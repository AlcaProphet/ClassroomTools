<?php
/**
 * 发言均衡热力图页面
 * 
 * 功能：
 *   - 学生发言按钮（+1/-1）
 *   - 热力图可视化（红→蓝渐变）
 *   - 随机点名
 *   - 清空本周数据
 *   - Tab 切换到历史记录
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../db/init.php';

if (!isTeacherLoggedIn()) { redirect('login.php'); }

$db = getDB();
$teacherId = getCurrentTeacherId();

// 获取班级 ID
$classId = intval($_GET['class_id'] ?? 0);
if ($classId <= 0) { redirect('classes.php'); }

// 验证班级归属
$stmt = $db->prepare("SELECT * FROM classes WHERE id = ? AND teacher_id = ?");
$stmt->execute([$classId, $teacherId]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$class) { redirect('classes.php'); }

// 获取学生列表（用于初始渲染）
$students = $db->prepare("
  SELECT s.*, COALESCE(sr.count, 0) AS speak_count
  FROM students s
  LEFT JOIN speaking_records sr ON sr.student_id = s.id
    AND sr.class_id = s.class_id
    AND sr.week_start = ?
    AND sr.is_archived = 0
  WHERE s.class_id = ?
  ORDER BY s.student_id ASC
");
$weekStart = (new DateTime())->modify('-' . ((int)(new DateTime())->format('N') - 1) . ' days')->format('Y-m-d');
$students->execute([$weekStart, $classId]);
$studentList = $students->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = $class['class_name'] . ' - 发言均衡';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- ========== 顶部信息与导航 ========== -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-1">🗣️ <?php echo htmlspecialchars($class['class_name']); ?></h4>
    <span class="text-muted small">
      🎫 <?php echo htmlspecialchars($class['class_code']); ?> · 
      📅 本周：<?php echo $weekStart; ?>
    </span>
  </div>
  <div>
    <a href="classes.php" class="btn btn-outline-secondary btn-sm">← 返回</a>
  </div>
</div>

<!-- ========== Tab 导航 ========== -->
<ul class="nav nav-tabs mb-4" id="speakTab" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="heatmap-tab" data-bs-toggle="tab" data-bs-target="#heatmap-panel" type="button">
      🔥 发言均衡
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-panel" type="button" onclick="loadHistoryWeeks()">
      📊 历史记录
    </button>
  </li>
</ul>

<!-- ========== Tab 内容 ========== -->
<div class="tab-content" id="speakTabContent">

  <!-- ===== 发言均衡面板 ===== -->
  <div class="tab-pane fade show active" id="heatmap-panel">
    <div class="row g-4">
      <!-- 左栏：学生发言按钮 -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">👨‍🎓 学生名单</h5>
            <span class="badge bg-primary" id="studentCount"><?php echo count($studentList); ?> 人</span>
          </div>
          <div class="card-body" id="studentButtonList">
            <?php if (empty($studentList)): ?>
              <div class="text-center py-4 text-muted">暂无学生，请先导入名单</div>
            <?php else: ?>
              <div class="row g-2">
                <?php foreach ($studentList as $s): ?>
                  <div class="col-6 col-md-4" id="btn-area-<?php echo $s['id']; ?>">
                    <div class="d-grid gap-1">
                      <button
                        class="btn btn-speak btn-outline-primary"
                        onclick="speakIncrement(<?php echo $s['id']; ?>)"
                        id="speak-btn-<?php echo $s['id']; ?>"
                      >
                        <div class="fw-bold"><?php echo htmlspecialchars($s['name']); ?></div>
                        <small class="text-muted"><?php echo htmlspecialchars($s['student_id']); ?></small>
                        <div class="fs-5 fw-bold speak-count" id="count-<?php echo $s['id']; ?>">
                          <?php echo intval($s['speak_count']); ?>
                        </div>
                      </button>
                      <button
                        class="btn btn-sm btn-outline-danger"
                        onclick="speakDecrement(<?php echo $s['id']; ?>)"
                        title="撤销一次"
                        style="font-size: 0.7rem;"
                      >
                        −1 撤销
                      </button>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- 右栏：热力图 -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">🔥 热力图（按发言次数排列）</h5>
          </div>
          <div class="card-body" id="heatmapArea">
            <div class="text-center py-5 text-muted" id="heatmapEmpty">
              加载中...
            </div>
            <div id="heatmapGrid" class="d-flex flex-wrap gap-2"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- 底部统计栏 -->
    <div class="row g-3 mt-3">
      <div class="col-md-3">
        <div class="card text-center p-3">
          <div class="text-muted small">总发言次数</div>
          <div class="fs-3 fw-bold" id="statTotal">-</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center p-3">
          <div class="text-muted small">零发言人数</div>
          <div class="fs-3 fw-bold text-danger" id="statZero">-</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center p-3">
          <div class="text-muted small">发言最多</div>
          <div class="fs-6 fw-bold" id="statMax">-</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center p-3">
          <button class="btn btn-warning btn-lg w-100" onclick="randomPick()">
            🎲 随机点名
          </button>
        </div>
      </div>
    </div>

    <!-- 清空数据按钮 -->
    <div class="text-end mt-3">
      <button class="btn btn-outline-danger btn-sm" onclick="confirmReset()">
        🗑️ 清空本周发言数据
      </button>
    </div>
  </div>

  <!-- ===== 历史记录面板 ===== -->
  <div class="tab-pane fade" id="history-panel">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">📊 历史记录</h5>
        <select class="form-select form-select-sm w-auto" id="historyWeekSelect" onchange="loadHistoryData()">
          <option value="">-- 选择周次 --</option>
        </select>
      </div>
      <div class="card-body">
        <div id="historyContent">
          <div class="text-center py-5 text-muted">请选择要查看的周次</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ========== 随机点名弹窗 ========== -->
<div class="modal fade" id="randomPickModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">🎲 随机点名</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4" id="randomPickResult">
        <div class="spinner-border text-warning" role="status">
          <span class="visually-hidden">抽取中...</span>
        </div>
        <p class="mt-2">正在抽取...</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning" onclick="randomPick()">再抽一次</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
      </div>
    </div>
  </div>
</div>

<!-- ========== JavaScript ========== -->
<script>
// 班级 ID（PHP 写入 JS）
const CLASS_ID = <?php echo $classId; ?>;

// ========== 页面加载时获取数据 ==========
document.addEventListener('DOMContentLoaded', () => {
  loadSpeakData();
});

// ========== 加载发言数据 ==========
async function loadSpeakData() {
  try {
    const resp = await fetch('speak_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=getData&class_id=${CLASS_ID}`
    });
    const data = await resp.json();
    if (!data.success) return;

    // 更新学生按钮上的计数
    data.students.forEach(s => {
      const el = document.getElementById('count-' + s.id);
      if (el) el.textContent = s.speak_count;
    });

    // 更新统计栏
    document.getElementById('statTotal').textContent = data.stats.total;
    document.getElementById('statZero').textContent = data.stats.zero_count;
    document.getElementById('statMax').textContent = data.stats.max_student
      ? `${data.stats.max_student.name} (${data.stats.max_student.speak_count}次)`
      : '-';

    // 更新热力图
    renderHeatmap(data.students);
  } catch (e) {
    console.error('加载发言数据失败:', e);
  }
}

// ========== 渲染热力图 ==========
function renderHeatmap(students) {
  const grid = document.getElementById('heatmapGrid');
  const empty = document.getElementById('heatmapEmpty');
  if (!students.length) {
    grid.innerHTML = '';
    empty.style.display = 'block';
    empty.textContent = '暂无学生数据';
    return;
  }
  empty.style.display = 'none';

  // 按发言次数从低到高排序
  const sorted = [...students].sort((a, b) => a.speak_count - b.speak_count);
  const maxCount = Math.max(...sorted.map(s => s.speak_count), 1);

  // 计算百分位用于颜色映射
  grid.innerHTML = sorted.map(s => {
    const pct = maxCount > 0 ? s.speak_count / maxCount : 0;
    const color = getHeatmapColor(pct);
    const fontSize = 12 + (pct * 12); // 次数越多字体越大
    const isZero = s.speak_count === 0;
    return `
      <div class="heatmap-block ${isZero ? 'heatmap-zero' : ''}"
           style="background-color: ${color}; font-size: ${fontSize}px;"
           title="${s.name} (${s.student_id}): ${s.speak_count}次">
        ${s.name}<br><small>${s.speak_count}</small>
      </div>
    `;
  }).join('');
}

// ========== 热力图颜色映射（红→橙→黄→绿→蓝） ==========
function getHeatmapColor(pct) {
  // pct: 0.0(最少) → 1.0(最多)
  // 红(0°) → 橙(30°) → 黄(60°) → 绿(120°) → 蓝(240°)
  // 映射到 HSL：色相从 0 到 240
  const hue = 240 - (pct * 240); // 0%发言→红(0°), 100%发言→蓝(240°)
  return `hsl(${hue}, 70%, ${60 - pct * 20}%)`;
}

// ========== 发言 +1 ==========
async function speakIncrement(studentId) {
  const btn = document.getElementById('speak-btn-' + studentId);
  // 闪光动画
  btn.classList.add('btn-speak-flash');
  setTimeout(() => btn.classList.remove('btn-speak-flash'), 400);

  try {
    const resp = await fetch('speak_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=increment&class_id=${CLASS_ID}&student_id=${studentId}`
    });
    const data = await resp.json();
    if (data.success) {
      document.getElementById('count-' + studentId).textContent = data.count;
      loadSpeakData(); // 刷新热力图和统计
    }
  } catch (e) {
    console.error('发言计数失败:', e);
    showToast('操作失败，请重试', 'error');
  }
}

// ========== 发言 -1（撤销） ==========
async function speakDecrement(studentId) {
  try {
    const resp = await fetch('speak_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=decrement&class_id=${CLASS_ID}&student_id=${studentId}`
    });
    const data = await resp.json();
    if (data.success) {
      document.getElementById('count-' + studentId).textContent = data.count;
      loadSpeakData();
    }
  } catch (e) {
    console.error('撤销失败:', e);
    showToast('操作失败，请重试', 'error');
  }
}

// ========== 随机点名 ==========
async function randomPick() {
  const modal = new bootstrap.Modal(document.getElementById('randomPickModal'));
  modal.show();

  // 显示加载状态
  document.getElementById('randomPickResult').innerHTML = `
    <div class="spinner-border text-warning" role="status">
      <span class="visually-hidden">抽取中...</span>
    </div>
    <p class="mt-2">正在抽取...</p>
  `;

  try {
    const resp = await fetch('speak_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=randomPick&class_id=${CLASS_ID}`
    });
    const data = await resp.json();
    if (data.success && data.student) {
      const s = data.student;
      const fromZero = s.speak_count === 0;
      document.getElementById('randomPickResult').innerHTML = `
        <div class="fs-1 mb-2">${fromZero ? '🎯' : '🎲'}</div>
        <div class="fs-2 fw-bold">${escapeHtml(s.name)}</div>
        <div class="text-muted">学号：${escapeHtml(s.student_id)}</div>
        <div class="mt-2">
          <span class="badge ${fromZero ? 'bg-success' : 'bg-secondary'}">
            ${fromZero ? '零发言·优先抽取' : '已发言 ' + s.speak_count + ' 次'}
          </span>
        </div>
      `;
    } else {
      document.getElementById('randomPickResult').innerHTML = `
        <div class="text-muted py-3">暂无学生数据</div>
      `;
    }
  } catch (e) {
    document.getElementById('randomPickResult').innerHTML = `
      <div class="text-danger py-3">抽取失败，请重试</div>
    `;
  }
}

// ========== 确认清空 ==========
function confirmReset() {
  const input = prompt('⚠️ 将清空本周发言数据（历史记录保留）。\n请输入「删除」确认：');
  if (input !== '删除') {
    showToast('操作已取消', 'info');
    return;
  }
  fetch('speak_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=reset&class_id=${CLASS_ID}`
  }).then(r => r.json()).then(data => {
    if (data.success) {
      showToast('本周发言数据已清空', 'success');
      loadSpeakData();
    }
  });
}

// ========== 加载历史周列表 ==========
async function loadHistoryWeeks() {
  try {
    const resp = await fetch('speak_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=getHistoryWeeks&class_id=${CLASS_ID}`
    });
    const data = await resp.json();
    const select = document.getElementById('historyWeekSelect');
    select.innerHTML = '<option value="">-- 选择周次 --</option>';
    if (data.success && data.weeks) {
      data.weeks.forEach(w => {
        const endDate = new Date(w.week_start);
        endDate.setDate(endDate.getDate() + 6);
        const label = `${w.week_start} ~ ${endDate.toISOString().split('T')[0]}（共${w.total_count}次）`;
        select.innerHTML += `<option value="${w.week_start}">${label}</option>`;
      });
    }
  } catch (e) {
    console.error('加载历史失败:', e);
  }
}

// ========== 加载某周历史数据 ==========
async function loadHistoryData() {
  const week = document.getElementById('historyWeekSelect').value;
  const container = document.getElementById('historyContent');
  if (!week) {
    container.innerHTML = '<div class="text-center py-5 text-muted">请选择要查看的周次</div>';
    return;
  }

  container.innerHTML = '<div class="text-center py-3"><div class="spinner-border"></div></div>';

  try {
    const resp = await fetch(`speak_api.php?action=getHistoryData&class_id=${CLASS_ID}&week=${week}`);
    const data = await resp.json();
    if (!data.success) { container.innerHTML = '<div class="text-muted">加载失败</div>'; return; }

    const sorted = [...data.students].sort((a, b) => a.speak_count - b.speak_count);
    const maxCount = Math.max(...sorted.map(s => s.speak_count), 1);

    container.innerHTML = `
      <div class="row mb-3">
        <div class="col-4"><strong>总发言：</strong>${data.stats.total} 次</div>
        <div class="col-4"><strong>零发言：</strong>${data.stats.zero_count} 人</div>
        <div class="col-4"><strong>日期：</strong>${data.stats.week_start}</div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        ${sorted.map(s => {
          const pct = maxCount > 0 ? s.speak_count / maxCount : 0;
          const hue = 240 - (pct * 240);
          const color = `hsl(${hue}, 70%, ${60 - pct * 20}%)`;
          return `<div class="heatmap-block" style="background-color:${color}">${escapeHtml(s.name)}<br><small>${s.speak_count}</small></div>`;
        }).join('')}
      </div>
    `;
  } catch (e) {
    container.innerHTML = '<div class="text-danger">加载失败</div>';
  }
}

// ========== 工具函数 ==========
function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>