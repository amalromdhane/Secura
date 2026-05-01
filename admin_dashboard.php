<?php
/**
 * Admin Dashboard – Secura
 * Module management: event delegation, JSON.stringify, no onclick attributes
 */
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php'); exit();
}
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: index.html'); exit();
}

$username   = $_SESSION['username']   ?? 'Admin';
$user_email = $_SESSION['user_email'] ?? '';

// Pre-fill edit modal if redirected from all_modules.php
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard – Secura</title>
  <link rel="stylesheet" href="css/style.css">
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
      --cyber-border: rgba(13, 110, 253, 0.2);
      --cyber-gradient: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
      --cyber-glow: 0 0 20px rgba(13, 110, 253, 0.4);
      --text-primary: #f8f9fa;
      --text-secondary: #adb5bd;
      --neon-cyan: #00d4ff;
      --glow-cyan: 0 0 20px rgba(0, 212, 255, 0.4);
    }
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
      background: var(--cyber-dark);
      color: var(--text-primary);
      min-height: 100vh;
      margin: 0;
      display: flex;
      background-image:
        radial-gradient(circle at 10% 20%, rgba(13, 110, 253, 0.03) 0%, transparent 20%),
        radial-gradient(circle at 90% 80%, rgba(111, 66, 193, 0.03) 0%, transparent 20%);
    }

    /* Navbar */
    .navbar {
      background: linear-gradient(135deg, rgba(30, 36, 54, 0.9) 0%, rgba(26, 31, 46, 0.9) 100%);
      backdrop-filter: blur(12px);
      box-shadow: 0 2px 10px rgba(13, 110, 253, 0.2);
      color:var(--text-primary);
      padding:15px 30px;
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-bottom: 20px;
      border-radius: 12px;
      border: 1px solid rgba(0, 212, 255, 0.3);
    }
    .navbar h1 { font-size:20px; color: var(--cyber-accent); margin: 0; }
    .user-info { display:flex; align-items:center; gap:14px; }
    .user-info span { opacity:.9; font-size:14px; color: var(--text-secondary); }
    .logout-btn {
      background: rgba(255,255,255,.1);
      color: var(--text-primary);
      padding:8px 18px;
      border:1px solid rgba(255,255,255,.2);
      border-radius:6px;
      cursor:pointer;
      text-decoration:none;
      font-size:14px;
      transition:all .25s;
    }
    .logout-btn:hover { background: rgba(0, 212, 255, 0.1); border-color: var(--cyber-accent); }

    /* Sidebar */
    .sidebar {
      width: 250px;
      background: linear-gradient(180deg, rgba(15, 24, 36, 0.95) 0%, rgba(20, 24, 36, 0.95) 100%);
      backdrop-filter: blur(12px);
      border-right: 1px solid rgba(13, 110, 253, 0.3);
      height: 100vh;
      position: fixed;
      left: 0;
      top: 0;
      padding: 20px;
      box-shadow: 2px 0 20px rgba(13, 110, 253, 0.2);
      z-index: 100;
    }
    .sidebar-header {
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 1px solid rgba(13, 110, 253, 0.2);
    }
    .sidebar-header h2 {
      color: var(--cyber-accent);
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
      background: rgba(13, 110, 253, 0.1);
      color: var(--cyber-accent);
      border-left: 3px solid var(--cyber-accent);
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
    .container { max-width:1200px; margin:0 auto; padding:0; }

    /* Stats */
    .stats-grid {
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
      gap:18px;
      margin-bottom:28px;
    }
    .stat-card {
      background: rgba(26, 31, 46, 0.9);
      border: 1px solid rgba(13, 110, 253, 0.25);
      padding:22px 24px;
      border-radius:16px;
      backdrop-filter: blur(10px);
      transition: all 0.3s ease;
      box-shadow: 0 4px 20px rgba(13, 110, 253, 0.15);
      position: relative;
      overflow: hidden;
    }
    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: var(--cyber-gradient);
    }
    .stat-card:hover {
      transform: translateY(-5px);
      border-color: rgba(13, 110, 253, 0.5);
      box-shadow: 0 10px 30px rgba(13, 110, 253, 0.2);
    }
    .stat-card:nth-child(1)::before { background: var(--cyber-accent); }
    .stat-card:nth-child(2)::before { background: var(--cyber-success); }
    .stat-card:nth-child(3)::before { background: var(--cyber-warning); }
    .stat-card h3 { color:var(--text-secondary); font-size:13px; margin-bottom:8px; text-transform:uppercase; letter-spacing:.5px; }
    .stat-card .number { font-size:32px; font-weight:700; color: var(--cyber-accent); }

    /* Dashboard section */
    .dash-section {
      background: linear-gradient(135deg, rgba(26, 31, 46, 0.9) 0%, rgba(22, 27, 40, 0.9) 100%);
      border: 1px solid rgba(13, 110, 253, 0.25);
      border-radius:16px;
      backdrop-filter: blur(10px);
      padding:24px;
      margin-bottom:22px;
      transition: all 0.3s ease;
      position: relative;
    }
    .dash-section::after {
      content: '';
      position: absolute;
      top: -1px;
      left: -1px;
      right: -1px;
      height: 2px;
      background: linear-gradient(90deg, transparent, var(--cyber-accent), transparent);
      border-radius: 16px 16px 0 0;
    }
    .dash-section:hover {
      border-color: rgba(13, 110, 253, 0.5);
      box-shadow: 0 10px 30px rgba(13, 110, 253, 0.2);
    }
    .dash-section h2 {
      font-size:18px;
      color:var(--text-primary);
      margin-bottom:20px;
      padding-bottom:12px;
      border-bottom:2px solid var(--cyber-accent);
    }

    /* Menu grid */
    .menu-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:14px; }
    .menu-item {
      background: rgba(18, 24, 38, 0.7);
      border: 1px solid rgba(13, 110, 253, 0.3);
      border-radius:12px;
      padding:20px;
      text-align:center;
      text-decoration:none;
      color:var(--text-primary);
      transition:all .25s;
      display:block;
      backdrop-filter: blur(5px);
    }
    .menu-item:hover {
      background: rgba(13, 110, 253, 0.1);
      border-color: var(--cyber-accent);
      transform:translateY(-3px);
      box-shadow: 0 6px 16px rgba(13, 110, 253, 0.2);
    }
    .menu-item .icon { font-size:30px; margin-bottom:8px; color: var(--cyber-primary); }
    .menu-item .label { font-weight:600; font-size:13px; }

    /* Alert */
    .alert { padding:14px 18px; border-radius:8px; margin-bottom:18px; font-size:14px; backdrop-filter: blur(5px); }
    .alert-success { background: rgba(32, 201, 151, 0.1); color: var(--cyber-success); border-left:4px solid var(--cyber-success); }

    /* Module list */
    .btn-add {
      display:inline-flex;
      align-items:center;
      gap:8px;
      background: var(--cyber-gradient);
      color:#fff;
      border:none;
      padding:11px 22px;
      border-radius:10px;
      cursor:pointer;
      font-size:14px;
      font-weight:600;
      margin-bottom:18px;
      transition:all .25s;
    }
    .btn-add:hover { transform:translateY(-2px); box-shadow: 0 6px 16px rgba(13, 110, 253, 0.4); }

    .list-container {
      border:1px solid rgba(13, 110, 253, 0.3);
      border-radius:12px;
      overflow:hidden;
      backdrop-filter: blur(10px);
    }
    .list-header {
      display:grid;
      grid-template-columns:2fr 1fr 80px 90px 1fr;
      background: var(--cyber-gradient);
      color:var(--text-primary);
      padding:13px 18px;
      font-size:13px;
      font-weight:600;
      gap:10px;
    }
    .module-row {
      display:grid;
      grid-template-columns:2fr 1fr 80px 90px 1fr;
      padding:14px 18px;
      background: rgba(18, 24, 38, 0.7);
      border-bottom:1px solid rgba(13, 110, 253, 0.15);
      align-items:center;
      gap:10px;
      transition:background .2s;
      position: relative;
    }
    .module-row:hover {
      background: rgba(13, 110, 253, 0.05);
      border-left: 3px solid var(--cyber-accent);
      padding-left: 15px;
    }
    .module-row:last-child { border-bottom:none; }
    .module-row:hover { background: rgba(13, 110, 253, 0.1); }

    .module-row .col-title { font-weight:600; font-size:14px; color:var(--text-primary); }
    .tag {
      display:inline-block;
      background: rgba(13, 110, 253, 0.15);
      color: var(--cyber-primary);
      border: 1px solid rgba(13, 110, 253, 0.3);
      padding:3px 10px;
      border-radius:20px;
      font-size:11px;
      font-weight:600;
      backdrop-filter: blur(5px);
    }
    .col-duration { text-align:center; color:var(--text-secondary); font-size:13px; }
    .col-status { text-align:center; }
    .badge {
      padding:4px 12px;
      border-radius:20px;
      font-size:11px;
      font-weight:600;
      backdrop-filter: blur(5px);
    }
    .badge-active   { background: rgba(32, 201, 151, 0.15); color: var(--cyber-success); border: 1px solid rgba(32, 201, 151, 0.3); }
    .badge-inactive { background: rgba(255, 71, 87, 0.15); color: var(--cyber-danger); border: 1px solid rgba(255, 71, 87, 0.3); }
    .col-actions { display:flex; gap:6px; justify-content:flex-end; }

    .btn-sm {
      padding:5px 12px;
      border:none;
      border-radius:6px;
      cursor:pointer;
      font-size:12px;
      font-weight:600;
      transition:all .2s;
      backdrop-filter: blur(5px);
    }
    .btn-edit    { background: rgba(255, 193, 7, 0.15); color: var(--cyber-warning); border: 1px solid rgba(255, 193, 7, 0.3); }
    .btn-edit:hover { background: rgba(255, 193, 7, 0.25); }
    .btn-toggle  { background: rgba(32, 201, 151, 0.15); color: var(--cyber-success); border: 1px solid rgba(32, 201, 151, 0.3); }
    .btn-toggle:hover { background: rgba(32, 201, 151, 0.25); }
    .btn-toggle.is-inactive { background: rgba(255, 71, 87, 0.15); color: var(--cyber-danger); border: 1px solid rgba(255, 71, 87, 0.3); }
    .btn-toggle.is-inactive:hover { background: rgba(255, 71, 87, 0.25); }
    .btn-del { background: rgba(255, 71, 87, 0.15); color: var(--cyber-danger); border: 1px solid rgba(255, 71, 87, 0.3); }
    .btn-del:hover { background: rgba(255, 71, 87, 0.25); }

    .list-empty { padding:40px; text-align:center; color:var(--text-secondary); font-style:italic; }
    .list-loading { padding:40px; text-align:center; color:#999; }

    /* Collapsible Add Form */
    .add-form-container {
      margin-top: 18px;
      background: rgba(18, 24, 38, 0.7);
      border: 1px solid rgba(13, 110, 253, 0.2);
      border-radius: 12px;
      padding: 20px;
      transition: all 0.3s ease;
      max-height: 70vh;
      overflow-y: auto;
    }
    .add-form {
      display: grid;
      gap: 15px;
    }
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }
    .checkbox-group {
      display: flex;
      align-items: center;
      padding: 10px 0;
    }
    .form-actions {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      margin-top: 15px;
    }

    /* Modal */
    .modal-backdrop {
      display:none;
      position:fixed;
      inset:0;
      background:rgba(0,0,0,.6);
      z-index:200;
      align-items:center;
      justify-content:center;
      backdrop-filter:blur(8px);
    }
    .modal-backdrop.open { display:flex; }
    .modal {
      background: rgba(26, 31, 46, 0.95);
      border: 1px solid rgba(13, 110, 253, 0.3);
      border-radius:16px;
      max-width:520px;
      width:92%;
      max-height:92vh;
      overflow-y:auto;
      box-shadow: 0 20px 60px rgba(13, 110, 253, 0.2);
      backdrop-filter: blur(15px);
    }
    .modal-head {
      display:flex;
      justify-content:space-between;
      align-items:center;
      padding:18px 22px;
      border-bottom:1px solid rgba(13, 110, 253, 0.3);
    }
    .modal-head h3 { font-size:18px; color:var(--text-primary); }
    .btn-close {
      background:none;
      border:none;
      font-size:26px;
      cursor:pointer;
      color:var(--text-secondary);
      line-height:1;
      transition:color .2s;
    }
    .btn-close:hover { color:var(--cyber-accent); }
    .modal-body { padding:22px; }
    .form-group { margin-bottom:18px; }
    .form-group label {
      display:block;
      margin-bottom:6px;
      font-weight:600;
      font-size:13px;
      color:var(--text-primary);
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
      width:100%;
      padding:10px 13px;
      background: rgba(18, 24, 38, 0.7);
      border:1px solid rgba(13, 110, 253, 0.3);
      border-radius:10px;
      color: var(--text-primary);
      font-size:14px;
      transition:all .3s ease;
      backdrop-filter: blur(5px);
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline:none;
      border-color: var(--cyber-accent);
      box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.15);
      background: rgba(18, 24, 38, 0.9);
    }
    .form-group small { color:#999; font-size:11px; margin-top:4px; display:block; }
    .checkbox-row {
      display:flex;
      align-items:center;
      gap:10px;
      font-weight:600;
      font-size:14px;
      color:var(--text-primary);
      cursor:pointer;
    }
    .checkbox-row input[type="checkbox"] {
      width:18px;
      height:18px;
      cursor:pointer;
      accent-color: var(--cyber-primary);
    }
    .modal-foot {
      display:flex;
      gap:10px;
      justify-content:flex-end;
      padding:16px 22px;
      border-top:1px solid rgba(13, 110, 253, 0.3);
    }
    .btn-cancel-form {
      background: rgba(18, 24, 38, 0.7);
      color: var(--text-secondary);
      border: 1px solid rgba(255, 255, 255, 0.1);
      padding:10px 22px;
      border-radius:10px;
      cursor:pointer;
      font-size:14px;
      font-weight:600;
      transition:all .2s;
      backdrop-filter: blur(5px);
    }
    .btn-cancel-form:hover { background: rgba(18, 24, 38, 0.9); }
    .btn-save {
      background: var(--cyber-gradient);
      color:#fff;
      border:none;
      padding:10px 24px;
      border-radius:10px;
      cursor:pointer;
      font-size:14px;
      font-weight:600;
      transition:all .25s;
    }
    .btn-save:hover { transform:translateY(-2px); box-shadow: 0 5px 16px rgba(13, 110, 253, 0.4); }

    /* Delete confirm modal */
    .modal-confirm { text-align:center; padding:32px; }
    .modal-confirm h3 { color: var(--cyber-danger); margin-bottom:12px; font-size:20px; }
    .modal-confirm p { color: var(--text-secondary); margin-bottom:24px; }
    .btn-del-confirm {
      background: rgba(255, 71, 87, 0.2);
      color: var(--cyber-danger);
      border: 1px solid rgba(255, 71, 87, 0.3);
      padding:10px 26px;
      border-radius:10px;
      cursor:pointer;
      font-size:14px;
      font-weight:600;
      transition:all .2s;
      backdrop-filter: blur(5px);
    }
    .btn-del-confirm:hover { background: rgba(255, 71, 87, 0.3); }

    /* Toast */
    .toast {
      position:fixed;
      top:20px;
      right:22px;
      z-index:9999;
      padding:13px 20px;
      border-radius:10px;
      font-size:14px;
      font-weight:500;
      color:#fff;
      box-shadow: 0 4px 20px rgba(13, 110, 253, 0.2);
      transition:all .35s;
      backdrop-filter: blur(10px);
    }
    .toast-success { background: rgba(32, 201, 151, 0.9); }
    .toast-error   { background: rgba(255, 71, 87, 0.9); }
    .toast-hide { opacity:0; transform:translateY(-12px); }

    /* Floating shapes */
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

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <h2>🔐 Secura</h2>
    </div>
    <ul class="sidebar-menu">
      <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
      <li><a href="all_modules.php"><i class="fas fa-layer-group"></i> Modules</a></li>
      <li><a href="pages/cloud.html"><i class="fas fa-cloud"></i> Cloud</a></li>
      <li><a href="pages/passwords.html"><i class="fas fa-lock"></i> Mots de passe</a></li>
      <li><a href="pages/phishing.html"><i class="fas fa-fish"></i> Phishing</a></li>
      <li><a href="pages/ransomware.html"><i class="fas fa-skull"></i> Ransomware</a></li>
      <li><a href="#"><i class="fas fa-cog"></i> Paramètres</a></li>
      <li><a href="#"><i class="fas fa-users"></i> Utilisateurs</a></li>
    </ul>
  </aside>

  <!-- Main Content -->
  <div class="main-content">
    <nav class="navbar">
      <h1>Admin Dashboard</h1>
      <div class="user-info">
        <span>Bienvenue, <?php echo htmlspecialchars($username); ?> (Admin)</span>
        <a href="login.php?action=logout" class="logout-btn">Déconnexion</a>
      </div>
    </nav>

<div class="container">

  <div class="alert alert-success">
    ✅ Connecté en tant qu'administrateur. Bonne gestion !
  </div>

  <!-- Stats -->
  <div class="stats-grid" id="statsGrid">
    <div class="stat-card"><h3>Total Modules</h3><div class="number" id="statTotal">…</div></div>
    <div class="stat-card"><h3>Modules Actifs</h3><div class="number" id="statActive">…</div></div>
    <div class="stat-card"><h3>Catégories</h3><div class="number" id="statCats">…</div></div>
  </div>



  <!-- Module management -->
  <div class="dash-section">
    <h2>📚 Gestion des Modules</h2>

    <button class="btn-add" id="btnToggleForm">➕ Nouveau Module</button>

    <!-- Collapsible Add Form -->
    <div id="addFormContainer" class="add-form-container" style="display: none;">
      <form id="addModuleForm" class="add-form">
        <div class="form-row">
          <div class="form-group">
            <label for="addTitle">Titre *</label>
            <input type="text" id="addTitle" required>
          </div>
          <div class="form-group">
            <label for="addCategory">Catégorie *</label>
            <select id="addCategory" required>
              <option value="">— Sélectionner —</option>
              <option>Sécurité</option>
              <option>Réseau</option>
              <option>Données</option>
              <option>Cloud</option>
              <option>IA</option>
              <option>Phishing</option>
              <option>Ransomware</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="addDuration">Durée (minutes)</label>
            <input type="number" id="addDuration" value="30" min="1" max="600">
          </div>
          <div class="form-group">
            <label for="addImage">URL de l'image</label>
            <input type="url" id="addImage" placeholder="https://…">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="addVideo">URL YouTube</label>
            <input type="url" id="addVideo" placeholder="https://www.youtube.com/watch?v=…">
          </div>
          <div class="form-group">
            <label for="addContent">Contenu additionnel</label>
            <textarea id="addContent" rows="3" placeholder="Contenu optionnel…"></textarea>
          </div>
        </div>
          <div class="form-group">
            <label for="addDescription">Description</label>
            <textarea id="addDescription" rows="2" placeholder="Description détaillée…"></textarea>
          </div>
        </div>
        <div class="form-row">
          <div class="checkbox-group">
            <label class="checkbox-row">
              <input type="checkbox" id="addQuizEnabled">
              <span>Quiz activé</span>
            </label>
          </div>
          <div class="checkbox-group">
            <label class="checkbox-row">
              <input type="checkbox" id="addActive" checked>
              <span>Module actif</span>
            </label>
          </div>
        </div>
        <div class="form-actions">
          <button type="button" class="btn" id="btnCancelAdd" style="background: rgba(18, 24, 38, 0.7); color: var(--text-secondary); border: 1px solid rgba(255, 255, 255, 0.1);">Annuler</button>
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>

    <div class="list-container">
      <div class="list-header">
        <span>Titre</span>
        <span>Catégorie</span>
        <span style="text-align:center">Durée</span>
        <span style="text-align:center">Statut</span>
        <span style="text-align:right">Actions</span>
      </div>
      <div id="moduleList">
        <div class="list-loading">Chargement…</div>
      </div>
    </div>
    </div>

  </div> <!-- End main-content -->

<!-- ── Add/Edit Module Modal ── -->
<div class="modal-backdrop" id="formModal">
  <div class="modal">
    <div class="modal-head">
      <h3 id="formTitle">Ajouter un Module</h3>
      <button class="btn-close" id="formClose">&times;</button>
    </div>
    <form id="moduleForm" novalidate>
      <input type="hidden" id="fId">
      <div class="modal-body">

        <div class="form-group">
          <label for="fTitle">Titre *</label>
          <input type="text" id="fTitle" placeholder="Titre du module" required>
        </div>

        <div class="form-group">
          <label for="fCategory">Catégorie *</label>
          <select id="fCategory" required>
            <option value="">— Sélectionner —</option>
            <option>Sécurité</option>
            <option>Réseau</option>
            <option>Données</option>
            <option>Cloud</option>
            <option>IA</option>
            <option>Phishing</option>
            <option>Ransomware</option>
          </select>
        </div>

        <div class="form-group">
          <label for="fDuration">Durée (minutes)</label>
          <input type="number" id="fDuration" value="30" min="1" max="600">
        </div>

        <div class="form-group">
          <label for="fImage">URL de l'image</label>
          <input type="url" id="fImage" placeholder="https://…">
        </div>

         <div class="form-group">
           <label for="fVideo">URL YouTube</label>
           <input type="url" id="fVideo" placeholder="https://www.youtube.com/watch?v=…">
         </div>

         <div class="form-group">
           <label for="fContent">Contenu additionnel</label>
           <textarea id="fContent" rows="3" placeholder="Contenu optionnel…"></textarea>
         </div>

        <div class="form-group">
          <label class="checkbox-row">
            <input type="checkbox" id="fQuizEnabled">
            <span>Quiz activé</span>
          </label>
        </div>

        <div class="form-group">
          <label for="fDescription">Description</label>
          <textarea id="fDescription" rows="3" placeholder="Description détaillée du module…"></textarea>
        </div>

        <div class="form-group">
          <label class="checkbox-row">
            <input type="checkbox" id="fActive" checked>
            <span>Module actif (visible)</span>
          </label>
        </div>

      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel-form" id="formCancel">Annuler</button>
        <button type="submit" class="btn-save">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Delete Confirm Modal ── -->
<div class="modal-backdrop" id="delModal">
  <div class="modal" style="max-width:400px">
    <div class="modal-confirm">
      <h3>🗑️ Confirmer la suppression</h3>
      <p>Cette action est <strong>irréversible</strong>. Supprimer ce module ?</p>
      <input type="hidden" id="delId">
      <div style="display:flex;gap:12px;justify-content:center">
        <button class="btn-cancel-form" id="delCancel">Annuler</button>
        <button class="btn-del-confirm" id="delConfirm">Supprimer</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';

  // ─── State ───────────────────────────────────────────────────────────────
  const state = {
    modules: [],         // array of module objects (typed)
    mode: 'add'          // 'add' | 'edit'
  };

  // ─── DOM ─────────────────────────────────────────────────────────────────
  const listEl    = document.getElementById('moduleList');
  const formModal = document.getElementById('formModal');
  const delModal  = document.getElementById('delModal');
  const form      = document.getElementById('moduleForm');

  // Form fields
  const f = {
    id:           document.getElementById('fId'),
    title:        document.getElementById('fTitle'),
    category:     document.getElementById('fCategory'),
     duration:     document.getElementById('fDuration'),
     image:        document.getElementById('fImage'),
     video:        document.getElementById('fVideo'),
     content:      document.getElementById('fContent'),
     quizEnabled:  document.getElementById('fQuizEnabled'),
    description:  document.getElementById('fDescription'),
    active:       document.getElementById('fActive'),
  };

  // ─── API ──────────────────────────────────────────────────────────────────
  function apiFetch(url, opts = {}) {
    return fetch(url, {
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      ...opts
    }).then(r => r.json());
  }

  // JSON.stringify used to serialise the payload to a URL-encoded body
  function encodeBody(obj) {
    return Object.entries(obj)
      .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
      .join('&');
  }

  // ─── Load ─────────────────────────────────────────────────────────────────
  function loadModules() {
    listEl.innerHTML = '<div class="list-loading">Chargement…</div>';
    apiFetch('module.php?action=list')
      .then(data => {
        if (!data.success) throw new Error(data.error);
        state.modules = data.modules;
        // Store in sessionStorage as JSON string (JSON.stringify)
        sessionStorage.setItem('admin_modules', JSON.stringify(state.modules));
        renderList();
        updateStats();
      })
      .catch(err => {
        listEl.innerHTML = `<div class="list-empty">Erreur : ${err.message}</div>`;
      });
  }

  // ─── Stats ────────────────────────────────────────────────────────────────
  function updateStats() {
    const m = state.modules;
    document.getElementById('statTotal').textContent  = m.length;
    document.getElementById('statActive').textContent = m.filter(x => x.active).length;
    document.getElementById('statCats').textContent   = new Set(m.map(x => x.category)).size;
  }

  // ─── Render list ─────────────────────────────────────────────────────────
  function renderList() {
    if (!state.modules.length) {
      listEl.innerHTML = '<div class="list-empty">Aucun module — cliquez sur « Nouveau Module » pour commencer.</div>';
      return;
    }

    listEl.innerHTML = state.modules.map(m => `
      <div class="module-row" data-id="${m.id}">
        <span class="col-title">${esc(m.title)}</span>
        <span><span class="tag">${esc(m.category)}</span></span>
        <span class="col-duration">${m.duration} min</span>
        <span class="col-status">
          <span class="badge ${m.active ? 'badge-active' : 'badge-inactive'}">
            ${m.active ? 'Actif' : 'Inactif'}
          </span>
        </span>
        <span class="col-actions">
          <button class="btn-sm btn-edit"   data-action="edit"   data-id="${m.id}">✏️ Modifier</button>
          <button class="btn-sm btn-del"    data-action="delete" data-id="${m.id}">🗑</button>
          <a href="admin_course.php?id=${m.id}" class="btn-sm btn-edit" style="background:#e3f2fd; color:#0d6efd; text-decoration:none;">📚 Cours</a>
          <a href="admin_quiz.php?id=${m.id}" class="btn-sm btn-edit" style="background:#f3e5f5; color:#7b1fa2; text-decoration:none;">❓ Quiz</a>
        </span>
      </div>`).join('');
  }

  // ─── Event delegation – module list ──────────────────────────────────────
  listEl.addEventListener('click', function (e) {
    console.log('Click detected on listEl');
    const btn = e.target.closest('[data-action]');
    console.log('Button found:', btn);
    if (!btn) {
      console.log('No button with data-action found');
      return;
    }
    e.preventDefault();

    const id     = parseInt(btn.dataset.id, 10);
    const action = btn.dataset.action;
    console.log('Action:', action, 'ID:', id);

    if (action === 'edit')   openEdit(id);
    if (action === 'delete') openDeleteModal(id);
    if (action === 'toggle') toggleStatus(id);
    if (action === 'toggle') toggleStatus(id);
    if (action === 'delete') openDeleteModal(id);
  });

  // ─── Toggle add form ───────────────────────────────────────────────────────
  document.getElementById('btnToggleForm').addEventListener('click', function () {
    const container = document.getElementById('addFormContainer');
    const isVisible = container.style.display !== 'none';
    if (isVisible) {
      container.style.display = 'none';
      this.innerHTML = '➕ Nouveau Module';
    } else {
      container.style.display = 'block';
      this.innerHTML = '➖ Masquer Formulaire';
      // Reset form
      document.getElementById('addModuleForm').reset();
      document.getElementById('addActive').checked = true;
      document.getElementById('addDuration').value = 30;
    }
  });

  // ─── Cancel add form ───────────────────────────────────────────────────────
  document.getElementById('btnCancelAdd').addEventListener('click', function () {
    document.getElementById('addFormContainer').style.display = 'none';
    document.getElementById('btnToggleForm').innerHTML = '➕ Nouveau Module';
  });

  // ─── Open edit form (modal) ─────────────────────────────────────────────────
  function openEdit(id) {
    console.log('openEdit called with id:', id, 'type:', typeof id);
    console.log('Available modules:', state.modules.map(m => ({id: m.id, title: m.title})));
    const m = state.modules.find(x => x.id == id); // Use == instead of === for type coercion
    console.log('Found module:', m);
    if (!m) {
      console.log('Module not found! Available IDs:', state.modules.map(m => m.id));
      return;
    }

    state.mode = 'edit';
    document.getElementById('formTitle').textContent = 'Modifier le Module';

    // Populate from JSON object (JSON.stringify used when storing, parse when reading)
    f.id.value           = m.id;
    f.title.value        = m.title        ?? '';
    f.category.value     = m.category     ?? '';
    f.duration.value     = m.duration     ?? 30;
    f.image.value        = m.image        ?? '';
    f.page.value         = m.page         ?? '';
    f.video.value        = m.video_url    ?? '';
    f.content.value      = m.content      ?? '';
    f.quizPage.value     = m.quiz_page    ?? '';
    f.quizEnabled.checked= !!m.quiz_enabled;
    f.description.value  = m.description  ?? '';
    f.active.checked     = !!m.active;

    console.log('Form populated, opening modal');
    openFormModal();
  }

  // ─── Open edit form ───────────────────────────────────────────────────────
  function openEdit(id) {
    console.log('openEdit called with id:', id, 'type:', typeof id);
    console.log('Available modules:', state.modules.map(m => ({id: m.id, title: m.title})));
    const m = state.modules.find(x => x.id == id); // Use == instead of === for type coercion
    console.log('Found module:', m);
    if (!m) {
      console.log('Module not found! Available IDs:', state.modules.map(m => m.id));
      return;
    }

    state.mode = 'edit';
    document.getElementById('formTitle').textContent = 'Modifier le Module';

    // Populate from JSON object (JSON.stringify used when storing, parse when reading)
    f.id.value           = m.id;
    f.title.value        = m.title        ?? '';
    f.category.value     = m.category     ?? '';
    f.duration.value     = m.duration     ?? 30;
    f.image.value        = m.image        ?? '';
    f.page.value         = m.page         ?? '';
    f.video.value        = m.video_url    ?? '';
    f.content.value      = m.content      ?? '';
    f.quizPage.value     = m.quiz_page    ?? '';
    f.quizEnabled.checked= !!m.quiz_enabled;
    f.description.value  = m.description  ?? '';
    f.active.checked     = !!m.active;

    console.log('Form populated, opening modal');
    openFormModal();
  }

  // ─── Inline Add Form submit ────────────────────────────────────────────────
  document.getElementById('addModuleForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const title = document.getElementById('addTitle').value.trim();
    const category = document.getElementById('addCategory').value;
    if (!title || !category) {
      showToast('Titre et catégorie sont obligatoires', 'error');
      return;
    }

    const payload = {
      title:        title,
      category:     category,
      duration:     document.getElementById('addDuration').value,
      image:        document.getElementById('addImage').value.trim(),
      video_url:    document.getElementById('addVideo').value.trim(),
      content:      document.getElementById('addContent').value.trim(),
      quiz_enabled: document.getElementById('addQuizEnabled').checked ? 1 : 0,
      description:  document.getElementById('addDescription').value.trim(),
      active:       document.getElementById('addActive').checked ? 1 : 0,
    };

    apiFetch('module.php?action=add', { method:'POST', body: encodeBody(payload) })
      .then(data => {
        if (data.success) {
          document.getElementById('addFormContainer').style.display = 'none';
          document.getElementById('btnToggleForm').innerHTML = '➕ Nouveau Module';
          showToast('Module ajouté ✔', 'success');
          loadModules();
        } else {
          showToast(data.error || 'Échec de l\'ajout', 'error');
        }
      })
      .catch(() => showToast('Erreur de connexion', 'error'));
  });

  // ─── Modal Form submit (edit) ──────────────────────────────────────────────
  form.addEventListener('submit', function (e) {
    e.preventDefault();

    if (!f.title.value.trim() || !f.category.value) {
      showToast('Titre et catégorie sont obligatoires', 'error');
      return;
    }

    // Build payload object, then encode (JSON.stringify used for sessionStorage backup)
    const payload = {
      title:        f.title.value.trim(),
      category:     f.category.value,
      duration:     f.duration.value,
      image:        f.image.value.trim(),
      video_url:    f.video.value.trim(),
      content:      f.content.value.trim(),
      quiz_enabled: f.quizEnabled.checked ? 1 : 0,
      description:  f.description.value.trim(),
      active:       f.active.checked ? 1 : 0,
    };

    payload.id = f.id.value;

    apiFetch('module.php?action=update', { method:'POST', body: encodeBody(payload) })
      .then(data => {
        if (data.success) {
          closeFormModal();
          showToast('Module mis à jour ✔', 'success');
          loadModules();
        } else {
          showToast(data.error || 'Échec de la mise à jour', 'error');
        }
      })
      .catch(() => showToast('Erreur de connexion', 'error'));
  });

  // ─── Toggle status ────────────────────────────────────────────────────────
  function toggleStatus(id) {
    const m = state.modules.find(x => x.id === id);
    if (!m) return;

    const payload = {
      id:           m.id,
      title:        m.title,
      category:     m.category,
      duration:     m.duration,
      image:        m.image        ?? '',
      video_url:    m.video_url    ?? '',
      content:      m.content      ?? '',
      quiz_enabled: m.quiz_enabled ?? 0,
      description:  m.description  ?? '',
      active:       m.active ? 0 : 1,   // flip
    };

    apiFetch('module.php?action=update', { method:'POST', body: encodeBody(payload) })
      .then(data => {
        if (data.success) {
          showToast('Statut mis à jour', 'success');
          loadModules();
        } else {
          showToast(data.error || 'Erreur', 'error');
        }
      })
      .catch(() => showToast('Erreur de connexion', 'error'));
  }

  // ─── Delete ───────────────────────────────────────────────────────────────
  function openDeleteModal(id) {
    document.getElementById('delId').value = id;
    delModal.classList.add('open');
  }

  document.getElementById('delConfirm').addEventListener('click', function () {
    const id = document.getElementById('delId').value;
    apiFetch('module.php?action=delete', { method:'POST', body: `id=${encodeURIComponent(id)}` })
      .then(data => {
        delModal.classList.remove('open');
        if (data.success) {
          showToast('Module supprimé', 'success');
          loadModules();
        } else {
          showToast(data.error || 'Erreur', 'error');
        }
      })
      .catch(() => {
        delModal.classList.remove('open');
        showToast('Erreur de connexion', 'error');
      });
  });

  document.getElementById('delCancel').addEventListener('click',
    () => delModal.classList.remove('open'));
  delModal.addEventListener('click',
    e => { if (e.target === delModal) delModal.classList.remove('open'); });

  // ─── Modal open/close helpers ─────────────────────────────────────────────
  function openFormModal()  { formModal.classList.add('open'); }
  function closeFormModal() { formModal.classList.remove('open'); }

  document.getElementById('formClose').addEventListener('click', closeFormModal);
  document.getElementById('formCancel').addEventListener('click', closeFormModal);
  formModal.addEventListener('click', e => { if (e.target === formModal) closeFormModal(); });

  // ─── Toast ────────────────────────────────────────────────────────────────
  function showToast(msg, type = 'success') {
    document.querySelectorAll('.toast').forEach(t => t.remove());
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => {
      el.classList.add('toast-hide');
      setTimeout(() => el.remove(), 350);
    }, 3000);
  }

  // ─── XSS helper ───────────────────────────────────────────────────────────
  function esc(str) {
    const d = document.createElement('div');
    d.textContent = String(str ?? '');
    return d.innerHTML;
  }

  // ─── Pre-fill edit if redirected with ?edit=ID ────────────────────────────
  const preEditId = <?php echo json_encode($edit_id); ?>;

  // ─── Init ─────────────────────────────────────────────────────────────────
  loadModules();

  // After load, open edit if needed
  if (preEditId > 0) {
    // wait for modules to load then open
    const waitEdit = setInterval(() => {
      if (state.modules.length) {
        clearInterval(waitEdit);
        openEdit(preEditId);
      }
    }, 200);
  }
})();
</script>
</body>
</html>