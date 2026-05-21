<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/admin/auth.php';

$module_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($module_id <= 0) {
    header('Location: admin_dashboard.php');
    exit();
}

try {
    $pdo = getDBConnection('cyber');
    $stmt = $pdo->prepare('SELECT title FROM modules WHERE id = ?');
    $stmt->execute([$module_id]);
    $module = $stmt->fetch();
    if (!$module) {
        header('Location: admin_dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    die($e->getMessage());
}

$page_title   = 'Gestion du Quiz';
$page_heading = 'Quiz : ' . htmlspecialchars($module['title']);
$active_menu  = 'modules';
$extra_css    = ['css/admin-quiz-extra.css'];

require_once __DIR__ . '/includes/admin/layout_start.php';
?>

  <div class="dash-section">
    <h2>❓ Gestion du Quiz</h2>

    <div class="admin-toolbar">
      <a href="admin_dashboard.php" class="btn btn-back-link">← Retour Dashboard</a>
      <a href="admin_course.php?id=<?php echo $module_id; ?>" class="btn btn-course-link">📚 Cours</a>
      <button type="button" id="btnOpenQuizModal" class="btn btn-primary btn-toolbar-end">➕ Ajouter une question</button>
    </div>

    <div id="questionList"></div>
  </div>

<?php require_once __DIR__ . '/includes/admin/layout_end.php'; ?>

<div id="questionModal" class="modal modal-quiz">
  <div class="modal-content">
    <h2 id="modalTitle" class="modal-quiz-title">Ajouter une question</h2>
    <form id="questionForm">
      <input type="hidden" id="questionId">
      <div class="form-group">
        <label for="question_text">Texte de la question</label>
        <input type="text" id="question_text" required>
      </div>
      <div class="form-group">
        <label for="order_index">Ordre</label>
        <input type="number" id="order_index" value="0">
      </div>
      <div class="form-group">
        <label>Options (cochez la bonne réponse)</label>
        <div id="optionsContainer"></div>
        <button type="button" id="btnAddQuizOption" class="btn btn-primary btn-add-option">
          <span>➕</span> Ajouter une option
        </button>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" data-modal-close="questionModal">Annuler</button>
        <button type="submit" class="btn-submit">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<script>window.SECURA_MODULE_ID = <?php echo (int)$module_id; ?>;</script>
<?php
$extra_js = ['js/admin-quiz.js'];
require_once __DIR__ . '/includes/admin/layout_footer.php';
?>
