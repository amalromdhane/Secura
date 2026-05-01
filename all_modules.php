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

require_once 'config.php';

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
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/cyberaware.css">
  <style>
    /* ── Hero ── */
    .modules-hero {
      background: linear-gradient(135deg, rgba(10,14,23,0.98) 0%, rgba(18,24,38,0.95) 100%);
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
        radial-gradient(circle at 20% 50%, rgba(13,110,253,0.12) 0%, transparent 40%),
        radial-gradient(circle at 80% 30%, rgba(0,212,255,0.08) 0%, transparent 40%);
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
      color: #00d4ff;
    }
    .hero-stat .lbl {
      font-size: 0.8rem;
      color: #6c757d;
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
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      color: #adb5bd;
      padding: 8px 20px;
      border-radius: 30px;
      font-size: 13px;
      cursor: pointer;
      transition: all 0.25s;
      font-weight: 500;
    }
    .filter-btn:hover,
    .filter-btn.active {
      background: rgba(13,110,253,0.2);
      border-color: #0d6efd;
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
      background: rgba(18, 24, 38, 0.85);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 18px;
      overflow: hidden;
      transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
      display: flex;
      flex-direction: column;
      position: relative;
    }
    .module-card:hover {
      transform: translateY(-8px);
      border-color: var(--neon-cyan, #00d4ff);
      box-shadow: 0 12px 40px rgba(0,212,255,0.18);
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
      background: rgba(13,110,253,0.15);
      color: #4d9aff;
      border: 1px solid rgba(13,110,253,0.3);
      padding: 4px 14px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 14px;
      align-self: flex-start;
    }

    .module-title {
      color: #f1f5ff;
      font-size: 1.25rem;
      font-weight: 700;
      margin-bottom: 10px;
      line-height: 1.35;
    }

    .module-desc {
      color: #8899aa;
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
      color: #00d4ff;
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
      background: linear-gradient(135deg, #0d6efd, #00d4ff);
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
      box-shadow: 0 6px 20px rgba(13,110,253,0.35);
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
      background: rgba(255,255,255,0.06);
      color: #adb5bd;
      text-decoration: none;
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 10px;
      padding: 10px 20px;
      font-weight: 500;
      font-size: 0.85rem;
      cursor: pointer;
      transition: all 0.25s;
      width: 100%;
    }
    .btn-quiz:hover {
      background: rgba(255,193,7,0.1);
      border-color: #ffc107;
      color: #ffc107;
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
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <div class="nav-container">
    <a class="nav-brand" href="index.html">
      <i class="fas fa-shield-alt"></i>
      Sec<span>ura</span>
    </a>
    <button class="nav-toggler" id="navToggler">
      <i class="fas fa-bars"></i>
    </button>
    <ul class="nav-menu" id="navMenu">
      <li class="nav-item"><a class="nav-link" href="index.html"><i class="fas fa-home"></i> Accueil</a></li>
      <li class="nav-item"><a class="nav-link" href="index.html#modules"><i class="fas fa-layer-group"></i> Modules</a></li>
      <li class="nav-item"><a class="nav-link" href="index.html#about"><i class="fas fa-info-circle"></i> À propos</a></li>
      <?php if ($is_logged_in): ?>
        <li class="nav-item"><a class="nav-link" href="admin_dashboard.php"><i class="fas fa-cog"></i> Admin</a></li>
        <li class="nav-item"><a class="nav-link" href="login.php?action=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
      <?php else: ?>
        <li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Connexion</a></li>
      <?php endif; ?>
    </ul>
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

<script>
(function () {
  'use strict';

  // ─── State ───────────────────────────────────────────────────────────────
  const state = {
    modules: [],
    activeCategory: 'all',
    pendingDeleteId: null,
    isAdmin: <?php echo json_encode($user_role === 'admin'); ?>
  };

  // ─── DOM refs ─────────────────────────────────────────────────────────────
  const grid       = document.getElementById('modulesGrid');
  const filterBar  = document.getElementById('filterBar');
  const deleteModal= document.getElementById('deleteModal');
  const confirmBtn = document.getElementById('confirmDelete');
  const cancelBtn  = document.getElementById('cancelDelete');

  // ─── API helpers ─────────────────────────────────────────────────────────
  function apiFetch(url, options = {}) {
    return fetch(url, {
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      ...options
    }).then(r => r.json());
  }

  function encodeBody(obj) {
    return Object.entries(obj)
      .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
      .join('&');
  }

  // ─── Load modules ─────────────────────────────────────────────────────────
  function loadModules() {
    apiFetch('module.php?action=list')
      .then(data => {
        if (!data.success) throw new Error(data.error || 'Erreur serveur');
        // JSON.stringify used for storage / transfer
        sessionStorage.setItem('secura_modules', JSON.stringify(data.modules));
        state.modules = data.modules;
        buildFilterBar();
        renderGrid(state.modules);
        updateStats();
      })
      .catch(err => {
        grid.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-exclamation-triangle" style="color:#dc3545;"></i>
            <h3 style="color:#dc3545;">Erreur de chargement</h3>
            <p>${err.message}</p>
          </div>`;
      });
  }

  // ─── Stats ────────────────────────────────────────────────────────────────
  function updateStats() {
    const modules = state.modules;
    const categories = new Set(modules.map(m => m.category)).size;
    const duration   = modules.reduce((s, m) => s + parseInt(m.duration || 0), 0);
    document.getElementById('totalCount').textContent    = modules.length;
    document.getElementById('categoryCount').textContent = categories;
    document.getElementById('totalDuration').textContent = duration;
  }

  // ─── Filter bar ───────────────────────────────────────────────────────────
  function buildFilterBar() {
    const categories = [...new Set(state.modules.map(m => m.category).filter(Boolean))];
    const extra = categories.map(cat =>
      `<button class="filter-btn" data-category="${escHtml(cat)}">${escHtml(cat)}</button>`
    ).join('');
    // Keep "Tous" button + append categories
    const tous = filterBar.querySelector('[data-category="all"]');
    tous.insertAdjacentHTML('afterend', extra);
  }

  // ─── Render grid ──────────────────────────────────────────────────────────
  function renderGrid(modules) {
    if (!modules.length) {
      grid.innerHTML = `
        <div class="empty-state">
          <i class="fas fa-book-open"></i>
          <h3>Aucun module disponible</h3>
          <p>Les modules ajoutés par l'administrateur apparaîtront ici.</p>
          ${state.isAdmin ? `<a href="admin_dashboard.php" class="btn-access" style="width:auto;display:inline-flex;margin-top:16px;"><i class="fas fa-plus"></i> Ajouter un module</a>` : ''}
        </div>`;
      return;
    }

    // Build HTML from JSON data – JSON.stringify used for data-attr embedding
    grid.innerHTML = modules.map(m => buildCard(m)).join('');
  }

  function buildCard(m) {
    const thumb = m.image
      ? `<img class="module-thumb" src="${escHtml(m.image)}" alt="${escHtml(m.title)}" loading="lazy">`
      : `<div class="module-thumb-placeholder"><i class="fas fa-book-open"></i></div>`;

    const accessBtn = `<a href="module.php?id=${m.id}" class="btn-access"><i class="bi bi-play-circle-fill"></i> Accéder au module</a>`;

    const quizBtn = parseInt(m.quiz_enabled)
      ? `<a href="quiz.php?id=${m.id}" class="btn-quiz"><i class="fas fa-clipboard-check"></i> Faire le quiz</a>`
      : '';

    const adminRibbon = state.isAdmin ? `<span class="admin-ribbon">Admin</span>` : '';

    // Embed full module data as JSON in data attribute (uses JSON.stringify)
    const dataAttr = `data-module='${JSON.stringify(m).replace(/'/g, "&#39;")}'`;

    const adminToolbar = state.isAdmin ? `
      <div class="admin-toolbar">
        <button class="btn-admin btn-admin-edit" data-action="edit" data-id="${m.id}">
          <i class="fas fa-pen"></i> Modifier
        </button>
        <button class="btn-admin btn-admin-delete" data-action="delete" data-id="${m.id}">
          <i class="fas fa-trash"></i> Supprimer
        </button>
      </div>` : '';

    return `
      <article class="module-card" data-id="${m.id}" data-category="${escHtml(m.category)}" ${dataAttr}>
        ${adminRibbon}
        ${thumb}
        <div class="module-body">
          <span class="module-tag"><i class="fas fa-tag me-1"></i>${escHtml(m.category)}</span>
          <h3 class="module-title">${escHtml(m.title)}</h3>
          <p class="module-desc">${escHtml(m.description || 'Aucune description fournie.')}</p>
          <div class="module-meta">
            <span><i class="fas fa-clock"></i> ${parseInt(m.duration) || 0} min</span>
            ${parseInt(m.quiz_enabled) ? '<span><i class="fas fa-clipboard-check"></i> Quiz inclus</span>' : ''}
          </div>
          <div class="module-actions">
            ${accessBtn}
            ${quizBtn}
          </div>
        </div>
        ${adminToolbar}
      </article>`;
  }

  // ─── Filter ───────────────────────────────────────────────────────────────
  function applyFilter(category) {
    state.activeCategory = category;
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.category === category);
    });
    const filtered = category === 'all'
      ? state.modules
      : state.modules.filter(m => m.category === category);
    renderGrid(filtered);
  }

  // ─── Event delegation – grid ──────────────────────────────────────────────
  grid.addEventListener('click', function (e) {
    const deleteBtn = e.target.closest('[data-action="delete"]');
    const editBtn   = e.target.closest('[data-action="edit"]');

    if (deleteBtn) {
      e.preventDefault();
      state.pendingDeleteId = deleteBtn.dataset.id;
      deleteModal.classList.add('active');
    }

    if (editBtn) {
      e.preventDefault();
      const id = editBtn.dataset.id;
      window.location.href = `admin_dashboard.php?edit=${id}`;
    }
  });

  // ─── Event delegation – filter bar ────────────────────────────────────────
  filterBar.addEventListener('click', function (e) {
    const btn = e.target.closest('.filter-btn');
    if (btn) applyFilter(btn.dataset.category);
  });

  // ─── Delete modal ─────────────────────────────────────────────────────────
  confirmBtn.addEventListener('click', function () {
    const id = state.pendingDeleteId;
    if (!id) return;

    apiFetch('module.php?action=delete', {
      method: 'POST',
      body: encodeBody({ id })
    }).then(data => {
      deleteModal.classList.remove('active');
      state.pendingDeleteId = null;
      if (data.success) {
        showToast('Module supprimé avec succès', 'success');
        loadModules();
      } else {
        showToast(data.error || 'Erreur lors de la suppression', 'error');
      }
    }).catch(() => {
      deleteModal.classList.remove('active');
      showToast('Erreur de connexion', 'error');
    });
  });

  cancelBtn.addEventListener('click', function () {
    deleteModal.classList.remove('active');
    state.pendingDeleteId = null;
  });

  deleteModal.addEventListener('click', function (e) {
    if (e.target === deleteModal) {
      deleteModal.classList.remove('active');
      state.pendingDeleteId = null;
    }
  });

  // ─── Toast notification ───────────────────────────────────────────────────
  function showToast(message, type = 'success') {
    const el = document.createElement('div');
    el.className = `toast-notification toast-${type}`;
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(() => {
      el.classList.add('toast-hide');
      setTimeout(() => el.remove(), 400);
    }, 3000);
  }

  // ─── XSS helper ───────────────────────────────────────────────────────────
  function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = String(str ?? '');
    return d.innerHTML;
  }

  // ─── Bootstrap ────────────────────────────────────────────────────────────
  loadModules();
})();
</script>
</body>
</html>