<?php
class Module {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll($active = true) {
        $stmt = $this->pdo->prepare("SELECT id, title, description, category, duration, image, page, quiz_page, video_url, active FROM modules WHERE active = ? ORDER BY id DESC");
        $stmt->execute([$active ? 1 : 0]);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM modules WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->pdo->prepare("INSERT INTO modules (title, description, category, duration, image, page, quiz_page, video_url, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['category'],
            $data['duration'],
            $data['image'],
            $data['page'],
            $data['quiz_page'],
            $data['video_url'],
            $data['active'] ?? 1
        ]);
    }

    public function update($id, $data) {
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;
        $stmt = $this->pdo->prepare("UPDATE modules SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM modules WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>