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

$page_title   = 'Gestion du Cours';
$page_heading = '<i class="fas fa-book-open"></i> Cours : <span>' . htmlspecialchars($module['title']) . '</span>';
$active_menu  = 'modules';
$extra_css    = ['css/admin-course-page.css'];

require_once __DIR__ . '/includes/admin/layout_start.php';
?>

  <div class="action-bar">
    <a href="admin_dashboard.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Dashboard</a>
    <a href="module.php?id=<?php echo $module_id; ?>" class="btn btn-view"><i class="fas fa-eye"></i> Voir le module</a>
    <a href="admin_quiz.php?id=<?php echo $module_id; ?>" class="btn btn-quiz"><i class="fas fa-circle-question"></i> Quiz</a>
    <button type="button" id="btnOpenChapterModal" class="btn btn-primary btn-toolbar-end">
      <i class="fas fa-plus"></i> Ajouter un chapitre
    </button>
  </div>

  <div class="chapters-card">
    <div class="chapters-card-header">
      <h3><i class="fas fa-list-ul"></i> Chapitres du cours</h3>
      <span class="chapter-count" id="chapterCount">0 chapitre</span>
    </div>
    <div id="chapterList">
      <div class="empty-state">
        <i class="fas fa-book-open"></i>
        <p>Chargement des chapitres…</p>
      </div>
    </div>
  </div>

<?php require_once __DIR__ . '/includes/admin/layout_end.php'; ?>

<div id="chapterModal" class="modal">
  <div class="modal-content modal-content-wide">
    <h2 id="modalTitle">Ajouter un chapitre</h2>

    <form id="chapterForm">
      <input type="hidden" id="chapterId" name="id">
      <input type="hidden" name="module_id" value="<?php echo $module_id; ?>">

      <div class="form-row">
        <div class="form-group">
          <label for="title">Titre du chapitre</label>
          <input type="text" id="title" name="title" placeholder="Ex : Leçon 1 — Introduction" required>
        </div>
        <div class="form-group">
          <label for="order_index">Ordre d'affichage</label>
          <input type="number" id="order_index" name="order_index" value="0" min="0">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="video_url">URL vidéo (optionnel)</label>
          <input type="url" id="video_url" name="video_url" placeholder="https://www.youtube-nocookie.com/embed/…">
        </div>
        <div class="form-group">
          <label for="image_url">URL image (optionnel)</label>
          <input type="url" id="image_url" name="image_url" placeholder="https://…">
        </div>
      </div>

      <div class="form-group">
        <label>Contenu du chapitre</label>
        <div class="editor-wrapper">
          <div class="editor-toolbar">
            <div class="tb-group">
              <span class="tb-label">Texte</span>
              <button type="button" class="tb-btn" data-cmd="bold"><i class="fas fa-bold"></i></button>
              <button type="button" class="tb-btn" data-cmd="italic"><i class="fas fa-italic"></i></button>
              <button type="button" class="tb-btn" data-cmd="underline"><i class="fas fa-underline"></i></button>
              <button type="button" class="tb-btn" data-heading="h4"><i class="fas fa-heading"></i> H4</button>
              <button type="button" class="tb-btn" data-heading="h6"><i class="fas fa-heading"></i> H6</button>
            </div>
            <div class="tb-sep"></div>
            <div class="tb-group">
              <span class="tb-label">Liste</span>
              <button type="button" class="tb-btn" data-cmd="insertUnorderedList"><i class="fas fa-list-ul"></i></button>
              <button type="button" class="tb-btn" data-cmd="insertOrderedList"><i class="fas fa-list-ol"></i></button>
            </div>
            <div class="tb-sep"></div>
            <div class="tb-group">
              <span class="tb-label">Blocs</span>
              <button type="button" class="tb-btn" data-block="card"><i class="fas fa-square"></i> Carte</button>
              <button type="button" class="tb-btn" data-block="grid2"><i class="fas fa-columns"></i> 2 col.</button>
              <button type="button" class="tb-btn" data-block="grid3"><i class="fas fa-table-columns"></i> 3 col.</button>
              <button type="button" class="tb-btn" data-block="steps"><i class="fas fa-shoe-prints"></i> Étapes</button>
            </div>
            <div class="tb-sep"></div>
            <div class="tb-group">
              <span class="tb-label">Alertes</span>
              <button type="button" class="tb-btn" data-block="tip"><i class="fas fa-lightbulb"></i> Conseil</button>
              <button type="button" class="tb-btn" data-block="warning"><i class="fas fa-triangle-exclamation"></i> Attention</button>
              <button type="button" class="tb-btn" data-block="danger"><i class="fas fa-circle-xmark"></i> Danger</button>
              <button type="button" class="tb-btn" data-block="info"><i class="fas fa-circle-info"></i> Info</button>
            </div>
            <div class="tb-sep"></div>
            <div class="tb-group">
              <span class="tb-label">Tableaux</span>
              <button type="button" class="tb-btn" data-block="table2"><i class="fas fa-table"></i> 2 col.</button>
              <button type="button" class="tb-btn" data-block="table3"><i class="fas fa-table"></i> 3 col.</button>
              <button type="button" class="tb-btn" data-block="table4"><i class="fas fa-table"></i> 4 col.</button>
            </div>
            <div class="tb-sep"></div>
            <div class="tb-group">
              <span class="tb-label">Spécial</span>
              <button type="button" class="tb-btn" data-block="321"><i class="fas fa-3"></i> 3-2-1</button>
            </div>
            <div class="view-toggle">
              <button type="button" class="view-btn active" id="btnPreview" data-view="preview"><i class="fas fa-eye"></i> Aperçu</button>
              <button type="button" class="view-btn" id="btnCode" data-view="code"><i class="fas fa-code"></i> HTML</button>
            </div>
          </div>
          <div id="editorContent" class="editor-content" contenteditable="true" spellcheck="false">
            <p>Commencez à écrire le contenu ou insérez un bloc depuis la barre d'outils…</p>
          </div>
          <textarea id="editorCode" class="editor-code" spellcheck="false"></textarea>
          <input type="hidden" id="contentHidden" name="content">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="description">Description courte</label>
          <textarea id="description" name="description" rows="3" placeholder="Résumé affiché dans la liste des chapitres…"></textarea>
        </div>
        <div class="form-group form-group-checkboxes">
          <label class="checkbox-row">
            <input type="checkbox" id="quiz_enabled" name="quiz_enabled" value="1">
            <span>Quiz activé pour ce chapitre</span>
          </label>
          <label class="checkbox-row">
            <input type="checkbox" id="chapter_published" name="chapter_published" value="1" checked>
            <span>Chapitre publié</span>
          </label>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" data-modal-close="chapterModal">Annuler</button>
        <button type="submit" class="btn-submit"><i class="fas fa-floppy-disk"></i> Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<script>window.SECURA_MODULE_ID = <?php echo (int)$module_id; ?>;</script>
<?php
$extra_js = ['js/admin-course.js'];
require_once __DIR__ . '/includes/admin/layout_footer.php';
?>
