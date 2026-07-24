<?php
/**
 * 发言均衡 API
 * 
 * 提供 JSON 格式的接口，处理发言计数相关的所有操作：
 *   - getData    获取班级所有学生的发言数据
 *   - increment  某学生发言 +1
 *   - decrement  某学生发言 -1（不低于0）
 *   - reset      清空当前班级发言数据
 *   - randomPick 随机点名（优先零发言）
 *   - archive    检查并执行每周归档
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../db/init.php';

// 未登录拦截
if (!isTeacherLoggedIn()) {
  http_response_code(401);
  echo json_encode(['error' => '未登录']);
  exit;
}

$db = getDB();
$teacherId = getCurrentTeacherId();

// 获取请求参数
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$classId = intval($_POST['class_id'] ?? $_GET['class_id'] ?? 0);

// 验证班级归属
if ($classId > 0) {
  $stmt = $db->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
  $stmt->execute([$classId, $teacherId]);
  if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['error' => '无权访问该班级']);
    exit;
  }
}

// 获取当前周的起始日期（周一）
function getCurrentWeekStart(): string {
  $now = new DateTime();
  $dayOfWeek = (int) $now->format('N'); // 1=周一, 7=周日
  $now->modify('-' . ($dayOfWeek - 1) . ' days');
  return $now->format('Y-m-d');
}

// 确保学生的本周发言记录存在
function ensureRecord(PDO $db, int $studentId, int $classId): array {
  $weekStart = getCurrentWeekStart();
  $stmt = $db->prepare("SELECT * FROM speaking_records WHERE student_id = ? AND class_id = ? AND week_start = ?");
  $stmt->execute([$studentId, $classId, $weekStart]);
  $record = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$record) {
    $db->prepare("INSERT INTO speaking_records (student_id, class_id, week_start, count, is_archived) VALUES (?, ?, ?, 0, 0)")->execute([$studentId, $classId, $weekStart]);
    $record = ['id' => $db->lastInsertId(), 'student_id' => $studentId, 'class_id' => $classId, 'week_start' => $weekStart, 'count' => 0, 'is_archived' => 0];
  }
  return $record;
}

// 执行每周归档检查
function checkArchive(PDO $db, int $classId): void {
  $currentWeek = getCurrentWeekStart();
  // 将所有不是本周且未归档的记录标记为已归档
  $db->prepare("UPDATE speaking_records SET is_archived = 1 WHERE class_id = ? AND week_start < ? AND is_archived = 0")->execute([$classId, $currentWeek]);
}

// ========== 路由处理 ==========

header('Content-Type: application/json; charset=utf-8');

try {
  switch ($action) {

    // --- 获取班级发言数据 ---
    case 'getData':
      checkArchive($db, $classId);
      $weekStart = getCurrentWeekStart();

      $students = $db->prepare("
        SELECT s.id, s.student_id, s.name,
          COALESCE(sr.count, 0) AS speak_count,
          sr.id AS record_id
        FROM students s
        LEFT JOIN speaking_records sr ON sr.student_id = s.id
          AND sr.class_id = s.class_id
          AND sr.week_start = ?
          AND sr.is_archived = 0
        WHERE s.class_id = ?
        ORDER BY s.student_id ASC
      ");
      $students->execute([$weekStart, $classId]);
      $list = $students->fetchAll(PDO::FETCH_ASSOC);

      // 计算统计数据
      $total = 0;
      $zeroCount = 0;
      $maxStudent = null;
      foreach ($list as &$s) {
        $s['speak_count'] = intval($s['speak_count']);
        $total += $s['speak_count'];
        if ($s['speak_count'] === 0) $zeroCount++;
        if ($maxStudent === null || $s['speak_count'] > $maxStudent['speak_count']) {
          $maxStudent = $s;
        }
      }

      echo json_encode([
        'success' => true,
        'students' => $list,
        'stats' => [
          'total' => $total,
          'zero_count' => $zeroCount,
          'max_student' => $maxStudent,
          'week_start' => $weekStart
        ]
      ]);
      break;

    // --- 发言 +1 ---
    case 'increment':
      $studentId = intval($_POST['student_id'] ?? 0);
      if ($studentId <= 0) { echo json_encode(['error' => '无效的学生ID']); break; }

      $record = ensureRecord($db, $studentId, $classId);
      $db->prepare("UPDATE speaking_records SET count = count + 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$record['id']]);

      $newCount = $db->query("SELECT count FROM speaking_records WHERE id = " . $record['id'])->fetchColumn();
      echo json_encode(['success' => true, 'student_id' => $studentId, 'count' => intval($newCount)]);
      break;

    // --- 发言 -1（不低于0） ---
    case 'decrement':
      $studentId = intval($_POST['student_id'] ?? 0);
      if ($studentId <= 0) { echo json_encode(['error' => '无效的学生ID']); break; }

      $record = ensureRecord($db, $studentId, $classId);
      // 只有 count > 0 时才减
      if ($record['count'] > 0) {
        $db->prepare("UPDATE speaking_records SET count = count - 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$record['id']]);
      }

      $newCount = $db->query("SELECT count FROM speaking_records WHERE id = " . $record['id'])->fetchColumn();
      echo json_encode(['success' => true, 'student_id' => $studentId, 'count' => intval($newCount)]);
      break;

    // --- 清空当前班级发言数据 ---
    case 'reset':
      $weekStart = getCurrentWeekStart();
      $db->prepare("UPDATE speaking_records SET count = 0 WHERE class_id = ? AND week_start = ? AND is_archived = 0")->execute([$classId, $weekStart]);
      echo json_encode(['success' => true, 'message' => '本周发言数据已清空']);
      break;

    // --- 随机点名 ---
    case 'randomPick':
      $weekStart = getCurrentWeekStart();
      // 优先从零发言学生中抽取
      $zeroStudents = $db->prepare("
        SELECT s.id, s.student_id, s.name, 0 AS speak_count
        FROM students s
        LEFT JOIN speaking_records sr ON sr.student_id = s.id
          AND sr.class_id = s.class_id
          AND sr.week_start = ?
          AND sr.is_archived = 0
        WHERE s.class_id = ?
          AND (sr.count IS NULL OR sr.count = 0)
        ORDER BY RANDOM()
        LIMIT 1
      ");
      $zeroStudents->execute([$weekStart, $classId]);
      $picked = $zeroStudents->fetch(PDO::FETCH_ASSOC);

      if (!$picked) {
        // 没有零发言学生，从全部学生中随机抽
        $allStudents = $db->prepare("
          SELECT s.id, s.student_id, s.name
          FROM students s
          WHERE s.class_id = ?
          ORDER BY RANDOM()
          LIMIT 1
        ");
        $allStudents->execute([$classId]);
        $picked = $allStudents->fetch(PDO::FETCH_ASSOC);
        if ($picked) {
          $c = $db->prepare("SELECT count FROM speaking_records WHERE student_id = ? AND class_id = ? AND week_start = ?")->fetchColumn();
          $picked['speak_count'] = intval($c ?? 0);
        }
      }

      if ($picked) {
        echo json_encode(['success' => true, 'student' => $picked]);
      } else {
        echo json_encode(['success' => false, 'message' => '该班级没有学生']);
      }
      break;

    // --- 获取历史周列表 ---
    case 'getHistoryWeeks':
      $weeks = $db->prepare("
        SELECT DISTINCT week_start,
          SUM(count) AS total_count
        FROM speaking_records
        WHERE class_id = ? AND is_archived = 1
        GROUP BY week_start
        ORDER BY week_start DESC
      ");
      $weeks->execute([$classId]);
      echo json_encode(['success' => true, 'weeks' => $weeks->fetchAll(PDO::FETCH_ASSOC)]);
      break;

    // --- 获取某周历史数据 ---
    case 'getHistoryData':
      $weekStart = $_GET['week'] ?? '';
      if ($weekStart === '') { echo json_encode(['error' => '缺少周参数']); break; }

      $students = $db->prepare("
        SELECT s.id, s.student_id, s.name,
          COALESCE(sr.count, 0) AS speak_count
        FROM students s
        LEFT JOIN speaking_records sr ON sr.student_id = s.id
          AND sr.class_id = s.class_id
          AND sr.week_start = ?
        WHERE s.class_id = ?
        ORDER BY s.student_id ASC
      ");
      $students->execute([$weekStart, $classId]);
      $list = $students->fetchAll(PDO::FETCH_ASSOC);

      $total = 0;
      $zeroCount = 0;
      foreach ($list as &$s) {
        $s['speak_count'] = intval($s['speak_count']);
        $total += $s['speak_count'];
        if ($s['speak_count'] === 0) $zeroCount++;
      }

      echo json_encode([
        'success' => true,
        'students' => $list,
        'stats' => ['total' => $total, 'zero_count' => $zeroCount, 'week_start' => $weekStart]
      ]);
      break;

    default:
      echo json_encode(['error' => '未知操作']);
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => '服务器错误：' . $e->getMessage()]);
}
