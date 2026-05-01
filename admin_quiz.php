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
        body { font-family: sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .btn { padding: 10px 15px; border-radius: 5px; cursor: pointer; text-decoration: none; border: none; font-weight: bold; }
        .btn-primary { background: #1a73e8; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .question-item { border-bottom: 1px solid #eee; padding: 15px 0; }
        .option-item { margin-left: 20px; font-size: 0.9em; color: #555; }
        .correct { color: #28a745; font-weight: bold; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; overflow-y: auto; }
        .modal.open { display: flex; }
        .modal-content { background: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 600px; margin: 20px; }
        .option-input-row { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Quiz : <?php echo htmlspecialchars($module['title']); ?></h1>
            <div>
                <a href="admin_dashboard.php" class="btn" style="background: #666; color: white;">Retour</a>
                <button onclick="openModal()" class="btn btn-primary">Ajouter une question</button>
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
                    <button type="button" onclick="addOptionInput()" class="btn" style="background:#eee; font-size:0.8em; margin-top:10px;">+ Ajouter une option</button>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" onclick="closeModal()" class="btn" style="background: #ccc;">Annuler</button>
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
                <button type="button" onclick="this.parentElement.remove()" class="btn btn-danger" style="padding:5px 10px;">×</button>
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
