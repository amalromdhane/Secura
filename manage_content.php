<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

require_once 'config.php';
$pdo = getDBConnection('cyber');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list_chapters':
        $module_id = (int)$_GET['module_id'];
        $stmt = $pdo->prepare("SELECT * FROM course_chapters WHERE module_id = ? ORDER BY order_index ASC");
        $stmt->execute([$module_id]);
        echo json_encode($stmt->fetchAll());
        break;

    case 'add_chapter':
        $stmt = $pdo->prepare("INSERT INTO course_chapters (module_id, title, content, video_url, order_index) VALUES (?, ?, ?, ?, ?)");
        $success = $stmt->execute([
            $_POST['module_id'],
            $_POST['title'],
            $_POST['content'],
            $_POST['video_url'],
            $_POST['order_index']
        ]);
        echo json_encode(['success' => $success]);
        break;

    case 'update_chapter':
        $stmt = $pdo->prepare("UPDATE course_chapters SET title=?, content=?, video_url=?, order_index=? WHERE id=?");
        $success = $stmt->execute([
            $_POST['title'],
            $_POST['content'],
            $_POST['video_url'],
            $_POST['order_index'],
            $_POST['id']
        ]);
        echo json_encode(['success' => $success]);
        break;

    case 'delete_chapter':
        $stmt = $pdo->prepare("DELETE FROM course_chapters WHERE id=?");
        $success = $stmt->execute([$_POST['id']]);
        echo json_encode(['success' => $success]);
        break;

    // Gestion Quiz
    case 'list_quiz':
        $module_id = (int)$_GET['module_id'];
        $stmt = $pdo->prepare("SELECT q.*, (SELECT COUNT(*) FROM quiz_options o WHERE o.question_id = q.id) as options_count FROM quiz_questions q WHERE module_id = ? ORDER BY order_index ASC");
        $stmt->execute([$module_id]);
        $questions = $stmt->fetchAll();
        
        foreach ($questions as &$q) {
            $stmt = $pdo->prepare("SELECT * FROM quiz_options WHERE question_id = ?");
            $stmt->execute([$q['id']]);
            $q['options'] = $stmt->fetchAll();
        }
        echo json_encode($questions);
        break;

    case 'save_quiz_question':
        $pdo->beginTransaction();
        try {
            $q_id = $_POST['id'] ?? null;
            if ($q_id) {
                $stmt = $pdo->prepare("UPDATE quiz_questions SET question_text=?, order_index=? WHERE id=?");
                $stmt->execute([$_POST['question_text'], $_POST['order_index'], $q_id]);
                // Supprimer les anciennes options pour les recréer
                $pdo->prepare("DELETE FROM quiz_options WHERE question_id=?")->execute([$q_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO quiz_questions (module_id, question_text, order_index) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['module_id'], $_POST['question_text'], $_POST['order_index']]);
                $q_id = $pdo->lastInsertId();
            }

            // Insérer les nouvelles options
            $options = json_decode($_POST['options'], true);
            foreach ($options as $opt) {
                $stmt = $pdo->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                $stmt->execute([$q_id, $opt['text'], $opt['is_correct'] ? 1 : 0]);
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'delete_quiz_question':
        $stmt = $pdo->prepare("DELETE FROM quiz_questions WHERE id=?");
        $success = $stmt->execute([$_POST['id']]);
        echo json_encode(['success' => $success]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue']);
}
?>
