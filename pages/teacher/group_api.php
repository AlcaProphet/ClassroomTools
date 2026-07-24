<?php
/**
 * 智能分组 API
 * 
 * 提供 JSON 接口：
 *   - getStudents    获取学生列表（含等级）
 *   - updateThresholds 更新成绩阈值并重新计算等级
 *   - startGrouping   执行分组算法
 *   - getLastResult   获取最近一次分组结果
 *   - exportResult    导出分组为文本
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../db/init.php';

if (!isTeacherLoggedIn()) {
  http_response_code(401);
  echo json_encode(['error' => '未登录']);
  exit;
}

$db = getDB();
$teacherId = getCurrentTeacherId();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$classId = intval($_POST['class_id'] ?? $_GET['class_id'] ?? 0);

// 验证班级归属
if ($classId > 0) {
  $stmt = $db->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
  $stmt->execute([$classId, $teacherId]);
  if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['error' => '无权访问']);
    exit;
  }
}

header('Content-Type: application/json; charset=utf-8');

try {
  switch ($action) {

    // ========== 获取学生列表（含等级） ==========
    case 'getStudents':
      $students = $db->prepare("
        SELECT id, student_id, name, gender, score, grade_level
        FROM students WHERE class_id = ?
        ORDER BY student_id ASC
      ");
      $students->execute([$classId]);
      $list = $students->fetchAll(PDO::FETCH_ASSOC);

      // 返回当前阈值
      $cfg = $db->prepare("SELECT * FROM grouping_config WHERE class_id = ?");
      $cfg->execute([$classId]);
      $config = $cfg->fetch(PDO::FETCH_ASSOC) ?: ['group_size' => 4, 'threshold_a' => 85, 'threshold_b' => 75];

      echo json_encode(['success' => true, 'students' => $list, 'config' => $config]);
      break;

    // ========== 更新阈值 ==========
    case 'updateThresholds':
      $groupSize = intval($_POST['group_size'] ?? 4);
      $thresholdA = floatval($_POST['threshold_a'] ?? 85);
      $thresholdB = floatval($_POST['threshold_b'] ?? 75);

      if ($groupSize < 2) $groupSize = 2;
      if ($groupSize > 20) $groupSize = 20;
      if ($thresholdA <= $thresholdB) {
        echo json_encode(['error' => 'A 等级分数线必须大于 B 等级分数线']);
        break;
      }

      // 更新配置
      $db->prepare("INSERT INTO grouping_config (class_id, group_size, threshold_a, threshold_b)
        VALUES (?, ?, ?, ?)
        ON CONFLICT(class_id) DO UPDATE SET group_size=?, threshold_a=?, threshold_b=?, updated_at=CURRENT_TIMESTAMP")
        ->execute([$classId, $groupSize, $thresholdA, $thresholdB, $groupSize, $thresholdA, $thresholdB]);

      // 根据新阈值重算所有学生等级
      $db->prepare("
        UPDATE students SET grade_level = CASE
          WHEN score >= ? THEN 'A'
          WHEN score >= ? THEN 'B'
          ELSE 'C'
        END
        WHERE class_id = ?
      ")->execute([$thresholdA, $thresholdB, $classId]);

      echo json_encode(['success' => true, 'message' => '阈值已更新']);
      break;

    // ========== 执行分组 ==========
    case 'startGrouping':
      // 获取配置
      $cfg = $db->prepare("SELECT * FROM grouping_config WHERE class_id = ?");
      $cfg->execute([$classId]);
      $config = $cfg->fetch(PDO::FETCH_ASSOC);
      $groupSize = intval($config['group_size'] ?? 4);

      // 获取所有学生
      $students = $db->prepare("SELECT * FROM students WHERE class_id = ? ORDER BY RANDOM()");
      $students->execute([$classId]);
      $all = $students->fetchAll(PDO::FETCH_ASSOC);

      if (count($all) === 0) {
        echo json_encode(['error' => '该班级没有学生，请先导入名单']);
        break;
      }

      // ====== 分层轮询分组算法 ======
      // 1. 按性别 + 等级分桶
      $buckets = [
        '男A' => [], '男B' => [], '男C' => [],
        '女A' => [], '女B' => [], '女C' => [],
        '其他' => []  // 性别未知的
      ];

      foreach ($all as $s) {
        $gender = $s['gender'] === '女' ? '女' : ($s['gender'] === '男' ? '男' : '其他');
        $level = $s['grade_level'] ?: 'C';
        if ($gender === '其他') {
          $buckets['其他'][] = $s;
        } else {
          $key = $gender . $level;
          $buckets[$key][] = $s;
        }
      }

      // 合并"其他"到对应等级桶
      foreach ($buckets['其他'] as $s) {
        $level = $s['grade_level'] ?: 'C';
        $buckets['男' . $level][] = $s;
      }

      // 2. 计算组数
      $totalStudents = count($all);
      $numGroups = max(1, (int) ceil($totalStudents / $groupSize));

      // 3. 创建空组
      $groups = [];
      for ($i = 0; $i < $numGroups; $i++) {
        $groups[$i] = [];
      }

      // 4. 从每个桶轮流分配学生到各组
      $bucketOrder = ['男A', '男B', '男C', '女A', '女B', '女C'];
      $groupIdx = 0;
      $maxBucketSize = max(array_map('count', $buckets));

      for ($round = 0; $round < $maxBucketSize; $round++) {
        foreach ($bucketOrder as $bucketKey) {
          if (isset($buckets[$bucketKey][$round])) {
            $groups[$groupIdx % $numGroups][] = $buckets[$bucketKey][$round];
            $groupIdx++;
          }
        }
      }

      // 5. 清除旧分组结果
      $db->beginTransaction();
      try {
        // 删除旧结果
        $oldGroups = $db->prepare("SELECT id FROM groups_result WHERE class_id = ?");
        $oldGroups->execute([$classId]);
        foreach ($oldGroups->fetchAll(PDO::FETCH_ASSOC) as $og) {
          $db->prepare("DELETE FROM group_members WHERE group_id = ?")->execute([$og['id']]);
        }
        $db->prepare("DELETE FROM groups_result WHERE class_id = ?")->execute([$classId]);

        // 写入新结果
        $resultGroups = [];
        foreach ($groups as $idx => $members) {
          if (empty($members)) continue;
          $db->prepare("INSERT INTO groups_result (class_id, group_number) VALUES (?, ?)")->execute([$classId, $idx + 1]);
          $groupId = $db->lastInsertId();

          $groupData = ['group_number' => $idx + 1, 'members' => []];
          foreach ($members as $m) {
            $db->prepare("INSERT INTO group_members (group_id, student_id) VALUES (?, ?)")->execute([$groupId, $m['id']]);
            $groupData['members'][] = [
              'name' => $m['name'],
              'student_id' => $m['student_id'],
              'gender' => $m['gender'],
              'grade_level' => $m['grade_level']
            ];
          }
          $resultGroups[] = $groupData;
        }
        $db->commit();

        echo json_encode([
          'success' => true,
          'groups' => $resultGroups,
          'group_size' => $groupSize,
          'total_students' => $totalStudents,
          'total_groups' => count($resultGroups)
        ]);
      } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['error' => '分组失败：' . $e->getMessage()]);
      }
      break;

    // ========== 获取最近一次分组结果 ==========
    case 'getLastResult':
      $groups = $db->prepare("SELECT * FROM groups_result WHERE class_id = ? ORDER BY group_number ASC");
      $groups->execute([$classId]);
      $groupList = $groups->fetchAll(PDO::FETCH_ASSOC);

      if (empty($groupList)) {
        echo json_encode(['success' => true, 'has_result' => false, 'groups' => []]);
        break;
      }

      $result = [];
      foreach ($groupList as $g) {
        $members = $db->prepare("
          SELECT s.name, s.student_id, s.gender, s.grade_level
          FROM group_members gm
          JOIN students s ON s.id = gm.student_id
          WHERE gm.group_id = ?
        ");
        $members->execute([$g['id']]);
        $result[] = [
          'group_number' => $g['group_number'],
          'members' => $members->fetchAll(PDO::FETCH_ASSOC)
        ];
      }

      $cfg = $db->prepare("SELECT group_size FROM grouping_config WHERE class_id = ?");
      $cfg->execute([$classId]);
      $gs = $cfg->fetchColumn() ?: 4;

      echo json_encode(['success' => true, 'has_result' => true, 'groups' => $result, 'group_size' => intval($gs)]);
      break;

    // ========== 导出分组结果 ==========
    case 'exportResult':
      $groups = $db->prepare("SELECT * FROM groups_result WHERE class_id = ? ORDER BY group_number ASC");
      $groups->execute([$classId]);
      $groupList = $groups->fetchAll(PDO::FETCH_ASSOC);

      $cn = $db->query("SELECT class_name FROM classes WHERE id = $classId")->fetchColumn();

      $text = "班级：{$cn}\n" . str_repeat('=', 40) . "\n\n";
      foreach ($groupList as $g) {
        $members = $db->prepare("
          SELECT s.name, s.student_id, s.gender, s.grade_level
          FROM group_members gm
          JOIN students s ON s.id = gm.student_id
          WHERE gm.group_id = ?
        ");
        $members->execute([$g['id']]);
        $mlist = $members->fetchAll(PDO::FETCH_ASSOC);

        $text .= "第 {$g['group_number']} 组（共 " . count($mlist) . " 人）\n";
        $text .= str_repeat('-', 30) . "\n";
        $maleCount = 0; $femaleCount = 0;
        $levelCount = ['A' => 0, 'B' => 0, 'C' => 0];
        foreach ($mlist as $m) {
          $text .= "  {$m['name']} ({$m['student_id']})  {$m['gender']}  {$m['grade_level']}等\n";
          if ($m['gender'] === '男') $maleCount++; elseif ($m['gender'] === '女') $femaleCount++;
          if (isset($levelCount[$m['grade_level']])) $levelCount[$m['grade_level']]++;
        }
        $text .= "  男{$maleCount}人  女{$femaleCount}人 | A{$levelCount['A']}人 B{$levelCount['B']}人 C{$levelCount['C']}人\n\n";
      }

      header('Content-Type: text/plain; charset=utf-8');
      header('Content-Disposition: attachment; filename="分组结果_' . date('Ymd') . '.txt"');
      echo $text;
      exit;

    default:
      echo json_encode(['error' => '未知操作']);
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => '服务器错误：' . $e->getMessage()]);
}
