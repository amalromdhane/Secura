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
require_once 'includes/auth.php';

$modules = [];
$db_error = '';
try {
    $pdo = getDBConnection('cyber');
    $stmt = $pdo->query(
        'SELECT id, title, description, category, duration, image, page, quiz_page, video_url, quiz_enabled, active
         FROM modules WHERE active = 1 ORDER BY id DESC'
    );
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $db_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tous les Modules – Secura</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/cyberaware.css">
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
      --cyber-glow: 0 0 20px rgba(13, 110, 253, 0.4);
      --text-primary: #333333;
      --text-secondary: #666666;
      --neon-cyan: #00d4ff;
      --glow-cyan: 0 0 20px rgba(0, 212, 255, 0.4);
    }

    *, *::before, *::after {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
      color: var(--text-primary);
      min-height: 100vh;
      margin: 0;
      position: relative;
      overflow-x: hidden;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background:
        radial-gradient(circle at 20% 80%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(99, 102, 241, 0.04) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(16, 185, 129, 0.03) 0%, transparent 50%);
      pointer-events: none;
      z-index: 0;
    }

    /* Navbar */
    .navbar {
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(25px);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.25);
      color: #1e293b;
      padding: 1rem 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2.5rem;
      border-radius: 20px;
      position: relative;
      z-index: 10;
      transition: all 0.3s ease;
    }
    .navbar:hover {
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }
    .navbar::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 1px;
      background: linear-gradient(90deg,
        transparent 0%,
        rgba(59, 130, 246, 0.3) 50%,
        transparent 100%);
    }
    .navbar h1 {
      font-size: 1.2rem;
      font-weight: 600;
      margin: 0;
      background: linear-gradient(135deg, #3b82f6, #334155);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      letter-spacing: -0.025em;
    }

    /* ── Hero ── */
    .modules-hero {
      background: linear-gradient(135deg, rgba(30,58,138,0.95) 0%, rgba(30,64,175,0.9) 25%, rgba(16,185,129,0.85) 50%, rgba(245,158,11,0.9) 75%, rgba(239,68,68,0.95) 100%);
      padding: 100px 0 80px;
      text-align: center;
      position: relative;
      overflow: hidden;
      animation: fadeInUp 1s ease-out;
    }
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .modules-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(circle at 20% 50%, rgba(59,130,246,0.2) 0%, transparent 50%),
        radial-gradient(circle at 50% 20%, rgba(16,185,129,0.18) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(245,158,11,0.15) 0%, transparent 50%),
        radial-gradient(circle at 40% 80%, rgba(239,68,68,0.12) 0%, transparent 50%);
      pointer-events: none;
      animation: float 6s ease-in-out infinite;
    }
    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
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
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 40px;
      justify-content: center;
      animation: fadeIn 0.8s ease-out 0.4s both;
    }
    .filter-btn {
      background: rgba(255,255,255,0.85);
      border: 1px solid rgba(255,255,255,0.3);
      color: #6b7280;
      padding: 10px 24px;
      border-radius: 50px;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      font-weight: 500;
      backdrop-filter: blur(10px);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    .filter-btn:hover {
      background: rgba(30, 64, 175, 0.1);
      border-color: #1e40af;
      color: #1e40af;
      transform: translateY(-2px);
      box-shadow: 0 4px 20px rgba(30, 64, 175, 0.2);
    }
    .filter-btn.active {
      background: linear-gradient(135deg, #1e40af, #3b82f6);
      border-color: #1e40af;
      color: #fff;
      box-shadow: 0 4px 20px rgba(30, 64, 175, 0.3);
    }

    /* ── Module grid ── */
    .modules-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
      gap: 32px;
      opacity: 1;
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    /* ── Module Card Modern & Elegant ── */
    .module-card {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      flex-direction: column;
      position: relative;
      box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(203, 213, 225, 0.3);
    }

    .module-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 25px 40px -12px rgba(0, 0, 0, 0.2);
      border-color: rgba(59, 130, 246, 0.3);
    }

    /* Card Image Container */
    .card-image-container {
      position: relative;
      height: 220px;
      overflow: hidden;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .module-card > .module-thumb,
    .module-card > .module-thumb-placeholder {
      display: block;
      width: 100%;
      height: 200px;
      object-fit: cover;
    }
    .module-thumb {
      width: 100%;
      height: 200px;
      object-fit: cover;
      transition: transform 0.5s ease;
    }

    .module-card:hover .module-thumb {
      transform: scale(1.08);
    }

    .module-thumb-placeholder {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 4rem;
      color: rgba(255, 255, 255, 0.4);
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    /* Category Badge on Image */
    .category-badge {
      position: absolute;
      top: 16px;
      right: 16px;
      padding: 6px 14px;
      border-radius: 30px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: white;
      backdrop-filter: blur(8px);
      background: rgba(0, 0, 0, 0.6);
      z-index: 2;
      transition: all 0.3s ease;
    }

    .module-card:hover .category-badge {
      background: rgba(0, 0, 0, 0.8);
      transform: scale(1.05);
    }

    /* Overlay gradient on image */
    .card-image-container::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 60px;
      background: linear-gradient(to top, rgba(0,0,0,0.3), transparent);
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .module-card:hover .card-image-container::after {
      opacity: 1;
    }

    /* Card Body */
    .module-body {
      padding: 24px;
      flex: 1;
      display: flex;
      flex-direction: column;
      background: white;
    }

    /* Difficulty/Length indicator */
    .module-meta {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 16px;
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      font-weight: 500;
      padding: 4px 10px;
      background: #f1f5f9;
      border-radius: 20px;
      color: #475569;
    }

    .meta-item i {
      font-size: 11px;
      color: #3b82f6;
    }

    .module-title {
      font-size: 1.2rem;
      font-weight: 700;
      color: #0f172a;
      margin-bottom: 12px;
      line-height: 1.4;
      transition: color 0.3s ease;
    }

    .module-card:hover .module-title {
      color: #2563eb;
    }

    .module-desc {
      color: #64748b;
      font-size: 0.85rem;
      line-height: 1.6;
      margin-bottom: 20px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    /* Module Actions */
    .module-actions {
      display: flex;
      gap: 12px;
      margin-top: auto;
    }

    .btn-access, .btn-quiz {
      flex: 1;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px 16px;
      border-radius: 12px;
      font-size: 0.85rem;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
      border: none;
      font-family: 'Inter', sans-serif;
    }

    .btn-access {
      background: linear-gradient(135deg, #1e40af, #1e3a8a);
      color: white;
      position: relative;
      overflow: hidden;
    }

    .btn-access::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s ease;
    }

    .btn-access:hover::before {
      left: 100%;
    }

    .btn-access:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(30, 64, 175, 0.35);
    }

    .btn-quiz {
      background: #f8fafc;
      color: #059669;
      border: 1px solid #d1fae5;
    }

    .btn-quiz:hover {
      background: #ecfdf5;
      border-color: #10b981;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
    }

    /* Admin Ribbon */
    .admin-ribbon {
      position: absolute;
      top: 16px;
      left: 16px;
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      z-index: 2;
      backdrop-filter: blur(4px);
    }

    /* Admin Toolbar */
    .admin-toolbar {
      display: flex;
      gap: 8px;
      padding: 12px 24px;
      border-top: 1px solid #e2e8f0;
      background: #fafcff;
    }

    .btn-admin {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      border: none;
      border-radius: 10px;
      padding: 8px 12px;
      font-size: 11px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      font-family: 'Inter', sans-serif;
    }

    .btn-admin-edit {
      background: #fef3c7;
      color: #d97706;
    }

    .btn-admin-edit:hover {
      background: #fde68a;
      transform: translateY(-1px);
    }

    .btn-admin-delete {
      background: #fee2e2;
      color: #dc2626;
    }

    .btn-admin-delete:hover {
      background: #fecaca;
      transform: translateY(-1px);
    }

    /* Empty State */
    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 80px 20px;
      color: #495057;
    }
    .empty-state i { font-size: 4rem; margin-bottom: 20px; display: block; }

    /* Toast Notification */
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

    /* Delete Modal */
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

    /* Responsive */
    @media (max-width: 640px) {
      .modules-grid { grid-template-columns: 1fr; }
    }

    /* User Avatar & Dropdown */
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

    /* Sidebar (unchanged) */
    .sidebar {
      width: 250px;
      background: var(--cyber-card);
      background-image: linear-gradient(135deg, rgba(30,58,138,0.3) 0%, rgba(30,64,175,0.25) 25%, rgba(16,185,129,0.2) 50%, rgba(245,158,11,0.25) 75%, rgba(239,68,68,0.3) 100%);
      border-right: 1px solid rgba(0, 0, 0, 0.1);
      height: 100vh;
      position: fixed;
      left: 0;
      top: 0;
      padding: 20px;
      box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
      z-index: 100;
    }
    .sidebar-header {
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }
    .sidebar-header h2 {
      color: var(--text-primary);
      font-size: 18px;
      margin: 0;
    }
    .sidebar-menu {
      list-style: none;
      padding: 0;
    }
    .sidebar-menu li {
      margin-bottom: 10px;
    }
    .sidebar-menu a {
      display: flex;
      align-items: center;
      padding: 12px 15px;
      color: var(--text-secondary);
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.3s ease;
      font-weight: 500;
    }
    .sidebar-menu a:hover, .sidebar-menu a.active {
      background: rgba(0, 0, 0, 0.1);
      color: var(--text-primary);
      border-left: 3px solid var(--cyber-primary);
    }
    .sidebar-menu a i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }

    /* Main content */
    .main-content {
      margin-left: 250px;
      flex: 1;
      padding: 20px;
      min-height: 100vh;
    }

    /* Search bar */
    .search-bar {
      margin-bottom: 30px;
      display: flex;
      justify-content: center;
      animation: fadeIn 0.8s ease-out 0.3s both;
    }
    .search-bar .form-input {
      max-width: 450px;
      width: 100%;
      padding: 16px 24px;
      border: 2px solid rgba(203, 213, 225, 0.5);
      border-radius: 50px;
      font-size: 16px;
      background: rgba(255, 255, 255, 0.95);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
      font-family: 'Inter', sans-serif;
    }
    .search-bar .form-input:focus {
      outline: none;
      border-color: #1e40af;
      box-shadow: 0 0 0 4px rgba(30, 64, 175, 0.1), 0 8px 30px rgba(0, 0, 0, 0.08);
      background: rgba(255, 255, 255, 1);
    }
    .search-bar .form-input::placeholder {
      color: #9ca3af;
      font-weight: 400;
    }
  </style>
</head>
<body>

  <!-- Sidebar (unchanged) -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <h2>🔐 Secura</h2>
    </div>
    <ul class="sidebar-menu">
      <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
      <li><a href="all_modules.php" class="active"><i class="fas fa-layer-group"></i> Modules</a></li>
      <li><a href="profil.php"><i class="fas fa-cog"></i> Paramètres</a></li>
      <li><a href="admin_users.php"><i class="fas fa-users"></i> Utilisateurs</a></li>
    </ul>
  </aside>

  <!-- Main Content -->
  <div class="main-content">

    <!-- Navbar -->
    <nav class="navbar">
      <h1><i class="fas fa-graduation-cap"></i> Catalogue de Formation</h1>
      <div class="user-info">
        <img src="<?php echo !empty($_SESSION['user_avatar']) ? htmlspecialchars($_SESSION['user_avatar']) : 'assets/images/default-avatar.svg'; ?>"
             alt="Avatar" class="user-avatar" id="userAvatar">
        <div class="user-dropdown" id="userDropdown">
          <a href="profil.php" class="user-dropdown-item">
            <i class="fas fa-user"></i> Mon Profil
          </a>
          <?php if ($user_role === 'admin'): ?>
          <a href="admin_dashboard.php" class="user-dropdown-item">
            <i class="fas fa-tachometer-alt"></i> Dashboard
          </a>
          <a href="admin_users.php" class="user-dropdown-item">
            <i class="fas fa-users"></i> Gestion Utilisateurs
          </a>
          <?php endif; ?>
          <a href="login.php?action=logout" class="user-dropdown-item">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
          </a>
        </div>
      </div>
    </nav>

    <!-- Content -->
    <div class="container" style="padding: 50px 20px 80px;">

      <!-- Search bar -->
      <div class="search-bar" style="margin-bottom: 20px; display: flex; justify-content: center;">
        <input type="text" id="moduleSearch" placeholder="🔍 Rechercher un module par titre, description ou catégorie..." class="form-input" style="max-width: 500px; width: 100%; padding: 14px 20px; border: 2px solid #e2e8f0; border-radius: 40px; font-size: 15px; transition: all 0.3s;">
      </div>

      <!-- Filter bar -->
      <div class="filter-bar" id="filterBar">
        <button class="filter-btn active" data-category="all">Tous</button>
        <!-- categories injected by JS -->
      </div>

      <?php if ($db_error !== ''): ?>
      <div class="empty-state" style="color:#dc3545;">
        <i class="fas fa-exclamation-triangle"></i>
        <h3>Erreur base de données</h3>
        <p><?php echo htmlspecialchars($db_error); ?></p>
      </div>
      <?php else: ?>
      <!-- Modules grid -->
      <div class="modules-grid" id="modulesGrid">
        <div class="empty-state">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Chargement des modules…</p>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Delete confirmation modal -->
    <div class="modal-overlay" id="deleteModal">
      <div class="modal-box">
        <h3><i class="fas fa-trash-alt me-2"></i> Supprimer le module</h3>
        <p>Cette action est irréversible. Êtes-vous sûr de vouloir supprimer ce module ?</p>
        <div class="modal-btns">
          <button class="btn-modal-cancel" id="cancelDelete">Annuler</button>
          <button class="btn-modal-confirm" id="confirmDelete">Supprimer</button>
        </div>
      </div>
    </div>

  </div>

  <script>
  window.isAdmin = <?php echo json_encode($user_role === 'admin'); ?>;
  window.isLoggedIn = <?php echo json_encode($is_logged_in); ?>;
  window.modulesData = <?php echo json_encode($modules, JSON_UNESCAPED_UNICODE); ?>;
  </script>
  <?php if ($db_error === ''): ?>
  <script src="js/modules.js"></script>
  <?php endif; ?>
  
  <script>
  // User dropdown toggle
  const userAvatar = document.getElementById('userAvatar');
  const userDropdown = document.getElementById('userDropdown');

  if (userAvatar && userDropdown) {
    userAvatar.addEventListener('click', (e) => {
      e.stopPropagation();
      userDropdown.classList.toggle('open');
    });

    document.addEventListener('click', () => {
      userDropdown.classList.remove('open');
    });
  }
  </script>
</body>
</html>