<?php
require_once 'config/database.php';
require_once __DIR__ . '/../Models/Module.php';

class ModuleController {
    private $moduleModel;

    public function __construct() {
        $pdo = getDBConnection('cyber');
        $this->moduleModel = new Module($pdo);
    }

    /**
     * Public-facing: list active modules (all_modules.php)
     */
    public function listPublic(): void
    {
        $modules = $this->moduleModel->getAll(active: true);
        require __DIR__ . '/../public/views/modules/public_list.php';
    }

    /**
     * Public-facing: show single module page (module.php)
     */
    public function show(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { header('Location: index.php'); exit; }
        $module = $this->moduleModel->getById($id);
        if (!$module)   { header('Location: index.php'); exit; }

        $chaptersStmt = (new Database())->getConnection('cyber')
            ->prepare("SELECT * FROM course_chapters WHERE module_id=? ORDER BY order_index ASC");
        $chaptersStmt->execute([$id]);
        $chapters = $chaptersStmt->fetchAll();

        require __DIR__ . '/../public/views/modules/detail.php';
    }

    /* ── JSON API ─────────────────────────────────────────────────── */

    public function apiList(): void
    {
        $this->getAll();
    }

    public function apiCreate(): void
    {
        $this->createModule($_POST);
    }

    public function apiUpdate(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $this->updateModule($id, $_POST);
    }

    public function apiDelete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $this->deleteModule($id);
    }

    /* ── Existing JSON helpers ─────────────────────────────────────── */

    public function getAll() {
        $modules = $this->moduleModel->getAll();
        echo json_encode(['success' => true, 'modules' => $modules]);
        exit;
    }

    public function getModule($id) {
        $module = $this->moduleModel->getById($id);
        if ($module) {
            echo json_encode(['success' => true, 'module' => $module]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Module not found']);
        }
        exit;
    }

    public function createModule($data) {
        if ($this->moduleModel->create($data)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create module']);
        }
        exit;
    }

    public function updateModule($id, $data) {
        if ($this->moduleModel->update($id, $data)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update module']);
        }
        exit;
    }

    public function deleteModule($id) {
        if ($this->moduleModel->delete($id)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete module']);
        }
        exit;
    }
}
?>
