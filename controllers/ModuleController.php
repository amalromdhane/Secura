<?php
require_once 'config/database.php';
require_once 'models/Module.php';

class ModuleController {
    private $moduleModel;

    public function __construct() {
        $pdo = getDBConnection('cyber');
        $this->moduleModel = new Module($pdo);
    }

    public function listModules() {
        $modules = $this->moduleModel->getAll();
        echo json_encode(['success' => true, 'modules' => $modules]);
    }

    public function getModule($id) {
        $module = $this->moduleModel->getById($id);
        if ($module) {
            echo json_encode(['success' => true, 'module' => $module]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Module not found']);
        }
    }

    public function createModule($data) {
        if ($this->moduleModel->create($data)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create module']);
        }
    }

    public function updateModule($id, $data) {
        if ($this->moduleModel->update($id, $data)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update module']);
        }
    }

    public function deleteModule($id) {
        if ($this->moduleModel->delete($id)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete module']);
        }
    }
}
?>