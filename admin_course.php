<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php'); exit();
}
require_once 'config.php';

$module_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($module_id <= 0) { header('Location: admin_dashboard.php'); exit(); }

try {
    $pdo = getDBConnection('cyber');
    $stmt = $pdo->prepare("SELECT title FROM modules WHERE id = ?");
    $stmt->execute([$module_id]);
    $module = $stmt->fetch();
    if (!$module) { header('Location: admin_dashboard.php'); exit(); }
} catch (PDOException $e) { die($e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion du Cours - <?php echo htmlspecialchars($module['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .btn { padding: 10px 15px; border-radius: 5px; cursor: pointer; text-decoration: none; border: none; font-weight: bold; }
        .btn-primary { background: #1a73e8; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .chapter-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #eee; }
        .chapter-item:last-child { border-bottom: none; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        textarea { height: 150px; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal.open { display: flex; }
        .modal-content { background: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 600px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Cours : <?php echo htmlspecialchars($module['title']); ?></h1>
            <div>
                <a href="admin_dashboard.php" class="btn" style="background: #666; color: white;">Retour Dashboard</a>
                <a href="module.php?id=<?php echo $module_id; ?>" class="btn" style="background: #28a745; color: white;">Voir le Module</a>
                <button onclick="openModal()" class="btn btn-primary">Ajouter un chapitre</button>
            </div>
        </div>

        <div class="card" id="chapterList">
            <!-- Liste chargée en JS -->
        </div>
    </div>

    <div id="chapterModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Ajouter un chapitre</h2>
            <form id="chapterForm">
                <input type="hidden" id="chapterId" name="id">
                <input type="hidden" name="module_id" value="<?php echo $module_id; ?>">
                <div class="form-group">
                    <label>Titre du chapitre</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label>URL Vidéo (YouTube Embed)</label>
                    <input type="text" id="video_url" name="video_url" placeholder="https://www.youtube.com/embed/...">
                </div>
                <div class="form-group">
                    <label>Contenu (HTML autorisé)</label>
                    <textarea id="content" name="content"></textarea>
                </div>
                <div class="form-group">
                    <label>Ordre d'affichage</label>
                    <input type="number" id="order_index" name="order_index" value="0">
                </div>
                <div style="text-align: right;">
                    <button type="button" onclick="closeModal()" class="btn" style="background: #ccc;">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function loadChapters() {
            fetch('manage_content.php?action=list_chapters&module_id=<?php echo $module_id; ?>')
                .then(r => r.json())
                .then(data => {
                    const list = document.getElementById('chapterList');
                    if (data.length === 0) {
                        list.innerHTML = '<p style="text-align:center; color:#666;">Aucun chapitre. Cliquez sur "Ajouter" pour commencer.</p>';
                        return;
                    }
                    list.innerHTML = data.map(c => `
                        <div class="chapter-item">
                            <div>
                                <strong>${c.title}</strong>
                                <div style="font-size: 0.8em; color: #666;">Ordre: ${c.order_index}</div>
                            </div>
                            <div>
                                <button onclick="editChapter(${JSON.stringify(c).replace(/"/g, '&quot;')})" class="btn" style="background:#ffc107;">Modifier</button>
                                <button onclick="deleteChapter(${c.id})" class="btn btn-danger">Supprimer</button>
                            </div>
                        </div>
                    `).join('');
                });
        }

        function openModal() {
            document.getElementById('modalTitle').innerText = "Ajouter un chapitre";
            document.getElementById('chapterForm').reset();
            document.getElementById('chapterId').value = "";
            document.getElementById('chapterModal').classList.add('open');
        }

        function closeModal() {
            document.getElementById('chapterModal').classList.remove('open');
        }

        function editChapter(c) {
            document.getElementById('modalTitle').innerText = "Modifier le chapitre";
            document.getElementById('chapterId').value = c.id;
            document.getElementById('title').value = c.title;
            document.getElementById('video_url').value = c.video_url || "";
            document.getElementById('content').value = c.content || "";
            document.getElementById('order_index').value = c.order_index;
            document.getElementById('chapterModal').classList.add('open');
        }

        function deleteChapter(id) {
            if (confirm('Supprimer ce chapitre ?')) {
                fetch('manage_content.php?action=delete_chapter', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}`
                }).then(() => loadChapters());
            }
        }

        document.getElementById('chapterForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const action = document.getElementById('chapterId').value ? 'update_chapter' : 'add_chapter';
            
            fetch('manage_content.php?action=' + action, {
                method: 'POST',
                body: new URLSearchParams(formData)
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    closeModal();
                    loadChapters();
                } else {
                    alert(data.error);
                }
            });
        };

        loadChapters();
    </script>
</body>
</html>
