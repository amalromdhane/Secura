<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

try {
    require_once 'includes/config.php';
    require_once 'includes/request.php';
    $pdo = getDBConnection('cyber');
    $action = $_GET['action'] ?? '';

    switch ($action) {

    case 'list_chapters':
        $module_id = (int)$_GET['module_id'];
        $stmt = $pdo->prepare("SELECT * FROM course_chapters WHERE module_id = ? ORDER BY order_index ASC");
        $stmt->execute([$module_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'add_chapter':
        // Auto-créer les colonnes manquantes
        $cols = $pdo->query("SHOW COLUMNS FROM course_chapters")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('video_url',    $cols)) $pdo->exec("ALTER TABLE course_chapters ADD COLUMN video_url VARCHAR(500) DEFAULT NULL");
        if (!in_array('image_url',    $cols)) $pdo->exec("ALTER TABLE course_chapters ADD COLUMN image_url VARCHAR(500) DEFAULT NULL");
        if (!in_array('description',  $cols)) $pdo->exec("ALTER TABLE course_chapters ADD COLUMN description TEXT DEFAULT NULL");
        if (!in_array('quiz_enabled', $cols)) $pdo->exec("ALTER TABLE course_chapters ADD COLUMN quiz_enabled TINYINT(1) DEFAULT 0");
        if (!in_array('order_index',  $cols)) $pdo->exec("ALTER TABLE course_chapters ADD COLUMN order_index INT DEFAULT 0");

        $stmt = $pdo->prepare("
            INSERT INTO course_chapters (module_id, title, content, description, video_url, image_url, quiz_enabled, order_index)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $success = $stmt->execute([
            (int)($_POST['module_id']    ?? 0),
            trim($_POST['title']         ?? ''),
            $_POST['content']            ?? '',   // ← HTML riche de l'éditeur
            trim($_POST['description']   ?? ''),  // ← description courte
            trim($_POST['video_url']     ?? ''),
            trim($_POST['image_url']     ?? ''),
            (int)($_POST['quiz_enabled'] ?? 0),
            (int)($_POST['order_index']  ?? 0),
        ]);
        echo json_encode(['success' => $success, 'id' => $pdo->lastInsertId()]);
        break;

    case 'update_chapter':
        $stmt = $pdo->prepare("
            UPDATE course_chapters
            SET title=?, content=?, description=?, video_url=?, image_url=?, quiz_enabled=?, order_index=?
            WHERE id=?
        ");
        $success = $stmt->execute([
            trim($_POST['title']         ?? ''),
            $_POST['content']            ?? '',   // ← HTML riche
            trim($_POST['description']   ?? ''),
            trim($_POST['video_url']     ?? ''),
            trim($_POST['image_url']     ?? ''),
            (int)($_POST['quiz_enabled'] ?? 0),
            (int)($_POST['order_index']  ?? 0),
            (int)($_POST['id']           ?? 0),
        ]);
        echo json_encode(['success' => $success]);
        break;

    case 'delete_chapter':
        $in = getRequestData();
        $stmt = $pdo->prepare("DELETE FROM course_chapters WHERE id=?");
        echo json_encode(['success' => $stmt->execute([(int)($in['id'] ?? 0)])]);
        break;

    case 'list_quiz':
        $module_id = (int)$_GET['module_id'];
        $stmt = $pdo->prepare("
            SELECT q.*, (SELECT COUNT(*) FROM quiz_options o WHERE o.question_id = q.id) as options_count
            FROM quiz_questions q WHERE module_id = ? ORDER BY order_index ASC
        ");
        $stmt->execute([$module_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($questions as &$q) {
            $os = $pdo->prepare("SELECT * FROM quiz_options WHERE question_id = ?");
            $os->execute([$q['id']]);
            $q['options'] = $os->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode($questions);
        break;

    case 'save_quiz_question':
        $in = getRequestData();
        $pdo->beginTransaction();
        try {
            $q_id = $in['id'] ?? null;
            $options = $in['options'] ?? [];
            if (is_string($options)) {
                $options = json_decode($options, true) ?: [];
            }
            if ($q_id) {
                $pdo->prepare("UPDATE quiz_questions SET question_text=?, order_index=? WHERE id=?")
                    ->execute([$in['question_text'], $in['order_index'], $q_id]);
                $pdo->prepare("DELETE FROM quiz_options WHERE question_id=?")->execute([$q_id]);
            } else {
                $pdo->prepare("INSERT INTO quiz_questions (module_id, question_text, order_index) VALUES (?,?,?)")
                    ->execute([$in['module_id'], $in['question_text'], $in['order_index']]);
                $q_id = $pdo->lastInsertId();
            }
            foreach ($options as $opt) {
                $pdo->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES (?,?,?)")
                    ->execute([$q_id, $opt['text'], $opt['is_correct'] ? 1 : 0]);
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'delete_quiz_question':
        $in = getRequestData();
        $stmt = $pdo->prepare("DELETE FROM quiz_questions WHERE id=?");
        echo json_encode(['success' => $stmt->execute([(int)($in['id'] ?? 0)])]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue: ' . $action]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>