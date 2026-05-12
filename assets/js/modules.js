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
          <span class="module-tag" data-category="${escHtml(m.category)}"><i class="fas fa-tag me-1"></i>${escHtml(m.category)}</span>
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

// ─── User Avatar Dropdown ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  const userAvatar = document.getElementById('userAvatar');
  const userDropdown = document.getElementById('userDropdown');

  if (userAvatar && userDropdown) {
    userAvatar.addEventListener('click', function(e) {
      e.stopPropagation();
      userDropdown.classList.toggle('open');
    });

    document.addEventListener('click', function() {
      userDropdown.classList.remove('open');
    });
  }

  // Mobile menu toggle
  const navToggler = document.getElementById('navToggler');
  const navMenu = document.getElementById('navMenu');

  if (navToggler && navMenu) {
    navToggler.addEventListener('click', function() {
      navMenu.classList.toggle('active');
    });
  }
});