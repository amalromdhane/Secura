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
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#f0f2f5; color:#333; }

    /* Navbar */
    .navbar {
      background: linear-gradient(135deg,#1a73e8,#0d47a1);
      color:#fff;
      padding:15px 30px;
      display:flex;
      justify-content:space-between;
      align-items:center;
      box-shadow:0 2px 10px rgba(0,0,0,.15);
      position:sticky;
      top:0;
      z-index:100;
    }
    .navbar h1 { font-size:22px; }
    .user-info { display:flex; align-items:center; gap:14px; }
    .user-info span { opacity:.9; font-size:14px; }
    .logout-btn {
      background:rgba(255,255,255,.2);
      color:#fff;
      padding:8px 18px;
      border:none;
      border-radius:6px;
      cursor:pointer;
      text-decoration:none;
      font-size:14px;
      transition:background .25s;
    }
    .logout-btn:hover { background:rgba(255,255,255,.32); }

    .container { max-width:1200px; margin:30px auto; padding:0 20px; }

    /* Stats */
    .stats-grid {
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
      gap:18px;
      margin-bottom:28px;
    }
    .stat-card {
      background:#fff;
      padding:22px 24px;
      border-radius:12px;
      box-shadow:0 2px 8px rgba(0,0,0,.07);
    }
    .stat-card h3 { color:#888; font-size:13px; margin-bottom:8px; text-transform:uppercase; letter-spacing:.5px; }
    .stat-card .number { font-size:32px; font-weight:700; color:#1a73e8; }

    /* Dashboard section */
    .dash-section {
      background:#fff;
      border-radius:12px;
      box-shadow:0 2px 8px rgba(0,0,0,.07);
      padding:24px;
      margin-bottom:22px;
    }
    .dash-section h2 {
      font-size:18px;
      color:#333;
      margin-bottom:20px;
      padding-bottom:12px;
      border-bottom:2px solid #1a73e8;
    }

    /* Menu grid */
    .menu-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:14px; }
    .menu-item {
      background:#f8f9fa;
      border:2px solid transparent;
      border-radius:10px;
      padding:20px;
      text-align:center;
      text-decoration:none;
      color:#333;
      transition:all .25s;
      display:block;
    }
    .menu-item:hover {
      background:#fff;
      border-color:#1a73e8;
      transform:translateY(-3px);
      box-shadow:0 6px 16px rgba(0,0,0,.1);
    }
    .menu-item .icon { font-size:30px; margin-bottom:8px; }
    .menu-item .label { font-weight:600; font-size:13px; }

    /* Alert */
    .alert { padding:14px 18px; border-radius:8px; margin-bottom:18px; font-size:14px; }
    .alert-success { background:#e8f5e9; color:#2e7d32; border-left:4px solid #2e7d32; }

    /* Module list */
    .btn-add {
      display:inline-flex;
      align-items:center;
      gap:8px;
      background:linear-gradient(135deg,#1a73e8,#0d47a1);
      color:#fff;
      border:none;
      padding:11px 22px;
      border-radius:8px;
      cursor:pointer;
      font-size:14px;
      font-weight:600;
      margin-bottom:18px;
      transition:all .25s;
    }
    .btn-add:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(26,115,232,.4); }

    .list-container {
      border:1px solid #e0e0e0;
      border-radius:10px;
      overflow:hidden;
    }
    .list-header {
      display:grid;
      grid-template-columns:2fr 1fr 80px 90px 1fr;
      background:linear-gradient(135deg,#1a73e8,#0d47a1);
      color:#fff;
      padding:13px 18px;
      font-size:13px;
      font-weight:600;
      gap:10px;
    }
    .module-row {
      display:grid;
      grid-template-columns:2fr 1fr 80px 90px 1fr;
      padding:14px 18px;
      background:#fff;
      border-bottom:1px solid #f0f0f0;
      align-items:center;
      gap:10px;
      transition:background .2s;
    }
    .module-row:last-child { border-bottom:none; }
    .module-row:hover { background:#fafcff; }

    .module-row .col-title { font-weight:600; font-size:14px; color:#222; }
    .tag {
      display:inline-block;
      background:#e3f2fd;
      color:#1976d2;
      padding:3px 10px;
      border-radius:20px;
      font-size:11px;
      font-weight:600;
    }
    .col-duration { text-align:center; color:#666; font-size:13px; }
    .col-status { text-align:center; }
    .badge {
      padding:4px 12px;
      border-radius:20px;
      font-size:11px;
      font-weight:600;
    }
    .badge-active   { background:#e8f5e9; color:#2e7d32; }
    .badge-inactive { background:#ffebee; color:#c62828; }
    .col-actions { display:flex; gap:6px; justify-content:flex-end; }

    .btn-sm {
      padding:5px 12px;
      border:none;
      border-radius:5px;
      cursor:pointer;
      font-size:12px;
      font-weight:600;
      transition:all .2s;
    }
    .btn-edit    { background:#fff3e0; color:#e65100; }
    .btn-edit:hover { background:#ffe0b2; }
    .btn-toggle  { background:#e8f5e9; color:#2e7d32; }
    .btn-toggle:hover { background:#c8e6c9; }
    .btn-toggle.is-inactive { background:#ffebee; color:#c62828; }
    .btn-toggle.is-inactive:hover { background:#ffcdd2; }
    .btn-del { background:#ffebee; color:#c62828; }
    .btn-del:hover { background:#ffcdd2; }

    .list-empty { padding:40px; text-align:center; color:#999; font-style:italic; }
    .list-loading { padding:40px; text-align:center; color:#999; }

    /* Modal */
    .modal-backdrop {
      display:none;
      position:fixed;
      inset:0;
      background:rgba(0,0,0,.55);
      z-index:200;
      align-items:center;
      justify-content:center;
      backdrop-filter:blur(3px);
    }
    .modal-backdrop.open { display:flex; }
    .modal {
      background:#fff;
      border-radius:14px;
      max-width:520px;
      width:92%;
      max-height:92vh;
      overflow-y:auto;
      box-shadow:0 20px 60px rgba(0,0,0,.25);
    }
    .modal-head {
      display:flex;
      justify-content:space-between;
      align-items:center;
      padding:18px 22px;
      border-bottom:1px solid #eee;
    }
    .modal-head h3 { font-size:18px; color:#222; }
    .btn-close {
      background:none;
      border:none;
      font-size:26px;
      cursor:pointer;
      color:#aaa;
      line-height:1;
      transition:color .2s;
    }
    .btn-close:hover { color:#333; }
    .modal-body { padding:22px; }
    .form-group { margin-bottom:18px; }
    .form-group label {
      display:block;
      margin-bottom:6px;
      font-weight:600;
      font-size:13px;
      color:#444;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
      width:100%;
      padding:10px 13px;
      border:1px solid #ddd;
      border-radius:7px;
      font-size:14px;
      transition:border .2s;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus { outline:none; border-color:#1a73e8; }
    .form-group small { color:#999; font-size:11px; margin-top:4px; display:block; }
    .checkbox-row {
      display:flex;
      align-items:center;
      gap:10px;
      font-weight:600;
      font-size:14px;
      color:#444;
      cursor:pointer;
    }
    .checkbox-row input[type="checkbox"] { width:18px; height:18px; cursor:pointer; }
    .modal-foot {
      display:flex;
      gap:10px;
      justify-content:flex-end;
      padding:16px 22px;
      border-top:1px solid #eee;
    }
    .btn-cancel-form {
      background:#f0f0f0;
      color:#666;
      border:none;
      padding:10px 22px;
      border-radius:7px;
      cursor:pointer;
      font-size:14px;
      font-weight:600;
      transition:background .2s;
    }
    .btn-cancel-form:hover { background:#e0e0e0; }
    .btn-save {
      background:linear-gradient(135deg,#1a73e8,#0d47a1);
      color:#fff;
      border:none;
      padding:10px 24px;
      border-radius:7px;
      cursor:pointer;
      font-size:14px;
      font-weight:600;
      transition:all .25s;
    }
    .btn-save:hover { transform:translateY(-2px); box-shadow:0 5px 16px rgba(26,115,232,.4); }

    /* Delete confirm modal */
    .modal-confirm { text-align:center; padding:32px; }
    .modal-confirm h3 { color:#c62828; margin-bottom:12px; font-size:20px; }
    .modal-confirm p { color:#666; margin-bottom:24px; }
    .btn-del-confirm {
      background:#c62828;
      color:#fff;
      border:none;
      padding:10px 26px;
      border-radius:7px;
      cursor:pointer;
      font-size:14px;
      font-weight:600;
      transition:background .2s;
    }
    .btn-del-confirm:hover { background:#b71c1c; }

    /* Toast */
    .toast {
      position:fixed;
      top:20px;
      right:22px;
      z-index:9999;
      padding:13px 20px;
      border-radius:8px;
      font-size:14px;
      font-weight:500;
      color:#fff;
      box-shadow:0 4px 20px rgba(0,0,0,.2);
      transition:all .35s;
    }
    .toast-success { background:#2e7d32; }
    .toast-error   { background:#c62828; }
    .toast-hide { opacity:0; transform:translateY(-12px); }
  </style>
</head>
<body>
<nav class="navbar">
  <h1>🔐 Admin Dashboard – Secura</h1>
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

  <!-- Menu -->
  <div class="dash-section">
    <h2>📋 Menu Administrateur</h2>
    <div class="menu-grid">
      <a href="pages/cloud.html"        class="menu-item"><div class="icon">☁️</div><div class="label">Cloud</div></a>
      <a href="pages/passwords.html"    class="menu-item"><div class="icon">🔑</div><div class="label">Mots de passe</div></a>
      <a href="pages/phishing.html"     class="menu-item"><div class="icon">🎣</div><div class="label">Phishing</div></a>
      <a href="pages/ransomware.html"   class="menu-item"><div class="icon">💀</div><div class="label">Ransomware</div></a>
      <a href="all_modules.php"         class="menu-item"><div class="icon">📚</div><div class="label">Tous les Modules</div></a>
      <a href="#"                        class="menu-item"><div class="icon">⚙️</div><div class="label">Paramètres</div></a>
    </div>
  </div>

  <!-- Module management -->
  <div class="dash-section">
    <h2>📚 Gestion des Modules</h2>

    <button class="btn-add" id="btnAdd">➕ Nouveau Module</button>

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

</div>

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
          <label for="fPage">Fichier HTML du module</label>
          <input type="text" id="fPage" placeholder="phishing.html">
          <small>Fichier dans le dossier pages/</small>
        </div>

        <div class="form-group">
          <label for="fVideo">URL YouTube</label>
          <input type="url" id="fVideo" placeholder="https://www.youtube.com/watch?v=…">
        </div>

        <div class="form-group">
          <label for="fContent">Contenu HTML additionnel</label>
          <textarea id="fContent" rows="3" placeholder="HTML optionnel…"></textarea>
        </div>

        <div class="form-group">
          <label for="fQuizPage">Fichier quiz HTML</label>
          <input type="text" id="fQuizPage" placeholder="PasswordQuiz.html">
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
    page:         document.getElementById('fPage'),
    video:        document.getElementById('fVideo'),
    content:      document.getElementById('fContent'),
    quizPage:     document.getElementById('fQuizPage'),
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
    if (action === 'toggle') toggleStatus(id);
    if (action === 'delete') openDeleteModal(id);
  });

  // ─── Open add form ────────────────────────────────────────────────────────
  document.getElementById('btnAdd').addEventListener('click', function () {
    state.mode = 'add';
    document.getElementById('formTitle').textContent = 'Ajouter un Module';
    form.reset();
    f.id.value       = '';
    f.active.checked = true;
    f.duration.value = 30;
    openFormModal();
  });

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

  // ─── Form submit (add or edit) ────────────────────────────────────────────
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
      page:         f.page.value.trim(),
      video_url:    f.video.value.trim(),
      content:      f.content.value.trim(),
      quiz_page:    f.quizPage.value.trim(),
      quiz_enabled: f.quizEnabled.checked ? 1 : 0,
      description:  f.description.value.trim(),
      active:       f.active.checked ? 1 : 0,
    };

    const isEdit = state.mode === 'edit';
    if (isEdit) payload.id = f.id.value;

    const url = isEdit ? 'module.php?action=update' : 'module.php?action=add';

    apiFetch(url, { method:'POST', body: encodeBody(payload) })
      .then(data => {
        if (data.success) {
          closeFormModal();
          showToast(isEdit ? 'Module mis à jour ✔' : 'Module ajouté ✔', 'success');
          loadModules();
        } else {
          showToast(data.error || 'Échec de l\'opération', 'error');
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
      page:         m.page         ?? '',
      video_url:    m.video_url    ?? '',
      content:      m.content      ?? '',
      quiz_page:    m.quiz_page    ?? '',
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