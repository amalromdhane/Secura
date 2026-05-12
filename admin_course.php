<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php'); exit();
}
require_once 'includes/config.php';

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
          --cyber-dark: #f8fafc;
          --cyber-card: #ffffff;
          --cyber-border: rgba(13, 110, 253, 0.2);
          --cyber-gradient: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
          --text-primary: #1e293b;
          --text-secondary: #64748b;
          --text-muted: #94a3b8;
          --neon-cyan: #00d4ff;
        }
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body {
          font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
          background: #f1f5f9;
          color: var(--text-primary);
          min-height: 100vh;
          display: flex;
        }

        /* ── Sidebar ── */
        .sidebar {
          width:250px; background:#fff;
          border-right:1px solid #e2e8f0;
          height:100vh; position:fixed; left:0; top:0; padding:24px 16px;
          box-shadow:2px 0 12px rgba(0,0,0,.06); z-index:100;
        }
        .sidebar-header {
          text-align:center; margin-bottom:28px; padding-bottom:20px;
          border-bottom:1px solid #e2e8f0;
        }
        .sidebar-header h2 { color:var(--text-primary); font-size:18px; font-weight:700; }
        .sidebar-menu { list-style:none; }
        .sidebar-menu li { margin-bottom:6px; }
        .sidebar-menu a {
          display:flex; align-items:center; padding:10px 14px; color:var(--text-secondary);
          text-decoration:none; border-radius:10px; transition:all .2s; font-weight:500; font-size:14px;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
          background:#eff6ff; color:#0d6efd;
        }
        .sidebar-menu a i { margin-right:10px; width:18px; text-align:center; font-size:15px; }

        /* ── Layout ── */
        .main-content { margin-left:250px; flex:1; padding:28px; min-height:100vh; }
        .container { max-width:900px; margin:0 auto; }

        /* ── Navbar ── */
        .topbar {
          display:flex; align-items:center; justify-content:space-between;
          background:#fff; border-radius:14px; padding:16px 24px;
          box-shadow:0 1px 4px rgba(0,0,0,.07); margin-bottom:28px;
          border:1px solid #e2e8f0;
        }
        .topbar-title { font-size:16px; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:10px; }
        .topbar-title span { color:var(--cyber-primary); }
        .user-info { display:flex; align-items:center; gap:12px; position:relative; }
        .user-avatar { width:38px; height:38px; border-radius:50%; cursor:pointer; border:2px solid var(--cyber-accent); object-fit:cover; }
        .user-dropdown {
          position:absolute; top:calc(100% + 8px); right:0;
          background:#fff; border:1px solid #e2e8f0; border-radius:10px;
          min-width:180px; box-shadow:0 8px 24px rgba(0,0,0,.1);
          opacity:0; visibility:hidden; transform:translateY(-6px);
          transition:all .25s; z-index:9999;
        }
        .user-dropdown.open { opacity:1; visibility:visible; transform:translateY(0); }
        .user-dropdown-item {
          display:flex; align-items:center; gap:10px; padding:11px 16px;
          color:var(--text-secondary); text-decoration:none; font-size:14px;
          border-bottom:1px solid #f1f5f9; transition:all .15s;
        }
        .user-dropdown-item:last-child { border-bottom:none; }
        .user-dropdown-item:hover { background:#f8faff; color:var(--cyber-primary); }
        .user-dropdown-item i { width:16px; }

        /* ── Action bar ── */
        .action-bar {
          display:flex; align-items:center; gap:10px; margin-bottom:22px; flex-wrap:wrap;
        }
        .btn {
          padding:9px 16px; border-radius:10px; cursor:pointer;
          text-decoration:none; border:none; font-weight:600; font-size:13px;
          transition:all .2s; display:inline-flex; align-items:center; gap:7px;
          white-space:nowrap;
        }
        .btn-back   { background:#f1f5f9; color:var(--text-secondary); border:1px solid #e2e8f0; }
        .btn-back:hover { background:#e2e8f0; }
        .btn-view   { background:#eff6ff; color:#0d6efd; border:1px solid #bfdbfe; }
        .btn-view:hover { background:#dbeafe; }
        .btn-quiz   { background:#f5f3ff; color:#7c3aed; border:1px solid #ddd6fe; }
        .btn-quiz:hover { background:#ede9fe; }
        .btn-primary { background:var(--cyber-gradient); color:#fff; border:none; }
        .btn-primary:hover { transform:translateY(-1px); box-shadow:0 4px 14px rgba(13,110,253,.35); }

        /* ── Chapter list card ── */
        .chapters-card {
          background:#fff; border-radius:16px;
          border:1px solid #e2e8f0;
          box-shadow:0 1px 4px rgba(0,0,0,.06);
          overflow:hidden;
        }
        .chapters-card-header {
          padding:18px 24px; border-bottom:1px solid #f1f5f9;
          display:flex; align-items:center; justify-content:space-between;
        }
        .chapters-card-header h3 {
          font-size:15px; font-weight:700; color:var(--text-primary);
          display:flex; align-items:center; gap:8px;
        }
        .chapters-card-header h3 i { color:var(--cyber-primary); }
        .chapter-count {
          background:#eff6ff; color:#0d6efd; font-size:12px; font-weight:700;
          padding:3px 10px; border-radius:20px;
        }

        /* ── Chapter item — TITRE SEULEMENT ── */
        .chapter-item {
          display:flex; align-items:center; justify-content:space-between;
          padding:16px 24px;
          border-bottom:1px solid #f8fafc;
          transition:background .15s;
        }
        .chapter-item:last-child { border-bottom:none; }
        .chapter-item:hover { background:#f8faff; }

        .chapter-left { display:flex; align-items:center; gap:14px; }

        .chapter-index {
          width:32px; height:32px; border-radius:50%; flex-shrink:0;
          background:linear-gradient(135deg,#eff6ff,#dbeafe);
          border:1px solid #bfdbfe;
          display:flex; align-items:center; justify-content:center;
          font-size:13px; font-weight:700; color:#0d6efd;
        }

        .chapter-title {
          font-size:14px; font-weight:600; color:var(--text-primary);
        }

        /* petites puces indicateurs optionnels (discrets) */
        .chapter-badges { display:flex; gap:5px; margin-top:4px; }
        .badge {
          font-size:10px; font-weight:600; padding:2px 7px; border-radius:10px;
          display:inline-flex; align-items:center; gap:3px;
        }
        .badge-video { background:#fef3c7; color:#92400e; }
        .badge-img   { background:#f0fdf4; color:#166534; }
        .badge-quiz  { background:#f5f3ff; color:#5b21b6; }

        .chapter-actions { display:flex; gap:8px; flex-shrink:0; }
        .btn-edit {
          padding:7px 13px; border-radius:8px; border:1px solid #e2e8f0;
          background:#fff; color:var(--text-secondary); font-size:12px; font-weight:600;
          cursor:pointer; display:flex; align-items:center; gap:5px; transition:all .15s;
        }
        .btn-edit:hover { background:#fffbeb; color:#b45309; border-color:#fde68a; }
        .btn-del {
          padding:7px 13px; border-radius:8px; border:1px solid #fee2e2;
          background:#fff; color:#dc2626; font-size:12px; font-weight:600;
          cursor:pointer; display:flex; align-items:center; gap:5px; transition:all .15s;
        }
        .btn-del:hover { background:#fef2f2; }

        /* état vide */
        .empty-state {
          padding:48px 24px; text-align:center; color:var(--text-muted);
        }
        .empty-state i { font-size:40px; margin-bottom:12px; display:block; color:#cbd5e1; }
        .empty-state p { font-size:14px; }

        /* ── Modal ── */
        .modal {
          display:none; position:fixed; inset:0;
          background:rgba(15,23,42,.5); align-items:center;
          justify-content:center; backdrop-filter:blur(6px); z-index:1000;
        }
        .modal.open { display:flex; }
        .modal-content {
          background:#fff; border-radius:20px;
          max-width:900px; width:97%; max-height:95vh; overflow-y:auto;
          box-shadow:0 24px 64px rgba(0,0,0,.18); padding:32px;
        }
        .modal-content h2 { font-size:18px; font-weight:700; margin-bottom:24px; color:var(--text-primary); }
        .form-group { margin-bottom:18px; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        label {
          display:flex; align-items:center; gap:8px; margin-bottom:7px;
          font-weight:600; color:var(--text-primary);
          text-transform:uppercase; letter-spacing:.4px; font-size:12px;
        }
        input[type="text"],input[type="url"],input[type="number"],textarea,select {
          width:100%; padding:11px 14px;
          background:#fff; border:1.5px solid #e2e8f0; border-radius:10px;
          color:var(--text-primary); font-size:14px;
          font-family:'Segoe UI',sans-serif; transition:all .2s;
        }
        input:focus,textarea:focus,select:focus {
          outline:none; border-color:var(--cyber-accent);
          box-shadow:0 0 0 3px rgba(0,212,255,.12); background:#fff;
        }
        .checkbox-row {
          display:flex; align-items:center; gap:10px;
          text-transform:none; letter-spacing:0; font-weight:500; font-size:14px; cursor:pointer;
        }
        .checkbox-row input[type="checkbox"] { width:17px; height:17px; accent-color:#0d6efd; cursor:pointer; }
        .modal-footer {
          display:flex; gap:12px; justify-content:flex-end;
          margin-top:28px; padding-top:20px; border-top:1.5px solid #f1f5f9;
        }
        .btn-cancel {
          padding:10px 22px; background:#f1f5f9; color:var(--text-secondary);
          border:1.5px solid #e2e8f0; border-radius:10px;
          cursor:pointer; font-size:14px; font-weight:600; transition:all .2s;
        }
        .btn-cancel:hover { background:#e2e8f0; }
        .btn-submit {
          padding:10px 26px; background:var(--cyber-gradient); color:#fff;
          border:none; border-radius:10px; cursor:pointer; font-size:14px; font-weight:600;
          transition:all .2s; display:flex; align-items:center; gap:7px;
        }
        .btn-submit:hover { transform:translateY(-1px); box-shadow:0 4px 14px rgba(13,110,253,.35); }

        /* ── Rich Editor ── */
        .editor-wrapper { border-radius:10px; overflow:hidden; border:1.5px solid #e2e8f0; }
        .editor-toolbar {
          background:#f8fafc; border-bottom:1px solid #e2e8f0;
          padding:10px 12px; display:flex; flex-wrap:wrap; gap:5px; align-items:center;
        }
        .tb-group { display:flex; gap:3px; align-items:center; }
        .tb-sep { width:1px; background:#e2e8f0; height:20px; margin:0 4px; }
        .tb-label { font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:.5px; font-weight:600; white-space:nowrap; }
        .tb-btn {
          padding:4px 9px; border:1px solid #e2e8f0; border-radius:6px;
          background:#fff; color:#475569; font-size:12px; cursor:pointer;
          display:flex; align-items:center; gap:4px; white-space:nowrap;
          font-family:'Segoe UI',sans-serif; transition:all .15s;
        }
        .tb-btn:hover { background:#eff6ff; color:#0d6efd; border-color:#bfdbfe; }
        .tb-btn i { font-size:12px; }
        .view-toggle { display:flex; gap:3px; margin-left:auto; }
        .view-btn {
          padding:4px 10px; border:1px solid #e2e8f0; border-radius:6px;
          background:transparent; color:#94a3b8; font-size:12px; cursor:pointer; transition:all .15s;
        }
        .view-btn.active { background:#0d6efd; color:#fff; border-color:#0d6efd; }
        .editor-content {
          min-height:240px; max-height:360px; overflow-y:auto;
          padding:16px; font-size:14px; line-height:1.7;
          background:#fff; color:#333; font-family:'Segoe UI',sans-serif;
        }
        .editor-content:focus { outline:none; }
        .editor-code {
          display:none; min-height:240px; max-height:360px; overflow:auto;
          padding:14px; font-family:'Courier New',monospace; font-size:12.5px;
          background:#1e1e2e; color:#cdd6f4; border:none; resize:none; width:100%; line-height:1.6;
        }

        /* Block styles in editor */
        .editor-content .blk-card { background:#f4f8ff; border:1px solid #c5d9f5; border-radius:10px; padding:14px 16px; margin:10px 0; }
        .editor-content .blk-card strong { display:block; margin-bottom:6px; color:#1a4080; }
        .editor-content .blk-tip { background:#eafaf1; border-left:4px solid #20c997; border-radius:0 10px 10px 0; padding:12px 16px; margin:10px 0; }
        .editor-content .blk-tip strong { color:#0d6640; display:block; margin-bottom:4px; }
        .editor-content .blk-warning { background:#fff8e6; border-left:4px solid #ffc107; border-radius:0 10px 10px 0; padding:12px 16px; margin:10px 0; }
        .editor-content .blk-warning strong { color:#856404; display:block; margin-bottom:4px; }
        .editor-content .blk-danger { background:#fff0f0; border-left:4px solid #ff4757; border-radius:0 10px 10px 0; padding:12px 16px; margin:10px 0; }
        .editor-content .blk-danger strong { color:#a81a27; display:block; margin-bottom:4px; }
        .editor-content .blk-info { background:#e8f4fd; border-left:4px solid #0d6efd; border-radius:0 10px 10px 0; padding:12px 16px; margin:10px 0; }
        .editor-content .blk-info strong { color:#0a4e9e; display:block; margin-bottom:4px; }
        .editor-content .blk-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin:10px 0; }
        .editor-content .blk-grid3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin:10px 0; }
        .editor-content .blk-grid2 .blk-card, .editor-content .blk-grid3 .blk-card { margin:0; }
        .editor-content .comparison-table { width:100%; border-collapse:collapse; margin:12px 0; font-size:13px; }
        .editor-content .comparison-table th { background:#e8eef8; padding:9px 12px; text-align:left; border:1px solid #c5d0e0; font-weight:600; color:#333; }
        .editor-content .comparison-table td { padding:8px 12px; border:1px solid #dde3ed; vertical-align:top; }
        .editor-content .comparison-table tr:nth-child(even) td { background:#f8faff; }
        .editor-content .blk-321 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin:12px 0; text-align:center; }
        .editor-content .blk-321-item { background:#f4f8ff; border-radius:10px; padding:14px 10px; border:1px solid #c5d9f5; }
        .editor-content .blk-321-num { font-size:36px; font-weight:700; color:#0d6efd; line-height:1; }
        .editor-content .blk-321-label { font-size:12px; color:#666; margin-top:6px; }
        .editor-content .blk-steps { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin:12px 0; }
        .editor-content .blk-step { background:#f4f8ff; border-radius:10px; padding:16px 12px; text-align:center; border:1px solid #c5d9f5; }
        .editor-content .blk-step-num { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:50%; background:#0d6efd; color:#fff; font-weight:700; font-size:16px; margin-bottom:8px; }
        .editor-content .blk-step-title { font-weight:600; margin-bottom:4px; font-size:13px; }
        .editor-content .blk-step-desc { font-size:12px; color:#666; }
    </style>
</head>
<body>

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-header"><h2>🔐 Secura</h2></div>
    <ul class="sidebar-menu">
      <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
      <li><a href="all_modules.php"><i class="fas fa-layer-group"></i> Modules</a></li>
      <li><a href="#"><i class="fas fa-cog"></i> Paramètres</a></li>
      <li><a href="admin_users.php"><i class="fas fa-users"></i> Utilisateurs</a></li>
    </ul>
  </aside>

  <div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
      <div class="topbar-title">
        <i class="fas fa-book-open" style="color:#0d6efd;"></i>
        Cours : <span><?php echo htmlspecialchars($module['title']); ?></span>
      </div>
      <div class="user-info">
        <img src="<?php echo !empty($_SESSION['user_avatar']) ? htmlspecialchars($_SESSION['user_avatar']) : 'assets/images/default-avatar.svg'; ?>"
             alt="Avatar" class="user-avatar" id="userAvatar">
        <div class="user-dropdown" id="userDropdown">
          <a href="profil.php" class="user-dropdown-item"><i class="fas fa-user"></i> Mon Profil</a>
          <a href="admin_users.php" class="user-dropdown-item"><i class="fas fa-users"></i> Utilisateurs</a>
          <a href="login.php?action=logout" class="user-dropdown-item"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </div>
      </div>
    </div>

    <div class="container">

      <!-- Action bar -->
      <div class="action-bar">
        <a href="admin_dashboard.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Dashboard</a>
        <a href="module.php?id=<?php echo $module_id; ?>" class="btn btn-view"><i class="fas fa-eye"></i> Voir le module</a>
        <a href="admin_quiz.php?id=<?php echo $module_id; ?>" class="btn btn-quiz"><i class="fas fa-circle-question"></i> Quiz</a>
        <button onclick="openModal()" class="btn btn-primary" style="margin-left:auto;">
          <i class="fas fa-plus"></i> Ajouter un chapitre
        </button>
      </div>

      <!-- Liste des chapitres -->
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

    </div>
  </div>

  <!-- ═══ MODAL ═══ -->
  <div id="chapterModal" class="modal">
    <div class="modal-content">
      <h2 id="modalTitle">Ajouter un chapitre</h2>

      <form id="chapterForm">
        <input type="hidden" id="chapterId" name="id">
        <input type="hidden" name="module_id" value="<?php echo $module_id; ?>">

        <div class="form-row">
          <div class="form-group">
            <label><i class="fas fa-heading" style="color:#00d4ff"></i> Titre du chapitre</label>
            <input type="text" id="title" name="title" placeholder="Ex : Leçon 1 — Introduction" required>
          </div>
          <div class="form-group">
            <label><i class="fas fa-sort-numeric-up" style="color:#00d4ff"></i> Ordre d'affichage</label>
            <input type="number" id="order_index" name="order_index" value="0" min="0">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label><i class="fas fa-video" style="color:#00d4ff"></i> URL vidéo (optionnel)</label>
            <input type="url" id="video_url" name="video_url" placeholder="https://www.youtube-nocookie.com/embed/…">
          </div>
          <div class="form-group">
            <label><i class="fas fa-image" style="color:#00d4ff"></i> URL image (optionnel)</label>
            <input type="url" id="image_url" name="image_url" placeholder="https://…">
          </div>
        </div>

        <div class="form-group">
          <label><i class="fas fa-pen-to-square" style="color:#00d4ff"></i> Contenu du chapitre</label>
          <div class="editor-wrapper">
            <div class="editor-toolbar">
              <div class="tb-group">
                <span class="tb-label">Texte</span>
                <button type="button" class="tb-btn" onclick="execCmd('bold')"><i class="fas fa-bold"></i></button>
                <button type="button" class="tb-btn" onclick="execCmd('italic')"><i class="fas fa-italic"></i></button>
                <button type="button" class="tb-btn" onclick="execCmd('underline')"><i class="fas fa-underline"></i></button>
                <button type="button" class="tb-btn" onclick="insertHeading('h4')"><i class="fas fa-heading"></i> H4</button>
                <button type="button" class="tb-btn" onclick="insertHeading('h6')"><i class="fas fa-heading"></i> H6</button>
              </div>
              <div class="tb-sep"></div>
              <div class="tb-group">
                <span class="tb-label">Liste</span>
                <button type="button" class="tb-btn" onclick="execCmd('insertUnorderedList')"><i class="fas fa-list-ul"></i></button>
                <button type="button" class="tb-btn" onclick="execCmd('insertOrderedList')"><i class="fas fa-list-ol"></i></button>
              </div>
              <div class="tb-sep"></div>
              <div class="tb-group">
                <span class="tb-label">Blocs</span>
                <button type="button" class="tb-btn" onclick="insBlock('card')"><i class="fas fa-square"></i> Carte</button>
                <button type="button" class="tb-btn" onclick="insBlock('grid2')"><i class="fas fa-columns"></i> 2 col.</button>
                <button type="button" class="tb-btn" onclick="insBlock('grid3')"><i class="fas fa-table-columns"></i> 3 col.</button>
                <button type="button" class="tb-btn" onclick="insBlock('steps')"><i class="fas fa-shoe-prints"></i> Étapes</button>
              </div>
              <div class="tb-sep"></div>
              <div class="tb-group">
                <span class="tb-label">Alertes</span>
                <button type="button" class="tb-btn" onclick="insBlock('tip')" style="color:#16a34a"><i class="fas fa-lightbulb"></i> Conseil</button>
                <button type="button" class="tb-btn" onclick="insBlock('warning')" style="color:#b45309"><i class="fas fa-triangle-exclamation"></i> Attention</button>
                <button type="button" class="tb-btn" onclick="insBlock('danger')" style="color:#dc2626"><i class="fas fa-circle-xmark"></i> Danger</button>
                <button type="button" class="tb-btn" onclick="insBlock('info')" style="color:#1d4ed8"><i class="fas fa-circle-info"></i> Info</button>
              </div>
              <div class="tb-sep"></div>
              <div class="tb-group">
                <span class="tb-label">Tableaux</span>
                <button type="button" class="tb-btn" onclick="insBlock('table2')"><i class="fas fa-table"></i> 2 col.</button>
                <button type="button" class="tb-btn" onclick="insBlock('table3')"><i class="fas fa-table"></i> 3 col.</button>
                <button type="button" class="tb-btn" onclick="insBlock('table4')"><i class="fas fa-table"></i> 4 col.</button>
              </div>
              <div class="tb-sep"></div>
              <div class="tb-group">
                <span class="tb-label">Spécial</span>
                <button type="button" class="tb-btn" onclick="insBlock('321')"><i class="fas fa-3"></i> 3-2-1</button>
              </div>
              <div class="view-toggle">
                <button type="button" class="view-btn active" id="btnPreview" onclick="switchView('preview')"><i class="fas fa-eye"></i> Aperçu</button>
                <button type="button" class="view-btn" id="btnCode" onclick="switchView('code')"><i class="fas fa-code"></i> HTML</button>
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
            <label><i class="fas fa-align-left" style="color:#00d4ff"></i> Description courte</label>
            <textarea id="description" name="description" rows="3" placeholder="Résumé affiché dans la liste des chapitres…"></textarea>
          </div>
          <div class="form-group" style="display:flex;flex-direction:column;gap:14px;justify-content:center;padding-top:22px;">
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
          <button type="button" onclick="closeModal()" class="btn-cancel">Annuler</button>
          <button type="submit" class="btn-submit"><i class="fas fa-floppy-disk"></i> Enregistrer</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  const BLOCKS = {
    card: `<div class="blk-card"><strong>Titre de la carte</strong><p>Décrivez le contenu ici…</p></div>`,
    grid2: `<div class="blk-grid2"><div class="blk-card"><strong>Colonne 1</strong><p>Contenu…</p></div><div class="blk-card"><strong>Colonne 2</strong><p>Contenu…</p></div></div>`,
    grid3: `<div class="blk-grid3"><div class="blk-card"><strong>Colonne 1</strong><p>Contenu…</p></div><div class="blk-card"><strong>Colonne 2</strong><p>Contenu…</p></div><div class="blk-card"><strong>Colonne 3</strong><p>Contenu…</p></div></div>`,
    tip:     `<div class="blk-tip"><strong>💡 Conseil</strong><p>Votre conseil pratique ici…</p></div>`,
    warning: `<div class="blk-warning"><strong>⚠️ Avertissement</strong><p>Votre message d'alerte ici…</p></div>`,
    danger:  `<div class="blk-danger"><strong>🚫 Danger</strong><p>Ne jamais faire cela…</p></div>`,
    info:    `<div class="blk-info"><strong>ℹ️ Information</strong><p>Votre message informatif ici…</p></div>`,
    table2: `<table class="comparison-table"><thead><tr><th>Colonne A</th><th>Colonne B</th></tr></thead><tbody><tr><td>Valeur 1</td><td>Valeur 2</td></tr><tr><td>Valeur 3</td><td>Valeur 4</td></tr></tbody></table>`,
    table3: `<table class="comparison-table"><thead><tr><th>Type</th><th>Exemples</th><th>Description</th></tr></thead><tbody><tr><td>Ligne 1</td><td>Ex A</td><td>Description…</td></tr></tbody></table>`,
    table4: `<table class="comparison-table"><thead><tr><th>Outil</th><th>Exemples</th><th>Fonction</th><th>Niveau</th></tr></thead><tbody><tr><td>Antivirus</td><td>Bitdefender</td><td>Détection</td><td>Essentiel</td></tr></tbody></table>`,
    '321': `<div class="blk-321"><div class="blk-321-item"><div class="blk-321-num">3</div><div class="blk-321-label">Copies de données</div></div><div class="blk-321-item"><div class="blk-321-num">2</div><div class="blk-321-label">Supports différents</div></div><div class="blk-321-item"><div class="blk-321-num">1</div><div class="blk-321-label">Copie hors ligne</div></div></div>`,
    steps: `<div class="blk-steps"><div class="blk-step"><div class="blk-step-num">1</div><div class="blk-step-title">Isoler</div><div class="blk-step-desc">Déconnecter l'appareil du réseau</div></div><div class="blk-step"><div class="blk-step-num">2</div><div class="blk-step-title">Signaler</div><div class="blk-step-desc">Alerter votre service informatique</div></div><div class="blk-step"><div class="blk-step-num">3</div><div class="blk-step-title">Restaurer</div><div class="blk-step-desc">Utiliser vos sauvegardes</div></div></div>`
  };

  let editorView = 'preview';
  const edContent = () => document.getElementById('editorContent');
  const edCode    = () => document.getElementById('editorCode');

  function switchView(v) {
    editorView = v;
    document.getElementById('btnPreview').className = 'view-btn' + (v==='preview'?' active':'');
    document.getElementById('btnCode').className    = 'view-btn' + (v==='code'?' active':'');
    if (v==='code') { edCode().value=edContent().innerHTML; edContent().style.display='none'; edCode().style.display='block'; }
    else { edContent().innerHTML=edCode().value; edCode().style.display='none'; edContent().style.display='block'; }
  }
  function getEditorHTML() { return editorView==='code' ? edCode().value : edContent().innerHTML; }
  function setEditorHTML(html) { edContent().innerHTML=html||''; edCode().value=html||''; }
  function execCmd(cmd) { if(editorView==='code') return; edContent().focus(); document.execCommand(cmd,false,null); }
  function insertHeading(tag) { if(editorView==='code') return; edContent().focus(); document.execCommand('formatBlock',false,'<'+tag+'>'); }
  function insBlock(type) {
    const html = BLOCKS[type]; if(!html) return;
    if (editorView==='code') { const ta=edCode(),pos=ta.selectionStart; ta.value=ta.value.slice(0,pos)+'\n\n'+html+'\n\n'+ta.value.slice(pos); ta.selectionStart=ta.selectionEnd=pos+html.length+4; ta.focus(); return; }
    edContent().focus();
    const sel=window.getSelection(), range=sel&&sel.rangeCount>0?sel.getRangeAt(0):null, wrap=document.createElement('div'); wrap.innerHTML=html; const node=wrap.firstChild;
    if(range&&edContent().contains(range.commonAncestorContainer)) { range.insertNode(node); range.setStartAfter(node); sel.removeAllRanges(); sel.addRange(range); }
    else edContent().appendChild(node);
  }

  function openModal() {
    document.getElementById('modalTitle').innerText='Ajouter un chapitre';
    document.getElementById('chapterForm').reset();
    document.getElementById('chapterId').value='';
    document.getElementById('order_index').value='0';
    document.getElementById('quiz_enabled').checked=false;
    document.getElementById('chapter_published').checked=true;
    setEditorHTML('<p>Commencez à écrire le contenu ou insérez un bloc depuis la barre d\'outils…</p>');
    if(editorView==='code') switchView('preview');
    document.getElementById('chapterModal').classList.add('open');
  }
  function closeModal() { document.getElementById('chapterModal').classList.remove('open'); }

  function editChapterFromData(c) {
    document.getElementById('modalTitle').innerText='Modifier le chapitre';
    document.getElementById('chapterId').value      = c.id;
    document.getElementById('title').value          = c.title       || '';
    document.getElementById('video_url').value      = c.video_url   || '';
    document.getElementById('image_url').value      = c.image_url   || '';
    document.getElementById('quiz_enabled').checked = c.quiz_enabled == 1;
    document.getElementById('description').value    = c.description || '';
    document.getElementById('order_index').value    = c.order_index || 0;
    setEditorHTML(c.content || '');
    if(editorView==='code') switchView('preview');
    document.getElementById('chapterModal').classList.add('open');
  }

  function escHtml(str) { const d=document.createElement('div'); d.textContent=str||''; return d.innerHTML; }

  /* ── Charger la liste — TITRE SEULEMENT ── */
  function loadChapters() {
    fetch('manage_content.php?action=list_chapters&module_id=<?php echo $module_id; ?>')
      .then(r => r.text().then(t => { try { return JSON.parse(t); } catch(e) { throw new Error('Réponse invalide'); } }))
      .then(data => {
        const list = document.getElementById('chapterList');
        const count = document.getElementById('chapterCount');

        if (!Array.isArray(data) || !data.length) {
          count.textContent = '0 chapitre';
          list.innerHTML = `
            <div class="empty-state">
              <i class="fas fa-book-open"></i>
              <p>Aucun chapitre. Cliquez sur <strong>Ajouter un chapitre</strong> pour commencer.</p>
            </div>`;
          return;
        }

        count.textContent = data.length + ' chapitre' + (data.length > 1 ? 's' : '');

        /* ✅ Affichage : numéro + titre uniquement + petits badges discrets */
        list.innerHTML = data.map((c, i) => `
          <div class="chapter-item">
            <div class="chapter-left">
              <div class="chapter-index">${i + 1}</div>
              <div>
                <div class="chapter-title">${escHtml(c.title)}</div>
                <div class="chapter-badges">
                  ${c.video_url   ? '<span class="badge badge-video"><i class="fas fa-video"></i> Vidéo</span>'    : ''}
                  ${c.image_url   ? '<span class="badge badge-img"><i class="fas fa-image"></i> Image</span>'      : ''}
                  ${c.quiz_enabled == 1 ? '<span class="badge badge-quiz"><i class="fas fa-circle-question"></i> Quiz</span>' : ''}
                </div>
              </div>
            </div>
            <div class="chapter-actions">
              <button onclick='editChapterFromData(${JSON.stringify(c).replace(/'/g,"&#39;")})' class="btn-edit">
                <i class="fas fa-pen"></i> Modifier
              </button>
              <button onclick="deleteChapter(${c.id})" class="btn-del">
                <i class="fas fa-trash"></i> Supprimer
              </button>
            </div>
          </div>
        `).join('');
      })
      .catch(err => console.error('Erreur:', err));
  }

  function deleteChapter(id) {
    if (!confirm('Supprimer ce chapitre définitivement ?')) return;
    fetch('manage_content.php?action=delete_chapter', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`id=${id}` })
      .then(r => r.json())
      .then(data => { if(data.success) loadChapters(); else alert(data.error||'Erreur.'); })
      .catch(err => alert('Erreur : '+err.message));
  }

  document.getElementById('chapterForm').onsubmit = function(e) {
    e.preventDefault();
    document.getElementById('contentHidden').value = getEditorHTML();
    const formData = new FormData(this);
    if (!document.getElementById('quiz_enabled').checked) formData.set('quiz_enabled','0');
    const action = document.getElementById('chapterId').value ? 'update_chapter' : 'add_chapter';
    fetch('manage_content.php?action=' + action, { method:'POST', body: new URLSearchParams(formData) })
      .then(r => r.text().then(t => { try { return JSON.parse(t); } catch(e) { throw new Error('Réponse invalide'); } }))
      .then(data => { if(data.success) { closeModal(); loadChapters(); } else alert(data.error||'Erreur.'); })
      .catch(err => alert('Erreur : '+err.message));
  };

  document.addEventListener('DOMContentLoaded', function () {
    const avatar=document.getElementById('userAvatar'), dropdown=document.getElementById('userDropdown');
    avatar.addEventListener('click', e => { e.stopPropagation(); dropdown.classList.toggle('open'); });
    document.addEventListener('click', () => dropdown.classList.remove('open'));
    loadChapters();
  });
  </script>
</body>
</html>