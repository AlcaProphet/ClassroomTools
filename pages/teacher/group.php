<?php
/**
 * 智能分组页面
 * 
 * 功能：
 *   - 学生名单表格（含等级标签）
 *   - 分组参数设置（人数/阈值）
 *   - 执行分组算法
 *   - 分组结果卡片展示
 *   - 导出分组结果
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../db/init.php';

if (!isTeacherLoggedIn()) { redirect('login.php'); }

$db = getDB();
$teacherId = getCurrentTeacherId();

$classId = intval($_GET['class_id'] ?? 0);
if ($classId <= 0) { redirect('classes.php'); }

$stmt = $db->prepare("SELECT * FROM classes WHERE id = ? AND teacher_id = ?");
$stmt->execute([$classId, $teacherId]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$class) { redirect('classes.php'); }

// 获取分组配置
$cfg = $db->prepare("SELECT * FROM grouping_config WHERE class_id = ?");
$cfg->execute([$classId]);
$config = $cfg->fetch(PDO::FETCH_ASSOC) ?: ['group_size' => 4, 'threshold_a' => 85, 'threshold_b' => 75];

// 获取学生列表（初始渲染用）
$students = $db->prepare("SELECT * FROM students WHERE class_id = ? ORDER BY student_id ASC");
$students->execute([$classId]);
$studentList = $students->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = $class['class_name'] . ' - 智能分组';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- ========== 页面标题 ========== -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-1">👥 <?php echo htmlspecialchars($class['class_name']); ?> · 智能分组</h4>
    <span class="text-muted small">🎫 <?php echo htmlspecialchars($class['class_code']); ?></span>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-success btn-sm" onclick="exportGroups()" id="btnExport" disabled>
      📥 导出分组
    </button>
    <a href="classes.php" class="btn btn-outline-secondary btn-sm">← 返回</a>
  </div>
</div>

<div class="row g-4">

  <!-- ========== 左栏：学生名单 + 阈值设置 ========== -->
  <div class="col-lg-12">
    
    <!-- 学生表格 -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">📋 学生名单</h5>
        <div class="d-flex gap-2 align-items-center">
          <span class="badge bg-primary" id="studentCount"><?php echo count($studentList); ?> 人</span>
          <a href="class_detail.php?id=<?php echo $classId; ?>" class="btn btn-outline-secondary btn-sm">导入名单</a>
        </div>
      </div>
      <div class="card-body p-0">
        <?php if (empty($studentList)): ?>
          <div class="text-center py-5 text-muted">
            <p class="mb-2">还没有导入学生名单</p>
            <a href="class_detail.php?id=<?php echo $classId; ?>" class="btn btn-primary btn-sm">去导入</a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>学号</th><th>姓名</th><th>性别</th><th>分数</th><th>等级</th>
                </tr>
              </thead>
              <tbody id="studentTable">
                <?php foreach ($studentList as $s): ?>
                <tr>
                  <td><code><?php echo htmlspecialchars($s['student_id']); ?></code></td>
                  <td><?php echo htmlspecialchars($s['name']); ?></td>
                  <td><?php echo $s['gender'] ?: '-'; ?></td>
                  <td><?php echo $s['score'] > 0 ? $s['score'] : '-'; ?></td>
                  <td>
                    <?php if ($s['grade_level']): ?>
                      <span class="badge badge-level-<?php echo strtolower($s['grade_level']); ?>">
                        <?php echo htmlspecialchars($s['grade_level']); ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- 分组参数 -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">⚙️ 分组参数</h5>
      </div>
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">每组人数</label>
            <input type="number" class="form-control" id="groupSize" value="<?php echo $config['group_size']; ?>" min="2" max="20">
          </div>
          <div class="col-md-3">
            <label class="form-label">A 等级分数线 (≥)</label>
            <input type="number" class="form-control" id="thresholdA" value="<?php echo $config['threshold_a']; ?>" step="1" min="0" max="100">
          </div>
          <div class="col-md-3">
            <label class="form-label">B 等级分数线 (≥)</label>
            <input type="number" class="form-control" id="thresholdB" value="<?php echo $config['threshold_b']; ?>" step="1" min="0" max="100">
          </div>
          <div class="col-md-3">
            <button class="btn btn-outline-primary w-100" onclick="updateThresholds()">
              💾 保存设置
            </button>
          </div>
        </div>
        <div class="text-muted small mt-2">
          规则：A ≥ 设定值，B ≥ 设定值，其余为 C。调整后自动重算等级。
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ========== 分组按钮 ========== -->
<div class="text-center my-4">
  <button class="btn btn-primary btn-lg px-5" onclick="doGrouping()" id="btnGroup">
    🎯 开始分组
  </button>
  <div id="groupingStatus" class="text-muted mt-2" style="display:none;">
    <div class="spinner-border spinner-border-sm me-1"></div> 正在计算最优分组...
  </div>
</div>

<!-- ========== 分组结果 ========== -->
<div id="groupResult" style="display:none;">
  <h5 class="mb-3">📊 分组结果（共 <span id="groupCount">0</span> 组，每组约 <span id="groupSizeLabel">-</span> 人）</h5>
  <div class="row g-3" id="groupCards"></div>
</div>

<!-- ========== JavaScript ========== -->
<script>
const CLASS_ID = <?php echo $classId; ?>;
let lastResult = null;

// 页面加载时检查是否有历史结果
document.addEventListener('DOMContentLoaded', () => {
  loadLastResult();
});

// ========== 更新阈值 ==========
async function updateThresholds() {
  const groupSize = document.getElementById('groupSize').value;
  const thresholdA = document.getElementById('thresholdA').value;
  const thresholdB = document.getElementById('thresholdB').value;

  if (parseFloat(thresholdA) <= parseFloat(thresholdB)) {
    showToast('A 等级分数线必须大于 B 等级', 'error');
    return;
  }

  try {
    const resp = await fetch('group_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=updateThresholds&class_id=${CLASS_ID}&group_size=${groupSize}&threshold_a=${thresholdA}&threshold_b=${thresholdB}`
    });
    const data = await resp.json();
    if (data.success) {
      showToast('设置已保存，等级已更新', 'success');
      // 刷新学生表格
      refreshStudentTable();
    } else {
      showToast(data.error || '保存失败', 'error');
    }
  } catch (e) {
    showToast('保存失败', 'error');
  }
}

// ========== 刷新学生表格 ==========
async function refreshStudentTable() {
  try {
    const resp = await fetch('group_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=getStudents&class_id=${CLASS_ID}`
    });
    const data = await resp.json();
    if (!data.success) return;

    document.getElementById('studentCount').textContent = data.students.length + ' 人';

    const tbody = document.getElementById('studentTable');
    tbody.innerHTML = data.students.map(s => `
      <tr>
        <td><code>${escapeHtml(s.student_id)}</code></td>
        <td>${escapeHtml(s.name)}</td>
        <td>${s.gender || '-'}</td>
        <td>${s.score > 0 ? s.score : '-'}</td>
        <td>${s.grade_level ? `<span class="badge badge-level-${s.grade_level.toLowerCase()}">${s.grade_level}</span>` : '<span class="text-muted">-</span>'}</td>
      </tr>
    `).join('');
  } catch (e) {
    console.error('刷新失败', e);
  }
}

// ========== 执行分组 ==========
async function doGrouping() {
  document.getElementById('groupingStatus').style.display = 'block';
  document.getElementById('btnGroup').disabled = true;

  try {
    const resp = await fetch('group_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=startGrouping&class_id=${CLASS_ID}`
    });
    const data = await resp.json();
    document.getElementById('groupingStatus').style.display = 'none';
    document.getElementById('btnGroup').disabled = false;

    if (data.success) {
      lastResult = data;
      renderGroups(data);
      document.getElementById('btnExport').disabled = false;
      showToast(`分组完成！共 ${data.total_groups} 组`, 'success');
    } else {
      showToast(data.error || '分组失败', 'error');
    }
  } catch (e) {
    document.getElementById('groupingStatus').style.display = 'none';
    document.getElementById('btnGroup').disabled = false;
    showToast('分组失败，请重试', 'error');
  }
}

// ========== 渲染分组结果 ==========
function renderGroups(data) {
  document.getElementById('groupResult').style.display = 'block';
  document.getElementById('groupCount').textContent = data.total_groups;
  document.getElementById('groupSizeLabel').textContent = data.group_size;

  const container = document.getElementById('groupCards');
  container.innerHTML = data.groups.map(g => {
    const males = g.members.filter(m => m.gender === '男').length;
    const females = g.members.filter(m => m.gender === '女').length;
    const aCount = g.members.filter(m => m.grade_level === 'A').length;
    const bCount = g.members.filter(m => m.grade_level === 'B').length;
    const cCount = g.members.filter(m => m.grade_level === 'C').length;

    return `
      <div class="col-md-6 col-lg-4">
        <div class="card group-card h-100">
          <div class="card-body">
            <h6 class="card-title mb-3">🏷️ 第 ${g.group_number} 组（${g.members.length} 人）</h6>
            <div class="mb-2">
              ${g.members.map(m => `
                <span class="badge bg-light text-dark me-1 mb-1">
                  ${escapeHtml(m.name)}
                  <small class="text-muted">${m.gender||'?'}</small>
                  <span class="badge badge-level-${(m.grade_level||'c').toLowerCase()} ms-1">${m.grade_level||'C'}</span>
                </span>
              `).join('')}
            </div>
            <hr class="my-2">
            <small class="text-muted">
              👦男${males} 👧女${females} | A${aCount} B${bCount} C${cCount}
            </small>
          </div>
        </div>
      </div>
    `;
  }).join('');

  // 滚动到结果区
  document.getElementById('groupResult').scrollIntoView({ behavior: 'smooth' });
}

// ========== 加载最近一次分组结果 ==========
async function loadLastResult() {
  try {
    const resp = await fetch('group_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=getLastResult&class_id=${CLASS_ID}`
    });
    const data = await resp.json();
    if (data.success && data.has_result) {
      lastResult = data;
      document.getElementById('btnExport').disabled = false;
      document.getElementById('groupSizeLabel').textContent = data.group_size;
      document.getElementById('groupCount').textContent = data.groups.length;
      // 渲染历史结果
      document.getElementById('groupResult').style.display = 'block';
      const container = document.getElementById('groupCards');
      container.innerHTML = data.groups.map(g => {
        const males = g.members.filter(m => m.gender === '男').length;
        const females = g.members.filter(m => m.gender === '女').length;
        const aCount = g.members.filter(m => m.grade_level === 'A').length;
        const bCount = g.members.filter(m => m.grade_level === 'B').length;
        const cCount = g.members.filter(m => m.grade_level === 'C').length;
        return `
          <div class="col-md-6 col-lg-4">
            <div class="card group-card h-100">
              <div class="card-body">
                <h6 class="card-title mb-3">🏷️ 第 ${g.group_number} 组（${g.members.length} 人）</h6>
                <div class="mb-2">
                  ${g.members.map(m => `
                    <span class="badge bg-light text-dark me-1 mb-1">
                      ${escapeHtml(m.name)}
                      <small>${m.gender||'?'}</small>
                      <span class="badge badge-level-${(m.grade_level||'c').toLowerCase()} ms-1">${m.grade_level||'C'}</span>
                    </span>
                  `).join('')}
                </div>
                <hr class="my-2">
                <small class="text-muted">👦男${males} 👧女${females} | A${aCount} B${bCount} C${cCount}</small>
              </div>
            </div>
          </div>
        `;
      }).join('');
    }
  } catch (e) {
    console.error('加载历史结果失败', e);
  }
}

// ========== 导出分组 ==========
function exportGroups() {
  window.open(`group_api.php?action=exportResult&class_id=${CLASS_ID}`, '_blank');
}

// ========== 工具函数 ==========
function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>