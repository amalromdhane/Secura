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
        :root {
            --cyber-primary: #0d6efd;
            --cyber-secondary: #6f42c1;
            --cyber-accent: #00d4ff;
            --cyber-success: #20c997;
            --cyber-warning: #ffc107;
            --cyber-danger: #ff4757;
      --cyber-dark: #141824;
      --cyber-card: #1e2436;
            --cyber-border: rgba(13, 110, 253, 0.15);
            --cyber-gradient: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
            --cyber-glow: 0 0 20px rgba(13, 110, 253, 0.4);
            --text-primary: #f8f9fa;
            --text-secondary: #adb5bd;
        }
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
            background: var(--cyber-dark);
            color: var(--text-primary);
            min-height: 100vh;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(13, 110, 253, 0.03) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(111, 66, 193, 0.03) 0%, transparent 20%);
        }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: linear-gradient(135deg, rgba(30, 36, 54, 0.9) 0%, rgba(26, 31, 46, 0.9) 100%);
            border: 1px solid rgba(0, 212, 255, 0.3);
            border-radius: 12px;
            padding: 20px;
            backdrop-filter: blur(10px);
        }
        .header h1 { color: var(--cyber-accent); }
        .card {
            background: rgba(26, 31, 46, 0.9);
            border: 1px solid rgba(13, 110, 253, 0.2);
            padding: 20px;
            border-radius: 16px;
            backdrop-filter: blur(10px);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .card:hover { border-color: rgba(13, 110, 253, 0.5); }
        .btn {
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            border: none;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary { background: var(--cyber-gradient); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(13, 110, 253, 0.4); }
        .btn-danger { background: rgba(255, 71, 87, 0.2); color: var(--cyber-danger); border: 1px solid rgba(255, 71, 87, 0.3); }
        .btn-danger:hover { background: rgba(255, 71, 87, 0.3); }
        .btn:hover { transform: translateY(-2px); }
        .chapter-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid rgba(13, 110, 253, 0.1);
            transition: background 0.2s;
        }
        .chapter-item:last-child { border-bottom: none; }
        .chapter-item:hover { background: rgba(13, 110, 253, 0.05); }
        .form-group { margin-bottom: 15px; }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }
        input, textarea {
            width: 100%;
            padding: 10px 13px;
            background: rgba(18, 24, 38, 0.7);
            border: 1px solid rgba(13, 110, 253, 0.3);
            border-radius: 8px;
            color: var(--text-primary);
            box-sizing: border-box;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: var(--cyber-accent);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.15);
            background: rgba(18, 24, 38, 0.9);
        }
        textarea { height: 150px; resize: vertical; }
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }
        .modal.open { display: flex; }
        .modal-content {
            background: rgba(26, 31, 46, 0.95);
            border: 1px solid rgba(13, 110, 253, 0.3);
            padding: 20px;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            backdrop-filter: blur(15px);
            box-shadow: 0 20px 60px rgba(13, 110, 253, 0.2);
        }
        .floating-shapes {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        }
        .shape {
            position: absolute;
            background: rgba(0, 212, 255, 0.08);
            border: 1px solid rgba(0, 212, 255, 0.2);
            border-radius: 12px;
            animation: float 8s ease-in-out infinite;
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.1);
        }
        .shape:nth-child(1) { width: 80px; height: 80px; top: 20%; left: 10%; }
        .shape:nth-child(2) { width: 60px; height: 60px; top: 60%; right: 15%; animation-delay: 2s; }
        .shape:nth-child(3) { width: 100px; height: 100px; bottom: 20%; left: 20%; animation-delay: 4s; }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }
    </style>
</head>
  <body>
    <div class="floating-shapes">
      <div class="shape"></div>
      <div class="shape"></div>
      <div class="shape"></div>
    </div>
    <div class="container">
        <div class="header">
            <h1>Cours : <?php echo htmlspecialchars($module['title']); ?></h1>
            <div style="display: flex; gap: 10px;">
                <a href="admin_dashboard.php" class="btn" style="background: rgba(18, 24, 38, 0.7); color: var(--text-secondary); border: 1px solid rgba(255, 255, 255, 0.1);">← Retour Dashboard</a>
                <a href="module.php?id=<?php echo $module_id; ?>" class="btn" style="background: rgba(32, 201, 151, 0.2); color: var(--cyber-success); border: 1px solid rgba(32, 201, 151, 0.3);">👁️ Voir le Module</a>
                <button onclick="openModal()" class="btn btn-primary">➕ Ajouter un chapitre</button>
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
                     <label>URL de l'image (optionnel)</label>
                     <input type="url" id="image_url" name="image_url" placeholder="https://…">
                 </div>

                 <div class="form-group">
                     <label class="checkbox-row">
                         <input type="checkbox" id="quiz_enabled" name="quiz_enabled">
                         <span>Quiz activé pour ce chapitre</span>
                     </label>
                 </div>
                 <div class="form-group">
                     <label>Description courte</label>
                     <textarea id="description" name="description" rows="2" placeholder="Description du chapitre…"></textarea>
                 </div>
                 <div class="form-group">
                     <label>Ordre d'affichage</label>
                     <input type="number" id="order_index" name="order_index" value="0">
                 </div>
                <div style="text-align: right; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeModal()" class="btn" style="background: rgba(18, 24, 38, 0.7); color: var(--text-secondary); border: 1px solid rgba(255, 255, 255, 0.1);">Annuler</button>
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
                                <div style="font-size: 0.8em; color: var(--text-secondary);">
                                    Ordre: ${c.order_index}
                                    ${c.image_url ? ' | Image: ✓' : ''}
                                    ${c.quiz_enabled == 1 ? ' | Quiz: ✓' : ''}
                                </div>
                            </div>
                            <div>
                                <button onclick="editChapter(${JSON.stringify(c).replace(/"/g, '&quot;')})" class="btn" style="background: rgba(255, 193, 7, 0.15); color: var(--cyber-warning); border: 1px solid rgba(255, 193, 7, 0.3);">✏️ Modifier</button>
                                <button onclick="deleteChapter(${c.id})" class="btn btn-danger">🗑️ Supprimer</button>
                            </div>
                        </div>
                    `).join('');
                });
        }

        function openModal() {
            document.getElementById('modalTitle').innerText = "Ajouter un chapitre";
            document.getElementById('chapterForm').reset();
            document.getElementById('chapterId').value = "";
            document.getElementById('order_index').value = "0";
            document.getElementById('quiz_enabled').checked = false;
            document.getElementById('chapterModal').classList.add('open');
        }

        function closeModal() {
            document.getElementById('chapterModal').classList.remove('open');
        }

        function editChapter(c) {
            document.getElementById('modalTitle').innerText = "Modifier le chapitre";
            document.getElementById('chapterId').value = c.id;
            document.getElementById('title').value = c.title;
            document.getElementById('image_url').value = c.image_url || "";
            document.getElementById('quiz_enabled').checked = c.quiz_enabled == 1;
            document.getElementById('description').value = c.description || "";
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
