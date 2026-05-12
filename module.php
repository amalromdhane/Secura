<?php
session_start();
require_once 'includes/config.php';

$is_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
$user_role    = $_SESSION['user_role'] ?? '';
$action       = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        header('Content-Type: application/json');
        try {
            $pdo  = getDBConnection('cyber');
            $stmt = $pdo->query("SELECT * FROM modules ORDER BY id DESC");
            echo json_encode(['success' => true, 'modules' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();

    case 'add':
    case 'update':
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Non autorisé']); exit();
        }
        try {
            $pdo  = getDBConnection('cyber');
            $data = [
                'title'        => $_POST['title']        ?? '',
                'category'     => $_POST['category']     ?? '',
                'duration'     => (int)($_POST['duration'] ?? 30),
                'description'  => $_POST['description']  ?? '',
                'image'        => $_POST['image']        ?? '',
                'page'         => $_POST['page']         ?? '',
                'video_url'    => $_POST['video_url']    ?? '',
                'content'      => $_POST['content']      ?? '',
                'quiz_enabled' => isset($_POST['quiz_enabled']) ? (int)$_POST['quiz_enabled'] : 0,
                'quiz_page'    => $_POST['quiz_page']    ?? '',
                'active'       => isset($_POST['active']) ? (int)$_POST['active'] : 1,
            ];
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO modules (title,category,duration,description,image,page,video_url,content,quiz_enabled,quiz_page,active) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute(array_values($data));
                $insertId = $pdo->lastInsertId();
            } else {
                $data['id'] = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE modules SET title=?,category=?,duration=?,description=?,image=?,page=?,video_url=?,content=?,quiz_enabled=?,quiz_page=?,active=?,updated_at=CURRENT_TIMESTAMP WHERE id=?");
                $stmt->execute(array_values($data));
                $insertId = $data['id'];
            }
            echo json_encode(['success' => true, 'id' => $insertId]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();

    case 'delete':
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Non autorisé']); exit();
        }
        try {
            $pdo  = getDBConnection('cyber');
            $stmt = $pdo->prepare("DELETE FROM modules WHERE id=?");
            echo json_encode(['success' => $stmt->execute([$_POST['id']])]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: index.html'); exit(); }

try {
    $pdo  = getDBConnection('cyber');
    $stmt = $pdo->prepare("SELECT * FROM modules WHERE id = ?");
    $stmt->execute([$id]);
    $module = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$module) { header('Location: index.html'); exit(); }

    $stmt = $pdo->prepare("SELECT * FROM course_chapters WHERE module_id = ? ORDER BY order_index ASC");
    $stmt->execute([$id]);
    $chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $chapterColumns = [];
    if (!empty($chapters)) { $chapterColumns = array_keys($chapters[0]); }
    $hasDescription = in_array('description', $chapterColumns);
    $hasContent     = in_array('content',     $chapterColumns);
    $hasVideoUrl    = in_array('video_url',   $chapterColumns);
    $hasImageUrl    = in_array('image_url',   $chapterColumns);

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

function col(array $row, string $key, string $default = ''): string {
    return isset($row[$key]) ? (string)$row[$key] : $default;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($module['title']); ?> - Secura</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/cyberaware.css">
  <style>
    :root { --neon-cyan:#00d4ff; --neon-green:#00ff88; }

    .video-wrapper {
      position:relative; padding-bottom:56.25%; height:0;
      overflow:hidden; border-radius:12px; margin-bottom:16px;
    }
    .video-wrapper iframe { position:absolute; top:0; left:0; width:100%; height:100%; border:none; }

    .card-module {
      background:rgba(18,24,38,.88); border:1px solid rgba(255,255,255,.08);
      border-radius:18px; padding:28px; margin-bottom:26px;
    }
    .progress-sidebar .card-module { position:sticky; top:20px; }
    .lesson-step {
      display:flex; align-items:flex-start; gap:14px;
      padding:13px 0; border-bottom:1px solid rgba(255,255,255,.05);
    }
    .lesson-step:last-child { border-bottom:none; }
    .lesson-step.active .step-number { background:var(--neon-cyan); color:#000; }
    .step-number {
      width:30px; height:30px; border-radius:50%;
      background:rgba(255,255,255,.1); flex-shrink:0;
      display:flex; align-items:center; justify-content:center;
      font-weight:600; color:#adb5bd; font-size:.85rem;
    }
    .step-label strong { display:block; font-size:.88rem; color:#e2e8f0; }
    .step-label small  { font-size:.78rem; color:#8896a7; line-height:1.4; }
    .quiz-cta {
      display:block; background:linear-gradient(135deg,#0d6efd,#00d4ff);
      color:#fff; text-decoration:none; border-radius:14px;
      padding:26px; text-align:center; margin:30px 0; transition:transform .3s;
    }
    .quiz-cta:hover { transform:translateY(-3px); color:#fff; }
    .quiz-cta h3 { margin:0 0 6px; font-weight:700; }
    .quiz-cta p  { margin:0; opacity:.9; font-size:.95rem; }
    .user-info { position:relative; display:flex; align-items:center; margin-left:20px; }
    .user-avatar { width:42px; height:42px; border-radius:50%; cursor:pointer; border:2px solid var(--neon-cyan); transition:all .3s; object-fit:cover; }
    .user-avatar:hover { border-color:var(--neon-green); box-shadow:0 0 15px rgba(0,212,255,.5); }
    .user-dropdown {
      position:absolute; top:100%; right:0; background:rgba(18,24,38,.97);
      border:1px solid rgba(255,255,255,.1); border-radius:10px;
      min-width:185px; opacity:0; visibility:hidden;
      transform:translateY(-10px); transition:all .3s; z-index:9999; box-shadow:0 8px 28px rgba(0,0,0,.35);
    }
    .user-dropdown.show { opacity:1; visibility:visible; transform:translateY(0); }
    .user-dropdown-item { display:block; padding:12px 16px; color:#e9ecef; text-decoration:none; transition:all .25s; border-bottom:1px solid rgba(255,255,255,.05); }
    .user-dropdown-item:last-child { border-bottom:none; }
    .user-dropdown-item:hover { background:rgba(0,212,255,.1); color:var(--neon-cyan); }
    .user-dropdown-item i { margin-right:8px; width:16px; }
    .info-item { display:flex; align-items:center; gap:10px; margin-bottom:12px; color:#c9d1d9; font-size:.9rem; }
    .info-item i { width:16px; color:#8896a7; }
    .glass-card p      { color:#c9d1d9; line-height:1.75; }
    .glass-card strong { color:#e2e8f0; }
    .glass-card a      { color:var(--neon-cyan); text-decoration:none; }
    .glass-card a:hover { text-decoration:underline; }

    /* ============================================================
       RICH CONTENT — styles intégrés directement pour surpasser
       cyberaware.css. Sélecteurs ultra-spécifiques.
    ============================================================ */

    /* Zone */
    div.chapter-rich-content,
    div.module-rich-content {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      font-size: 14px;
      line-height: 1.75;
      color: #c9d1d9;
    }

    /* Texte */
    div.chapter-rich-content p,
    div.module-rich-content p   { color: #c9d1d9; margin: 0 0 0.85em; }
    div.chapter-rich-content ul,
    div.chapter-rich-content ol,
    div.module-rich-content ul,
    div.module-rich-content ol  { padding-left: 1.5em; margin-bottom: 0.85em; color: #c9d1d9; }
    div.chapter-rich-content li,
    div.module-rich-content li  { margin-bottom: 0.35em; color: #c9d1d9; }
    div.chapter-rich-content strong,
    div.module-rich-content strong { color: #e2e8f0; }
    div.chapter-rich-content h4,
    div.module-rich-content h4  { color: #00d4ff; font-size: 1.05rem; margin: 1em 0 0.4em; }
    div.chapter-rich-content h6,
    div.module-rich-content h6  { color: #8896a7; font-size: 0.88rem; margin: 0.8em 0 0.3em; }

    /* ── CARTE ── */
    div.chapter-rich-content .blk-card,
    div.module-rich-content .blk-card {
      background: rgba(13,110,253,0.08) !important;
      border: 1px solid rgba(0,212,255,0.25) !important;
      border-radius: 12px !important;
      padding: 16px 18px !important;
      margin: 12px 0 !important;
      position: relative;
      overflow: hidden;
      display: block;
    }
    div.chapter-rich-content .blk-card::before,
    div.module-rich-content .blk-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; height: 2px;
      background: linear-gradient(90deg,#0d6efd,#00d4ff);
    }
    div.chapter-rich-content .blk-card > strong,
    div.module-rich-content .blk-card > strong {
      display: block !important;
      color: #6ea8fe !important;
      font-size: 0.96rem !important;
      font-weight: 700 !important;
      margin-bottom: 8px !important;
    }
    div.chapter-rich-content .blk-card > p,
    div.module-rich-content .blk-card > p { color: #c9d1d9 !important; margin: 0 !important; }

    /* ── GRILLES ── */
    div.chapter-rich-content .blk-grid2,
    div.module-rich-content .blk-grid2 {
      display: grid !important; grid-template-columns: 1fr 1fr !important;
      gap: 14px !important; margin: 14px 0 !important;
    }
    div.chapter-rich-content .blk-grid3,
    div.module-rich-content .blk-grid3 {
      display: grid !important; grid-template-columns: 1fr 1fr 1fr !important;
      gap: 12px !important; margin: 14px 0 !important;
    }
    div.chapter-rich-content .blk-grid2 .blk-card,
    div.chapter-rich-content .blk-grid3 .blk-card,
    div.module-rich-content .blk-grid2 .blk-card,
    div.module-rich-content .blk-grid3 .blk-card { margin: 0 !important; }
    @media(max-width:768px){
      div.chapter-rich-content .blk-grid2,
      div.chapter-rich-content .blk-grid3,
      div.module-rich-content .blk-grid2,
      div.module-rich-content .blk-grid3 { grid-template-columns: 1fr !important; }
    }

    /* ── ALERTES ── */
    div.chapter-rich-content .blk-tip,
    div.module-rich-content .blk-tip {
      background: rgba(32,201,151,0.09) !important;
      border-left: 4px solid #20c997 !important;
      border-top:none; border-right:none; border-bottom:none;
      border-radius: 0 12px 12px 0 !important;
      padding: 14px 18px !important; margin: 12px 0 !important; display: block !important;
    }
    div.chapter-rich-content .blk-tip > strong,
    div.module-rich-content .blk-tip > strong {
      display:block !important; color:#20c997 !important;
      font-weight:700 !important; margin-bottom:5px !important;
    }
    div.chapter-rich-content .blk-tip > p,
    div.module-rich-content .blk-tip > p { color:#a8d5c2 !important; margin:0 !important; }

    div.chapter-rich-content .blk-warning,
    div.module-rich-content .blk-warning {
      background: rgba(255,193,7,0.09) !important;
      border-left: 4px solid #ffc107 !important;
      border-top:none; border-right:none; border-bottom:none;
      border-radius: 0 12px 12px 0 !important;
      padding: 14px 18px !important; margin: 12px 0 !important; display: block !important;
    }
    div.chapter-rich-content .blk-warning > strong,
    div.module-rich-content .blk-warning > strong {
      display:block !important; color:#ffc107 !important;
      font-weight:700 !important; margin-bottom:5px !important;
    }
    div.chapter-rich-content .blk-warning > p,
    div.module-rich-content .blk-warning > p { color:#d4b483 !important; margin:0 !important; }

    div.chapter-rich-content .blk-danger,
    div.module-rich-content .blk-danger {
      background: rgba(255,71,87,0.09) !important;
      border-left: 4px solid #ff4757 !important;
      border-top:none; border-right:none; border-bottom:none;
      border-radius: 0 12px 12px 0 !important;
      padding: 14px 18px !important; margin: 12px 0 !important; display: block !important;
    }
    div.chapter-rich-content .blk-danger > strong,
    div.module-rich-content .blk-danger > strong {
      display:block !important; color:#ff6b7a !important;
      font-weight:700 !important; margin-bottom:5px !important;
    }
    div.chapter-rich-content .blk-danger > p,
    div.module-rich-content .blk-danger > p { color:#d4a0a5 !important; margin:0 !important; }

    div.chapter-rich-content .blk-info,
    div.module-rich-content .blk-info {
      background: rgba(13,110,253,0.09) !important;
      border-left: 4px solid #0d6efd !important;
      border-top:none; border-right:none; border-bottom:none;
      border-radius: 0 12px 12px 0 !important;
      padding: 14px 18px !important; margin: 12px 0 !important; display: block !important;
    }
    div.chapter-rich-content .blk-info > strong,
    div.module-rich-content .blk-info > strong {
      display:block !important; color:#6ea8fe !important;
      font-weight:700 !important; margin-bottom:5px !important;
    }
    div.chapter-rich-content .blk-info > p,
    div.module-rich-content .blk-info > p { color:#93b8f5 !important; margin:0 !important; }

    /* ── TABLEAU ── */
    div.chapter-rich-content table.comparison-table,
    div.module-rich-content table.comparison-table {
      width: 100% !important; border-collapse: collapse !important;
      margin: 16px 0 !important; font-size: 13px !important;
      display: table !important;
      border: 1px solid rgba(0,212,255,0.18) !important;
      border-radius: 10px !important; overflow: hidden !important;
    }
    div.chapter-rich-content table.comparison-table thead,
    div.module-rich-content table.comparison-table thead {
      background: linear-gradient(135deg,rgba(13,110,253,0.35),rgba(0,212,255,0.15)) !important;
    }
    div.chapter-rich-content table.comparison-table th,
    div.module-rich-content table.comparison-table th {
      padding: 11px 14px !important; text-align: left !important;
      font-weight: 700 !important; color: #e2e8f0 !important;
      border: 1px solid rgba(0,212,255,0.12) !important;
      font-size: 0.78rem !important; text-transform: uppercase !important; letter-spacing: .4px !important;
    }
    div.chapter-rich-content table.comparison-table td,
    div.module-rich-content table.comparison-table td {
      padding: 10px 14px !important; color: #c9d1d9 !important;
      border: 1px solid rgba(255,255,255,0.05) !important; vertical-align: top !important;
    }
    div.chapter-rich-content table.comparison-table tr:nth-child(even) td,
    div.module-rich-content table.comparison-table tr:nth-child(even) td {
      background: rgba(255,255,255,0.03) !important;
    }
    div.chapter-rich-content table.comparison-table tbody tr:hover td,
    div.module-rich-content table.comparison-table tbody tr:hover td {
      background: rgba(0,212,255,0.06) !important;
    }

    /* ── 3-2-1 ── */
    div.chapter-rich-content .blk-321,
    div.module-rich-content .blk-321 {
      display: grid !important; grid-template-columns: repeat(3,1fr) !important;
      gap: 12px !important; margin: 16px 0 !important; text-align: center !important;
    }
    div.chapter-rich-content .blk-321-item,
    div.module-rich-content .blk-321-item {
      background: rgba(13,110,253,0.09) !important;
      border: 1px solid rgba(0,212,255,0.22) !important;
      border-radius: 12px !important; padding: 22px 14px !important; display: block !important;
      transition: transform .22s, box-shadow .22s;
    }
    div.chapter-rich-content .blk-321-item:hover,
    div.module-rich-content .blk-321-item:hover {
      transform: translateY(-4px); box-shadow: 0 10px 26px rgba(0,212,255,0.15);
    }
    div.chapter-rich-content .blk-321-num,
    div.module-rich-content .blk-321-num {
      font-size: 2.8rem !important; font-weight: 900 !important;
      color: #00d4ff !important; line-height: 1 !important;
      text-shadow: 0 0 20px rgba(0,212,255,0.4) !important; display: block !important;
    }
    div.chapter-rich-content .blk-321-label,
    div.module-rich-content .blk-321-label {
      font-size: 0.76rem !important; color: #8896a7 !important;
      margin-top: 9px !important; line-height: 1.45 !important; display: block !important;
    }
    @media(max-width:600px){
      div.chapter-rich-content .blk-321,
      div.module-rich-content .blk-321 { grid-template-columns: 1fr !important; }
    }

    /* ── ÉTAPES ── */
    div.chapter-rich-content .blk-steps,
    div.module-rich-content .blk-steps {
      display: grid !important; grid-template-columns: repeat(3,1fr) !important;
      gap: 12px !important; margin: 16px 0 !important;
    }
    div.chapter-rich-content .blk-step,
    div.module-rich-content .blk-step {
      background: rgba(13,110,253,0.08) !important;
      border: 1px solid rgba(13,110,253,0.22) !important;
      border-radius: 12px !important; padding: 20px 14px !important;
      text-align: center !important; display: block !important;
      transition: transform .22s, box-shadow .22s;
    }
    div.chapter-rich-content .blk-step:hover,
    div.module-rich-content .blk-step:hover {
      transform: translateY(-4px); box-shadow: 0 10px 26px rgba(0,212,255,0.12);
    }
    div.chapter-rich-content .blk-step-num,
    div.module-rich-content .blk-step-num {
      display: inline-flex !important; align-items: center !important;
      justify-content: center !important; width: 40px !important; height: 40px !important;
      border-radius: 50% !important;
      background: linear-gradient(135deg,#0d6efd,#00aaff) !important;
      color: #fff !important; font-weight: 800 !important; font-size: 1.05rem !important;
      margin-bottom: 12px !important; box-shadow: 0 4px 14px rgba(13,110,253,0.45) !important;
    }
    div.chapter-rich-content .blk-step-title,
    div.module-rich-content .blk-step-title {
      font-weight: 700 !important; color: #e2e8f0 !important;
      margin-bottom: 6px !important; font-size: 0.9rem !important; display: block !important;
    }
    div.chapter-rich-content .blk-step-desc,
    div.module-rich-content .blk-step-desc {
      font-size: 0.78rem !important; color: #8896a7 !important;
      line-height: 1.5 !important; display: block !important;
    }
    @media(max-width:768px){
      div.chapter-rich-content .blk-steps,
      div.module-rich-content .blk-steps { grid-template-columns: 1fr !important; }
    }

    /* ── SÉPARATEUR CHAPITRE ── */
    .chapter-divider { display:flex; align-items:center; gap:14px; margin-bottom:22px; }
    .chapter-divider-badge {
      background: linear-gradient(135deg,#0d6efd,#00d4ff); color:#fff;
      font-size:.7rem; font-weight:700; padding:5px 15px; border-radius:20px;
      white-space:nowrap; letter-spacing:.6px; text-transform:uppercase;
      box-shadow:0 4px 12px rgba(0,212,255,.28);
    }
    .chapter-divider-line { flex:1; height:1px; background:linear-gradient(90deg,rgba(0,212,255,.35),transparent); }
  </style>
</head>
<body>

<nav class="navbar">
  <div class="nav-container">
    <a class="nav-brand" href="index.php"><i class="fas fa-shield-alt"></i> Sec<span>ura</span></a>
    <button class="nav-toggler" id="navToggler"><i class="fas fa-bars"></i></button>
    <ul class="nav-menu" id="navMenu">
      <li class="nav-item"><a class="nav-link" href="index.php#modules"><i class="fas fa-layer-group"></i> Modules</a></li>
      <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home"></i> Accueil</a></li>
      <li class="nav-item"><a class="nav-link" href="index.html#about"><i class="fas fa-info-circle"></i> À propos</a></li>
      <?php if ($is_logged_in): ?>
      <li class="nav-item">
        <div class="user-info">
          <img src="<?php echo !empty($_SESSION['user_avatar']) ? htmlspecialchars($_SESSION['user_avatar']) : 'assets/images/default-avatar.svg'; ?>" alt="Avatar" class="user-avatar" id="userAvatar">
          <div class="user-dropdown" id="userDropdown">
            <a href="profil.php" class="user-dropdown-item"><i class="fas fa-user"></i> Mon Profil</a>
            <?php if ($user_role === 'admin'): ?>
            <a href="admin_dashboard.php" class="user-dropdown-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <?php endif; ?>
            <a href="login.php?action=logout" class="user-dropdown-item"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
          </div>
        </div>
      </li>
      <?php endif; ?>
    </ul>
  </div>
</nav>

<section class="hero-section">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
        <li class="breadcrumb-item"><a href="index.php#modules">Modules</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($module['title']); ?></li>
      </ol>
    </nav>
    <div class="row align-items-center">
      <div class="col-lg-6">
        <div class="module-icon">
          <?php
          $icon_class = 'fas fa-layer-group';
          switch (strtolower($module['category'] ?? '')) {
            case 'phishing':   $icon_class = 'fas fa-envelope-open-text'; break;
            case 'réseau':     $icon_class = 'fas fa-network-wired'; break;
            case 'données':    $icon_class = 'fas fa-database'; break;
            case 'cloud':      $icon_class = 'fas fa-cloud'; break;
            case 'ia':         $icon_class = 'fas fa-brain'; break;
            case 'ransomware': $icon_class = 'fas fa-lock'; break;
            case 'sécurité':   $icon_class = 'fas fa-shield-alt'; break;
          }
          ?>
          <i class="<?php echo $icon_class; ?>"></i>
        </div>
        <h1 class="mb-4"><?php echo htmlspecialchars($module['title']); ?></h1>
        <p class="lead mb-5"><?php echo htmlspecialchars($module['description'] ?? 'Apprenez les meilleures pratiques de sécurité.'); ?></p>
        <?php if ($is_logged_in): ?>
        <div class="d-flex gap-3">
          <a href="quiz.php?id=<?php echo $module['id']; ?>" class="btn btn-primary"><i class="fas fa-play-circle me-2"></i>Démarrer le Quiz</a>
          <?php if ($user_role === 'admin'): ?>
          <a href="admin_course.php?id=<?php echo $module['id']; ?>" class="btn btn-secondary"><i class="fas fa-edit me-2"></i>Gérer le Cours</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="col-lg-6 text-center">
        <img src="<?php echo htmlspecialchars($module['image'] ?? ''); ?>"
             alt="<?php echo htmlspecialchars($module['title']); ?>"
             class="img-fluid rounded-4 shadow-lg"
             style="max-height:400px;border:3px solid var(--neon-cyan);box-shadow:0 0 20px rgba(0,255,255,.5);"
             onerror="this.style.display='none'">
      </div>
    </div>
  </div>
</section>

<div class="container" style="padding:3rem 0;">
  <div class="row">

    <div class="col-lg-8">

      <?php if (!empty($module['video_url'])): ?>
      <div class="glass-card">
        <h3 class="mb-4" style="color:var(--neon-cyan);"><i class="fas fa-play-circle me-3"></i>Vidéo d'introduction</h3>
        <div class="video-wrapper">
          <iframe src="<?php echo htmlspecialchars($module['video_url']); ?>"
                  title="<?php echo htmlspecialchars($module['title']); ?>"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($module['content'])): ?>
      <div class="glass-card">
        <h4 class="mb-4" style="color:#a78bfa;"><i class="fas fa-info-circle me-3"></i>À propos de ce module</h4>
        <div class="module-rich-content">
          <?php echo $module['content']; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($chapters)): ?>
        <?php foreach ($chapters as $index => $chapter): ?>
        <div class="glass-card">

          <div class="chapter-divider">
            <span class="chapter-divider-badge">Chapitre <?php echo $index + 1; ?></span>
            <div class="chapter-divider-line"></div>
          </div>

          <h4 style="color:var(--neon-cyan);margin-top:0;" class="mb-4">
            <i class="fas fa-book-open me-3"></i><?php echo htmlspecialchars($chapter['title']); ?>
          </h4>

          <?php $imgUrl = $hasImageUrl ? col($chapter,'image_url') : ''; if ($imgUrl): ?>
          <img src="<?php echo htmlspecialchars($imgUrl); ?>"
               alt="<?php echo htmlspecialchars($chapter['title']); ?>"
               class="img-fluid rounded-3 my-3"
               style="border:2px solid var(--neon-cyan);max-height:400px;width:100%;object-fit:cover;">
          <?php endif; ?>

          <?php $vidUrl = $hasVideoUrl ? col($chapter,'video_url') : ''; if ($vidUrl): ?>
          <div class="video-wrapper mb-3">
            <iframe src="<?php echo htmlspecialchars($vidUrl); ?>"
                    title="<?php echo htmlspecialchars($chapter['title']); ?>"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
          </div>
          <?php endif; ?>

          <?php $descText = $hasDescription ? col($chapter,'description') : ''; if ($descText): ?>
          <p style="color:#8896a7;font-style:italic;font-size:.92rem;margin-bottom:16px;">
            <i class="fas fa-circle-info me-2" style="color:var(--neon-cyan);"></i>
            <?php echo htmlspecialchars($descText); ?>
          </p>
          <?php endif; ?>

          <?php $richContent = $hasContent ? col($chapter,'content') : ''; if ($richContent): ?>
          <div class="chapter-rich-content">
            <?php echo $richContent; ?>
          </div>
          <?php endif; ?>

        </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if (!empty($module['quiz_enabled'])): ?>
      <a href="quiz.php?id=<?php echo $module['id']; ?>" class="quiz-cta">
        <h3><i class="fas fa-circle-question me-2"></i>Validez vos connaissances</h3>
        <p>Testez ce que vous avez appris avec notre quiz interactif</p>
      </a>
      <?php endif; ?>

    </div>

    <div class="col-lg-4">
      <div class="progress-sidebar">
        <div class="card-module">
          <h5 style="color:var(--neon-cyan);" class="mb-4"><i class="fas fa-list-check me-2"></i>Plan du module</h5>
          <?php if (!empty($module['video_url'])): ?>
          <div class="lesson-step active">
            <div class="step-number"><i class="fas fa-play" style="font-size:.6rem;"></i></div>
            <div class="step-label"><strong>Introduction vidéo</strong><small>Vidéo de présentation</small></div>
          </div>
          <?php endif; ?>
          <?php foreach ($chapters as $index => $chapter): ?>
          <div class="lesson-step">
            <div class="step-number"><?php echo $index + 1; ?></div>
            <div class="step-label">
              <strong><?php echo htmlspecialchars($chapter['title']); ?></strong>
              <?php
              $sideDesc = $hasDescription ? col($chapter,'description') : '';
              if (!$sideDesc) $sideDesc = 'Chapitre ' . ($index + 1);
              $sideDesc = mb_strlen($sideDesc) > 55 ? mb_substr($sideDesc,0,55).'…' : $sideDesc;
              ?>
              <small><?php echo htmlspecialchars($sideDesc); ?></small>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (!empty($module['quiz_enabled'])): ?>
          <div class="lesson-step">
            <div class="step-number" style="background:rgba(0,212,255,.15);color:var(--neon-cyan);">Q</div>
            <div class="step-label"><strong>Quiz d'évaluation</strong><small>Testez vos connaissances</small></div>
          </div>
          <?php endif; ?>
        </div>

        <div class="card-module">
          <h5 style="color:var(--neon-cyan);" class="mb-4"><i class="fas fa-circle-info me-2"></i>Informations</h5>
          <div class="info-item"><i class="fas fa-clock"></i><span>Durée : <strong style="color:#e2e8f0;"><?php echo intval($module['duration'] ?? 0); ?> min</strong></span></div>
          <div class="info-item"><i class="fas fa-tag"></i><span>Catégorie : <strong style="color:#e2e8f0;"><?php echo htmlspecialchars($module['category'] ?? ''); ?></strong></span></div>
          <?php if (!empty($chapters)): ?>
          <div class="info-item"><i class="fas fa-book"></i><span><?php echo count($chapters); ?> chapitre<?php echo count($chapters)>1?'s':''; ?></span></div>
          <?php endif; ?>
          <?php if (!empty($module['quiz_enabled'])): ?>
          <div class="info-item" style="color:var(--neon-cyan);"><i class="fas fa-circle-check"></i><span>Quiz disponible</span></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
  document.getElementById('navToggler').addEventListener('click', function () {
    document.getElementById('navMenu').classList.toggle('active');
  });
  <?php if ($is_logged_in): ?>
  (function () {
    const avatar   = document.getElementById('userAvatar');
    const dropdown = document.getElementById('userDropdown');
    avatar.addEventListener('click', function (e) { e.stopPropagation(); dropdown.classList.toggle('show'); });
    document.addEventListener('click', function () { dropdown.classList.remove('show'); });
  })();
  <?php endif; ?>
</script>
</body>
</html>