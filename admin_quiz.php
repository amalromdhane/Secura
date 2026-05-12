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
    <title>Gestion du Quiz - <?php echo htmlspecialchars($module['title']); ?></title>
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
        .question-item {
            border-bottom: 1px solid rgba(13, 110, 253, 0.1);
            padding: 15px 0;
            transition: background 0.2s;
        }
        .question-item:hover { background: rgba(13, 110, 253, 0.05); }
        .option-item { margin-left: 20px; font-size: 0.9em; color: var(--text-secondary); }
        .correct { color: var(--cyber-success); font-weight: bold; }
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
        input {
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
        input:focus {
            outline: none;
            border-color: var(--cyber-accent);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.15);
            background: rgba(18, 24, 38, 0.9);
        }
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            align-items: center;
            justify-content: center;
            overflow-y: auto;
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
            margin: 20px;
            backdrop-filter: blur(15px);
            box-shadow: 0 20px 60px rgba(13, 110, 253, 0.2);
        }
        .option-input-row { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; }
        .option-input-row input[type="text"] { flex: 1; }
        .option-input-row input[type="radio"] { width: auto; accent-color: var(--cyber-primary); }
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
            <h1>Quiz : <?php echo htmlspecialchars($module['title']); ?></h1>
            <div style="display: flex; gap: 10px;">
                <a href="admin_dashboard.php" class="btn" style="background: rgba(18, 24, 38, 0.7); color: var(--text-secondary); border: 1px solid rgba(255, 255, 255, 0.1);">← Retour Dashboard</a>
                <button onclick="openModal()" class="btn btn-primary">➕ Ajouter une question</button>
            </div>
        </div>

        <div class="card" id="questionList">
            <!-- Liste chargée en JS -->
        </div>
    </div>

    <div id="questionModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Ajouter une question</h2>
            <form id="questionForm">
                <input type="hidden" id="questionId">
                <div class="form-group">
                    <label>Texte de la question</label>
                    <input type="text" id="question_text" required>
                </div>
                <div class="form-group">
                    <label>Ordre</label>
                    <input type="number" id="order_index" value="0">
                </div>
                <div class="form-group">
                    <label>Options (Cochez la bonne réponse)</label>
                    <div id="optionsContainer">
                        <!-- Options générées ici -->
                    </div>
                    <button type="button" onclick="addOptionInput()" class="btn" style="background: rgba(13, 110, 253, 0.1); color: var(--cyber-primary); border: 1px solid rgba(13, 110, 253, 0.3); font-size:0.8em; margin-top:10px;">➕ Ajouter une option</button>
                </div>
                <div style="text-align: right; margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeModal()" class="btn" style="background: rgba(18, 24, 38, 0.7); color: var(--text-secondary); border: 1px solid rgba(255, 255, 255, 0.1);">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function loadQuestions() {
            fetch('manage_content.php?action=list_quiz&module_id=<?php echo $module_id; ?>')
                .then(r => r.json())
                .then(data => {
                    const list = document.getElementById('questionList');
                    if (data.length === 0) {
                        list.innerHTML = '<p style="text-align:center; color:#666;">Aucune question. Cliquez sur "Ajouter" pour commencer.</p>';
                        return;
                    }
                    list.innerHTML = data.map(q => `
                        <div class="question-item">
                            <div style="display:flex; justify-content:space-between;">
                                <strong>${q.question_text}</strong>
                                <div>
                                    <button onclick='editQuestion(${JSON.stringify(q).replace(/'/g, "&apos;")})' class="btn" style="background:#ffc107; padding:5px 10px;">Modifier</button>
                                    <button onclick="deleteQuestion(${q.id})" class="btn btn-danger" style="padding:5px 10px;">Supprimer</button>
                                </div>
                            </div>
                            <div style="margin-top:10px;">
                                ${q.options.map(o => `<div class="option-item ${o.is_correct == 1 ? 'correct' : ''}">${o.is_correct == 1 ? '✓' : '○'} ${o.option_text}</div>`).join('')}
                            </div>
                        </div>
                    `).join('');
                });
        }

        function addOptionInput(text = '', isCorrect = false) {
            const container = document.getElementById('optionsContainer');
            const div = document.createElement('div');
            div.className = 'option-input-row';
            div.innerHTML = `
                <input type="radio" name="is_correct" ${isCorrect ? 'checked' : ''} style="width:auto;">
                <input type="text" class="option-text" value="${text}" placeholder="Texte de l'option" required>
                <button type="button" onclick="this.parentElement.remove()" class="btn btn-danger" style="padding:5px 10px; font-size: 1.2rem;">×</button>
            `;
            container.appendChild(div);
        }

        function openModal() {
            document.getElementById('modalTitle').innerText = "Ajouter une question";
            document.getElementById('questionId').value = "";
            document.getElementById('question_text').value = "";
            document.getElementById('order_index').value = "0";
            document.getElementById('optionsContainer').innerHTML = "";
            addOptionInput(); addOptionInput(); // Commencer avec 2 options
            document.getElementById('questionModal').classList.add('open');
        }

        function closeModal() {
            document.getElementById('questionModal').classList.remove('open');
        }

        function editQuestion(q) {
            document.getElementById('modalTitle').innerText = "Modifier la question";
            document.getElementById('questionId').value = q.id;
            document.getElementById('question_text').value = q.question_text;
            document.getElementById('order_index').value = q.order_index;
            const container = document.getElementById('optionsContainer');
            container.innerHTML = "";
            q.options.forEach(o => addOptionInput(o.option_text, o.is_correct == 1));
            document.getElementById('questionModal').classList.add('open');
        }

        function deleteQuestion(id) {
            if (confirm('Supprimer cette question ?')) {
                fetch('manage_content.php?action=delete_quiz_question', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}`
                }).then(() => loadQuestions());
            }
        }

        document.getElementById('questionForm').onsubmit = function(e) {
            e.preventDefault();
            const options = [];
            const rows = document.querySelectorAll('.option-input-row');
            rows.forEach(row => {
                options.push({
                    text: row.querySelector('.option-text').value,
                    is_correct: row.querySelector('input[type="radio"]').checked
                });
            });

            const body = new URLSearchParams();
            body.append('id', document.getElementById('questionId').value);
            body.append('module_id', '<?php echo $module_id; ?>');
            body.append('question_text', document.getElementById('question_text').value);
            body.append('order_index', document.getElementById('order_index').value);
            body.append('options', JSON.stringify(options));

            fetch('manage_content.php?action=save_quiz_question', {
                method: 'POST',
                body: body
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    closeModal();
                    loadQuestions();
                } else {
                    alert(data.error);
                }
            });
        };

        loadQuestions();
    </script>
</body>
</html>
