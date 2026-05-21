<?php
namespace App\Controllers;

use App\Core\Database;

class AdminController
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = (new Database())->getConnection('cyber');
    }

    public function dashboard(): void
    {
        $stats = [
            'total' => (int)$this->pdo->query("SELECT COUNT(*) FROM modules")->fetchColumn(),
            'active' => (int)$this->pdo->query("SELECT COUNT(*) FROM modules WHERE active=1")->fetchColumn(),
            'categories' => (int)$this->pdo->query("SELECT COUNT(DISTINCT category) FROM modules")->fetchColumn(),
        ];
        $modules = $this->pdo->query("SELECT * FROM modules ORDER BY id DESC")->fetchAll(\PDO::FETCH_ASSOC);
        require __DIR__ . '/../public/views/admin/dashboard.php';
    }

    public function users(): void
    {
        $users = (new Database())->getConnection('secura')
                     ->query("SELECT id,email,username,role,created_at FROM users ORDER BY id DESC")
                     ->fetchAll(\PDO::FETCH_ASSOC);
        require __DIR__ . '/../public/views/admin/users.php';
    }

    public function course(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $module = $this->pdo->prepare("SELECT * FROM modules WHERE id=?")->execute([$id]);
        $module = $this->pdo->query("SELECT * FROM modules WHERE id=$id")->fetch(\PDO::FETCH_ASSOC);
        if (!$module) { header('Location: admin_dashboard.php'); exit; }
        $chapters = $this->pdo->prepare("SELECT * FROM course_chapters WHERE module_id=? ORDER BY order_index")
                      ->execute([$id]) ? $this->pdo->query("SELECT * FROM course_chapters WHERE module_id=$id ORDER BY order_index")->fetchAll(\PDO::FETCH_ASSOC) : [];
        require __DIR__ . '/../public/views/admin/course_form.php';
    }

    public function quiz(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        require __DIR__ . '/../public/views/admin/quiz_form.php';
    }

    public function saveCourse(): void
    {
        header('Content-Type: application/json');
        // Handled inline in admin_course.php for now
        echo json_encode(['success' => false, 'error' => 'Non implémenté']);
        exit;
    }

    public function saveQuiz(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Non implémenté']);
        exit;
    }

    public function content(): void
    {
        header('Location: manage_content.php');
        exit;
    }
}
