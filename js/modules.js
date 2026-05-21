(function () {
  'use strict';

  const state = {
    modules: [],
    activeCategory: 'all',
    searchQuery: '',
    pendingDeleteId: null,
    isAdmin: !!window.isAdmin
  };

  const grid = document.getElementById('modulesGrid');
  const filterBar = document.getElementById('filterBar');
  const searchInput = document.getElementById('moduleSearch');
  const deleteModal = document.getElementById('deleteModal');
  const confirmBtn = document.getElementById('confirmDelete');
  const cancelBtn = document.getElementById('cancelDelete');

  if (!grid) {
    return;
  }

  function apiFetch(url, options) {
    options = options || {};
    const opts = Object.assign({}, options);
    if (opts.body && typeof opts.body === 'object') {
      opts.method = opts.method || 'POST';
      opts.headers = Object.assign({ 'Content-Type': 'application/json' }, opts.headers || {});
      opts.body = JSON.stringify(opts.body);
    }
    return fetch(url, opts).then(function (r) {
      if (!r.ok) {
        throw new Error('HTTP ' + r.status);
      }
      return r.json();
    });
  }

  function resolveModuleTarget(m) {
    if (m.page && String(m.page).trim() !== '') {
      var page = String(m.page).trim();
      if (/^https?:\/\//i.test(page)) {
        return page;
      }
      var path = page.indexOf('pages/') === 0 || page.indexOf('./pages/') === 0
        ? page.replace(/^\.\//, '')
        : 'pages/' + page.replace(/^\.\//, '');
      return 'access_module.php?to=' + encodeURIComponent(path);
    }
    return 'module.php?id=' + m.id;
  }

  function normalizeModule(m) {
    return {
      id: m.id,
      title: m.title || '',
      description: m.description || '',
      category: m.category || '',
      duration: m.duration || 0,
      image: m.image || '',
      page: m.page || '',
      quiz_enabled: m.quiz_enabled != null ? m.quiz_enabled : 0,
      active: m.active != null ? m.active : 1
    };
  }

  function applyModulesList(list) {
    state.modules = list.map(normalizeModule);
    sessionStorage.setItem('secura_modules', JSON.stringify(state.modules));
    buildFilterBar();
    renderFilteredGrid();
    updateStats();
  }

  function showGridError(message) {
    grid.innerHTML =
      '<div class="empty-state">' +
      '<i class="fas fa-exclamation-triangle" style="color:#dc3545;"></i>' +
      '<h3 style="color:#dc3545;">Erreur de chargement</h3>' +
      '<p>' + escHtml(message) + '</p>' +
      '</div>';
  }

  function loadModules() {
    if (window.modulesData && Array.isArray(window.modulesData)) {
      applyModulesList(window.modulesData);
      return;
    }

    apiFetch('module.php?action=list')
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.error || 'Erreur serveur');
        }
        applyModulesList(data.modules || []);
      })
      .catch(function (err) {
        showGridError(err.message);
      });
  }

  function updateStats() {
    const modules = state.modules;
    const categories = new Set(modules.map(function (m) { return m.category; })).size;
    const duration = modules.reduce(function (s, m) {
      return s + parseInt(m.duration || 0, 10);
    }, 0);

    const totalEl = document.getElementById('totalCount');
    const catEl = document.getElementById('categoryCount');
    const durEl = document.getElementById('totalDuration');
    if (totalEl) totalEl.textContent = modules.length;
    if (catEl) catEl.textContent = categories;
    if (durEl) durEl.textContent = duration;
  }

  function buildFilterBar() {
    if (!filterBar) return;

    const tous = filterBar.querySelector('[data-category="all"]');
    if (!tous) return;

    filterBar.querySelectorAll('.filter-btn:not([data-category="all"])').forEach(function (btn) {
      btn.remove();
    });

    const categories = [];
    state.modules.forEach(function (m) {
      if (m.category && categories.indexOf(m.category) === -1) {
        categories.push(m.category);
      }
    });

    categories.forEach(function (cat) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'filter-btn';
      btn.setAttribute('data-category', cat);
      btn.textContent = cat;
      filterBar.appendChild(btn);
    });
  }

  function renderGrid(modules) {
    if (!modules.length) {
      grid.innerHTML =
        '<div class="empty-state">' +
        '<i class="fas fa-book-open"></i>' +
        '<h3>Aucun module disponible</h3>' +
        '<p>Les modules ajoutés par l\'administrateur apparaîtront ici.</p>' +
        (state.isAdmin
          ? '<a href="admin_dashboard.php" class="btn-access" style="width:auto;display:inline-flex;margin-top:16px;"><i class="fas fa-plus"></i> Ajouter un module</a>'
          : '') +
        '</div>';
      return;
    }

    grid.innerHTML = modules.map(buildCard).join('');
  }

  function buildCard(m) {
    const thumb = m.image
      ? '<img class="module-thumb" src="' + escHtml(m.image) + '" alt="' + escHtml(m.title) + '" loading="lazy">'
      : '<div class="module-thumb-placeholder"><i class="fas fa-book-open"></i></div>';

    const loggedIn = !!window.isLoggedIn;
    const moduleTarget = resolveModuleTarget(m);
    const accessHref = loggedIn
      ? moduleTarget
      : (/^https?:\/\//i.test(moduleTarget)
          ? 'login.php?redirect=' + encodeURIComponent('all_modules.php')
          : 'login.php?redirect=' + encodeURIComponent(moduleTarget));
    const accessBtn =
      '<a href="' + escHtml(accessHref) + '" class="btn-access' + (loggedIn ? '' : ' btn-access-locked') + '">' +
      '<i class="bi bi-' + (loggedIn ? 'play-circle-fill' : 'lock-fill') + '"></i> ' +
      (loggedIn ? 'Accéder au module' : 'Se connecter pour accéder') +
      '</a>';

    const quizHref = loggedIn
      ? 'quiz.php?id=' + m.id
      : 'login.php?redirect=' + encodeURIComponent('quiz.php?id=' + m.id);
    const quizBtn = parseInt(m.quiz_enabled, 10)
      ? '<a href="' + escHtml(quizHref) + '" class="btn-quiz"><i class="fas fa-clipboard-check"></i> Faire le quiz</a>'
      : '';

    const adminRibbon = state.isAdmin ? '<span class="admin-ribbon">Admin</span>' : '';

    const adminToolbar = state.isAdmin
      ? '<div class="admin-toolbar">' +
        '<button type="button" class="btn-admin btn-admin-edit" data-action="edit" data-id="' + m.id + '">' +
        '<i class="fas fa-pen"></i> Modifier</button>' +
        '<button type="button" class="btn-admin btn-admin-delete" data-action="delete" data-id="' + m.id + '">' +
        '<i class="fas fa-trash"></i> Supprimer</button>' +
        '</div>'
      : '';

    return (
      '<article class="module-card" data-id="' + m.id + '" data-category="' + escHtml(m.category) + '">' +
      adminRibbon +
      thumb +
      '<div class="module-body">' +
      '<span class="module-tag"><i class="fas fa-tag me-1"></i>' + escHtml(m.category) + '</span>' +
      '<h3 class="module-title">' + escHtml(m.title) + '</h3>' +
      '<p class="module-desc">' + escHtml(m.description || 'Aucune description fournie.') + '</p>' +
      '<div class="module-meta">' +
      '<span><i class="fas fa-clock"></i> ' + (parseInt(m.duration, 10) || 0) + ' min</span>' +
      (parseInt(m.quiz_enabled, 10) ? '<span><i class="fas fa-clipboard-check"></i> Quiz inclus</span>' : '') +
      '</div>' +
      '<div class="module-actions">' + accessBtn + quizBtn + '</div>' +
      '</div>' +
      adminToolbar +
      '</article>'
    );
  }

  function applyFilter(category) {
    state.activeCategory = category;
    document.querySelectorAll('.filter-btn').forEach(function (btn) {
      btn.classList.toggle('active', btn.getAttribute('data-category') === category);
    });
    renderFilteredGrid();
  }

  function applySearch(query) {
    state.searchQuery = query.toLowerCase();
    renderFilteredGrid();
  }

  function renderFilteredGrid() {
    let filtered = state.modules;

    if (state.activeCategory !== 'all') {
      filtered = filtered.filter(function (m) {
        return m.category === state.activeCategory;
      });
    }

    if (state.searchQuery) {
      filtered = filtered.filter(function (m) {
        return (
          m.title.toLowerCase().indexOf(state.searchQuery) !== -1 ||
          (m.description && m.description.toLowerCase().indexOf(state.searchQuery) !== -1) ||
          (m.category && m.category.toLowerCase().indexOf(state.searchQuery) !== -1)
        );
      });
    }

    renderGrid(filtered);
  }

  grid.addEventListener('click', function (e) {
    const deleteBtn = e.target.closest('[data-action="delete"]');
    const editBtn = e.target.closest('[data-action="edit"]');

    if (deleteBtn && deleteModal) {
      e.preventDefault();
      state.pendingDeleteId = deleteBtn.getAttribute('data-id');
      deleteModal.classList.add('active');
    }

    if (editBtn) {
      e.preventDefault();
      window.location.href = 'admin_dashboard.php?edit=' + editBtn.getAttribute('data-id');
    }
  });

  if (filterBar) {
    filterBar.addEventListener('click', function (e) {
      const btn = e.target.closest('.filter-btn');
      if (btn) {
        applyFilter(btn.getAttribute('data-category'));
      }
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      applySearch(this.value);
    });
  }

  if (confirmBtn) {
    confirmBtn.addEventListener('click', function () {
      const id = state.pendingDeleteId;
      if (!id) return;

      apiFetch('module.php?action=delete', {
        method: 'POST',
        body: { id: id }
      })
        .then(function (data) {
          if (deleteModal) deleteModal.classList.remove('active');
          state.pendingDeleteId = null;
          if (data.success) {
            showToast('Module supprimé avec succès', 'success');
            window.modulesData = null;
            loadModules();
          } else {
            showToast(data.error || 'Erreur lors de la suppression', 'error');
          }
        })
        .catch(function () {
          if (deleteModal) deleteModal.classList.remove('active');
          showToast('Erreur de connexion', 'error');
        });
    });
  }

  if (cancelBtn && deleteModal) {
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
  }

  function showToast(message, type) {
    type = type || 'success';
    document.querySelectorAll('.toast-notification').forEach(function (t) {
      t.remove();
    });
    const el = document.createElement('div');
    el.className = 'toast-notification toast-' + type;
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(function () {
      el.classList.add('toast-hide');
      setTimeout(function () {
        el.remove();
      }, 400);
    }, 3000);
  }

  function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = String(str != null ? str : '');
    return d.innerHTML;
  }

  loadModules();
})();
