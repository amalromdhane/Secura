<?php
/**
 * Admin Dashboard – Secura
 */
require_once __DIR__ . '/includes/admin/auth.php';

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

$page_title  = 'Admin Dashboard';
$active_menu = 'dashboard';

require_once __DIR__ . '/includes/admin/layout_start.php';
?>

<br>
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

        <div class="checkbox-group">
          <label>Quiz activé</label>
          <input type="checkbox" id="addQuizEnabled">
        </div>

        <div class="checkbox-group">
          <label>Module actif (visible)</label>
          <input type="checkbox" id="addActive" checked>
        </div>
        <div class="form-actions">
          <button type="button" class="btn-annuler" id="btnCancelAdd" style="background: rgba(18, 24, 38, 0.7); color: var(--text-secondary); border: 1px solid rgba(255, 255, 255, 0.1);">Annuler</button>
          <button type="submit" class="btn-add">Enregistrer</button>
        </div>
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

<?php require_once __DIR__ . '/includes/admin/layout_end.php'; ?>

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
    const options = Object.assign({}, opts);
    if (options.body && typeof options.body === 'object') {
      options.method = options.method || 'POST';
      options.headers = Object.assign({ 'Content-Type': 'application/json' }, options.headers || {});
      options.body = JSON.stringify(options.body);
    }
    return fetch(url, options).then(function (r) { return r.json(); });
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
        <span class="col-title">${m.image ? `<img src="${esc(m.image)}" alt="" style="width:60px; height:60px; margin-right:10px; object-fit:cover; border-radius:4px;">` : ''}${esc(m.title)}</span>
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
    f.video.value        = m.video_url    ?? '';
    f.content.value      = m.content      ?? '';
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

    apiFetch('module.php?action=add', { method:'POST', body: payload })
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

    apiFetch('module.php?action=update', { method:'POST', body: payload })
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

    apiFetch('module.php?action=update', { method:'POST', body: payload })
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
    apiFetch('module.php?action=delete', { method:'POST', body: { id: id } })
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
<?php require_once __DIR__ . '/includes/admin/layout_footer.php'; ?>
