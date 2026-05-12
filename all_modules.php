<?php
/**
 * Dynamic Modules Display Page
 * Shows all modules from database in passwords.html style
 * Admin-created modules appear seamlessly alongside static ones
 */
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    $is_logged_in = false;
    $user_role = '';
} else {
    $is_logged_in = true;
    $user_role = $_SESSION['user_role'] ?? '';
}

require_once 'includes/config.php';

$pdo = getDBConnection('cyber');
$stmt = $pdo->query("SELECT id, title, description, category, duration, image, page, quiz_page, video_url, active FROM modules WHERE active = 1 ORDER BY id DESC");
$modules = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tous les Modules – Secura</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/cyberaware.css">
  <style>
    /* ── Hero ── */
    .modules-hero {
      background: linear-gradient(135deg, rgba(30,58,138,0.9) 0%, rgba(30,64,175,0.85) 25%, rgba(16,185,129,0.8) 50%, rgba(245,158,11,0.85) 75%, rgba(239,68,68,0.9) 100%);
      padding: 90px 0 70px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .modules-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(circle at 20% 50%, rgba(59,130,246,0.15) 0%, transparent 40%),
        radial-gradient(circle at 50% 20%, rgba(16,185,129,0.12) 0%, transparent 40%),
        radial-gradient(circle at 80% 70%, rgba(245,158,11,0.1) 0%, transparent 40%);
      pointer-events: none;
    }
    .modules-hero h1 {
      font-size: clamp(2rem, 5vw, 3.2rem);
      font-weight: 800;
      background: linear-gradient(135deg, #0d6efd 0%, #00d4ff 100%);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      margin-bottom: 18px;
    }
    .modules-hero p {
      font-size: 1.1rem;
      color: #adb5bd;
      max-width: 680px;
      margin: 0 auto 30px;
    }
    .hero-stats {
      display: flex;
      justify-content: center;
      gap: 40px;
      flex-wrap: wrap;
      margin-top: 30px;
    }
    .hero-stat {
      text-align: center;
    }
    .hero-stat .num {
      font-size: 2rem;
      font-weight: 700;
      color: #1e40af;
    }
    .hero-stat .lbl {
      font-size: 0.8rem;
      color: #6b7280;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    /* ── Filter bar ── */
    .filter-bar {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 36px;
      justify-content: center;
    }
    .filter-btn {
      background: rgba(255,255,255,0.8);
      border: 1px solid #e5e7eb;
      color: #6b7280;
      padding: 8px 20px;
      border-radius: 30px;
      font-size: 13px;
      cursor: pointer;
      transition: all 0.25s;
      font-weight: 500;
    }
    .filter-btn:hover,
    .filter-btn.active {
      background: #1e40af;
      border-color: #1e40af;
      color: #fff;
    }

    /* ── Module grid ── */
    .modules-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 28px;
    }

    /* ── Module card (passwords.html style) ── */
    .module-card {
      background: rgba(255, 255, 255, 0.95);
      border: 2px solid transparent;
      border-radius: 18px;
      overflow: hidden;
      transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
      display: flex;
      flex-direction: column;
      position: relative;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .module-card:hover {
      transform: translateY(-8px);
      border-color: #1e40af;
      box-shadow: 0 12px 40px rgba(30, 64, 175, 0.25);
    }

    /* Category-specific colors */
    .module-card[data-category="Sécurité"] {
      border-left: 4px solid #1e40af;
      background: linear-gradient(135deg, rgba(30, 64, 175, 0.05) 0%, rgba(255, 255, 255, 0.95) 100%);
    }
    .module-card[data-category="Phishing"] {
      border-left: 4px solid #dc2626;
      background: linear-gradient(135deg, rgba(220, 38, 38, 0.05) 0%, rgba(255, 255, 255, 0.95) 100%);
    }
    .module-card[data-category="Ransomware"] {
      border-left: 4px solid #d97706;
      background: linear-gradient(135deg, rgba(217, 119, 6, 0.05) 0%, rgba(255, 255, 255, 0.95) 100%);
    }
    .module-card[data-category="Cloud"] {
      border-left: 4px solid #059669;
      background: linear-gradient(135deg, rgba(5, 150, 105, 0.05) 0%, rgba(255, 255, 255, 0.95) 100%);
    }
    .module-card[data-category="Mot de passe"] {
      border-left: 4px solid #7c3aed;
      background: linear-gradient(135deg, rgba(124, 58, 237, 0.05) 0%, rgba(255, 255, 255, 0.95) 100%);
    }

    .module-thumb {
      width: 100%;
      height: 190px;
      object-fit: cover;
      display: block;
    }
    .module-thumb-placeholder {
      width: 100%;
      height: 190px;
      background: linear-gradient(135deg, #0d6efd22, #00d4ff11);
      display: flex;
      align-items: center;
      justify-content: center;
      color: rgba(255,255,255,0.15);
      font-size: 3.5rem;
    }

    .module-body {
      padding: 24px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .module-tag {
      display: inline-block;
      color: #fff;
      border: 1px solid transparent;
      padding: 4px 14px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 14px;
      align-self: flex-start;
    }
    .module-tag[data-category="Sécurité"] { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
    .module-tag[data-category="Phishing"] { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .module-tag[data-category="Ransomware"] { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .module-tag[data-category="Cloud"] { background: linear-gradient(135deg, #10b981, #059669); }
    .module-tag[data-category="Mot de passe"] { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    .module-tag { background: linear-gradient(135deg, #6b7280, #4b5563); } /* default */

    .module-title {
      color: #1f2937;
      font-size: 1.25rem;
      font-weight: 700;
      margin-bottom: 10px;
      line-height: 1.35;
    }

    .module-desc {
      color: #6b7280;
      font-size: 0.9rem;
      line-height: 1.65;
      flex: 1;
      margin-bottom: 18px;
    }

    .module-meta {
      display: flex;
      align-items: center;
      gap: 18px;
      font-size: 0.82rem;
      color: #1e40af;
      margin-bottom: 20px;
    }
    .module-meta i { font-size: 0.85rem; }

    .module-actions {
      display: flex;
      gap: 10px;
      flex-direction: column;
    }

    .btn-access {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background: linear-gradient(135deg, #1e40af, #1e3a8a);
      color: #fff;
      text-decoration: none;
      border: none;
      border-radius: 10px;
      padding: 12px 20px;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.25s;
      width: 100%;
    }
    .btn-access:hover {
      opacity: 0.88;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(30, 64, 175, 0.4);
      color: #fff;
    }
    .btn-access:disabled {
      opacity: 0.4;
      cursor: not-allowed;
      transform: none;
    }

    .btn-quiz {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background: rgba(16, 185, 129, 0.1);
      color: #059669;
      text-decoration: none;
      border: 1px solid #10b981;
      border-radius: 10px;
      padding: 10px 20px;
      font-weight: 500;
      font-size: 0.85rem;
      cursor: pointer;
      transition: all 0.25s;
      width: 100%;
    }
    .btn-quiz:hover {
      background: rgba(16, 185, 129, 0.2);
      border-color: #059669;
      color: #047857;
    }

    /* ── Admin controls ── */
    .admin-ribbon {
      position: absolute;
      top: 12px;
      right: 12px;
      background: #28a745;
      color: #fff;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }
    .admin-toolbar {
      display: flex;
      gap: 8px;
      padding: 14px 24px;
      border-top: 1px solid rgba(255,255,255,0.06);
      background: rgba(0,0,0,0.15);
    }
    .btn-admin {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      border: none;
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }
    .btn-admin-edit {
      background: rgba(255,193,7,0.12);
      color: #ffc107;
      border: 1px solid rgba(255,193,7,0.25);
    }
    .btn-admin-edit:hover { background: rgba(255,193,7,0.22); }
    .btn-admin-delete {
      background: rgba(220,53,69,0.12);
      color: #dc3545;
      border: 1px solid rgba(220,53,69,0.25);
    }
    .btn-admin-delete:hover { background: rgba(220,53,69,0.22); }

    /* ── Empty state ── */
    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 80px 20px;
      color: #495057;
    }
    .empty-state i { font-size: 4rem; margin-bottom: 20px; display: block; }

    /* ── Notification toast ── */
    .toast-notification {
      position: fixed;
      bottom: 30px;
      right: 30px;
      background: #1e2a3a;
      border: 1px solid rgba(13,110,253,0.4);
      color: #fff;
      padding: 14px 22px;
      border-radius: 12px;
      font-size: 14px;
      font-weight: 500;
      z-index: 9999;
      box-shadow: 0 8px 30px rgba(0,0,0,0.4);
      transform: translateY(0);
      opacity: 1;
      transition: all 0.4s ease;
    }
    .toast-notification.toast-success { border-color: rgba(40,167,69,0.5); }
    .toast-notification.toast-error { border-color: rgba(220,53,69,0.5); }
    .toast-notification.toast-hide { opacity: 0; transform: translateY(20px); }

    /* ── Delete confirm modal ── */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.7);
      z-index: 1000;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(4px);
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
      background: #121824;
      border: 1px solid rgba(220,53,69,0.3);
      border-radius: 16px;
      padding: 36px 32px;
      max-width: 420px;
      width: 90%;
      text-align: center;
    }
    .modal-box h3 { color: #dc3545; margin-bottom: 12px; }
    .modal-box p { color: #8899aa; margin-bottom: 24px; }
    .modal-btns { display: flex; gap: 12px; justify-content: center; }
    .btn-modal-cancel {
      background: rgba(255,255,255,0.08);
      color: #adb5bd;
      border: none;
      padding: 10px 28px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: background 0.2s;
    }
    .btn-modal-cancel:hover { background: rgba(255,255,255,0.14); }
    .btn-modal-confirm {
      background: #dc3545;
      color: #fff;
      border: none;
      padding: 10px 28px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: background 0.2s;
    }
    .btn-modal-confirm:hover { background: #b02a37; }

    /* ── Responsive ── */
    @media (max-width: 640px) {
      .modules-grid { grid-template-columns: 1fr; }
    }

    /* ── User Avatar & Dropdown ── */
    .user-info {
      display: flex;
      align-items: center;
      position: relative;
    }

    .user-avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      border: 2px solid #1e40af;
      cursor: pointer;
      transition: all 0.3s ease;
      object-fit: cover;
    }

    .user-avatar:hover {
      border-color: #1e3a8a;
      box-shadow: 0 0 20px rgba(30, 64, 175, 0.4);
      transform: scale(1.05);
    }

    .user-dropdown {
      position: absolute;
      top: 100%;
      right: 0;
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      min-width: 180px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      backdrop-filter: blur(10px);
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s ease;
      z-index: 9999;
    }

    .user-dropdown.open {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .user-dropdown-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 18px;
      color: #374151;
      text-decoration: none;
      border-bottom: 1px solid #e5e7eb;
      transition: all 0.3s ease;
      font-weight: 500;
    }

    .user-dropdown-item:hover {
      background: #f3f4f6;
      color: #1f2937;
      transform: translateX(5px);
    }

    .user-dropdown-item:last-child {
      border-bottom: none;
    }

    .user-dropdown-item i {
      width: 16px;
    }

    /* Navbar adjustments for user info */
    .nav-container {
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 100%;
    }

    @media (max-width: 768px) {
      .user-info {
        margin-left: auto;
      }
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <div class="nav-container">
    <a class="nav-brand" href="index.php">
      <i class="fas fa-shield-alt"></i>
      Sec<span>ura</span>
    </a>
    <button class="nav-toggler" id="navToggler">
      <i class="fas fa-bars"></i>
    </button>
    <ul class="nav-menu" id="navMenu">
      <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home"></i> Accueil</a></li>
      <li class="nav-item"><a class="nav-link" href="index.php#modules"><i class="fas fa-layer-group"></i> Modules</a></li>
      <li class="nav-item"><a class="nav-link" href="index.html#about"><i class="fas fa-info-circle"></i> À propos</a></li>
      <?php if ($is_logged_in): ?>
        <?php if ($user_role === 'admin'): ?>
          <li class="nav-item"><a class="nav-link" href="admin_dashboard.php"><i class="fas fa-cog"></i> Admin</a></li>
        <?php endif; ?>
      <?php else: ?>
        <li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Connexion</a></li>
      <?php endif; ?>
    </ul>
    <?php if ($is_logged_in): ?>
      <div class="user-info">
        <img src="<?php echo !empty($_SESSION['user_avatar']) ? htmlspecialchars($_SESSION['user_avatar']) : 'assets/images/default-avatar.svg'; ?>"
             alt="Avatar" class="user-avatar" id="userAvatar">
        <div class="user-dropdown" id="userDropdown">
          <a href="profil.php" class="user-dropdown-item">
            <i class="fas fa-user"></i> Mon Profil
          </a>
          <a href="login.php?action=logout" class="user-dropdown-item">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</nav>

<!-- Hero -->
<section class="modules-hero">
  <div class="container">
    <h1><i class="fas fa-graduation-cap"></i> Catalogue de Formation</h1>
    <p>Explorez notre bibliothèque complète de modules de cybersécurité. Des contenus pratiques, interactifs et constamment mis à jour.</p>
    <div class="hero-stats">
      <div class="hero-stat">
        <div class="num" id="totalCount">–</div>
        <div class="lbl">Modules</div>
      </div>
      <div class="hero-stat">
        <div class="num" id="categoryCount">–</div>
        <div class="lbl">Catégories</div>
      </div>
      <div class="hero-stat">
        <div class="num" id="totalDuration">–</div>
        <div class="lbl">Minutes de contenu</div>
      </div>
    </div>
  </div>
</section>

<!-- Content -->
<div class="container" style="padding: 50px 20px 80px;">

  <!-- Filter bar -->
  <div class="filter-bar" id="filterBar">
    <button class="filter-btn active" data-category="all">Tous</button>
    <!-- categories injected by JS -->
  </div>

  <!-- Modules grid -->
  <div class="modules-grid" id="modulesGrid">
    <div class="empty-state">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Chargement des modules…</p>
    </div>
  </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal-box">
    <h3><i class="fas fa-trash-alt me-2"></i>Supprimer le module</h3>
    <p>Cette action est irréversible. Êtes-vous sûr de vouloir supprimer ce module ?</p>
    <div class="modal-btns">
      <button class="btn-modal-cancel" id="cancelDelete">Annuler</button>
      <button class="btn-modal-confirm" id="confirmDelete">Supprimer</button>
    </div>
  </div>
</div>

<!-- Footer -->
<footer class="cyber-footer">
  <div class="container">
    <div class="row">
      <div class="col-lg-8" style="margin:0 auto;">
        <div class="d-flex justify-content-center mb-4" style="gap:1.5rem;">
          <div style="width:50px;height:50px;background:rgba(13,110,253,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-shield-lock" style="font-size:1.8rem;color:var(--cyber-primary);"></i>
          </div>
          <div style="width:50px;height:50px;background:rgba(32,201,151,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-lock" style="font-size:1.8rem;color:var(--cyber-success);"></i>
          </div>
        </div>
        <h3 class="text-center mb-3"><span style="color:var(--cyber-accent);">Secura</span></h3>
        <p class="text-center text-muted mb-4">Plateforme de sensibilisation à la cybersécurité</p>
        <hr style="opacity:0.25;margin:2rem 0;">
        <p class="text-center text-muted small mb-0">© 2026 Secura – Plateforme de Sensibilisation à la Cybersécurité</p>
      </div>
    </div>
  </div>
</footer>

<script src="assets/js/modules.js"></script>
</body>
</html>