(function () {
  'use strict';

  const moduleId = window.SECURA_MODULE_ID || 0;

  const BLOCKS = {
    card: '<div class="blk-card"><strong>Titre de la carte</strong><p>Décrivez le contenu ici…</p></div>',
    grid2: '<div class="blk-grid2"><div class="blk-card"><strong>Colonne 1</strong><p>Contenu…</p></div><div class="blk-card"><strong>Colonne 2</strong><p>Contenu…</p></div></div>',
    grid3: '<div class="blk-grid3"><div class="blk-card"><strong>Colonne 1</strong><p>Contenu…</p></div><div class="blk-card"><strong>Colonne 2</strong><p>Contenu…</p></div><div class="blk-card"><strong>Colonne 3</strong><p>Contenu…</p></div></div>',
    tip: '<div class="blk-tip"><strong>💡 Conseil</strong><p>Votre conseil pratique ici…</p></div>',
    warning: '<div class="blk-warning"><strong>⚠️ Avertissement</strong><p>Votre message d\'alerte ici…</p></div>',
    danger: '<div class="blk-danger"><strong>🚫 Danger</strong><p>Ne jamais faire cela…</p></div>',
    info: '<div class="blk-info"><strong>ℹ️ Information</strong><p>Votre message informatif ici…</p></div>',
    table2: '<table class="comparison-table"><thead><tr><th>Colonne A</th><th>Colonne B</th></tr></thead><tbody><tr><td>Valeur 1</td><td>Valeur 2</td></tr></tbody></table>',
    table3: '<table class="comparison-table"><thead><tr><th>Type</th><th>Exemples</th><th>Description</th></tr></thead><tbody><tr><td>Ligne 1</td><td>Ex A</td><td>Description…</td></tr></tbody></table>',
    table4: '<table class="comparison-table"><thead><tr><th>Outil</th><th>Exemples</th><th>Fonction</th><th>Niveau</th></tr></thead><tbody><tr><td>Antivirus</td><td>Bitdefender</td><td>Détection</td><td>Essentiel</td></tr></tbody></table>',
    '321': '<div class="blk-321"><div class="blk-321-item"><div class="blk-321-num">3</div><div class="blk-321-label">Copies de données</div></div><div class="blk-321-item"><div class="blk-321-num">2</div><div class="blk-321-label">Supports différents</div></div><div class="blk-321-item"><div class="blk-321-num">1</div><div class="blk-321-label">Copie hors ligne</div></div></div>',
    steps: '<div class="blk-steps"><div class="blk-step"><div class="blk-step-num">1</div><div class="blk-step-title">Isoler</div><div class="blk-step-desc">Déconnecter l\'appareil du réseau</div></div><div class="blk-step"><div class="blk-step-num">2</div><div class="blk-step-title">Signaler</div><div class="blk-step-desc">Alerter votre service informatique</div></div><div class="blk-step"><div class="blk-step-num">3</div><div class="blk-step-title">Restaurer</div><div class="blk-step-desc">Utiliser vos sauvegardes</div></div></div>'
  };

  let editorView = 'preview';
  const edContent = function () { return document.getElementById('editorContent'); };
  const edCode = function () { return document.getElementById('editorCode'); };

  function switchView(v) {
    editorView = v;
    document.getElementById('btnPreview').className = 'view-btn' + (v === 'preview' ? ' active' : '');
    document.getElementById('btnCode').className = 'view-btn' + (v === 'code' ? ' active' : '');
    if (v === 'code') {
      edCode().value = edContent().innerHTML;
      edContent().style.display = 'none';
      edCode().style.display = 'block';
    } else {
      edContent().innerHTML = edCode().value;
      edCode().style.display = 'none';
      edContent().style.display = 'block';
    }
  }

  function getEditorHTML() {
    return editorView === 'code' ? edCode().value : edContent().innerHTML;
  }

  function setEditorHTML(html) {
    edContent().innerHTML = html || '';
    edCode().value = html || '';
  }

  function execCmd(cmd) {
    if (editorView === 'code') return;
    edContent().focus();
    document.execCommand(cmd, false, null);
  }

  function insertHeading(tag) {
    if (editorView === 'code') return;
    edContent().focus();
    document.execCommand('formatBlock', false, '<' + tag + '>');
  }

  function insBlock(type) {
    const html = BLOCKS[type];
    if (!html) return;
    if (editorView === 'code') {
      const ta = edCode();
      const pos = ta.selectionStart;
      ta.value = ta.value.slice(0, pos) + '\n\n' + html + '\n\n' + ta.value.slice(pos);
      ta.selectionStart = ta.selectionEnd = pos + html.length + 4;
      ta.focus();
      return;
    }
    edContent().focus();
    const sel = window.getSelection();
    const range = sel && sel.rangeCount > 0 ? sel.getRangeAt(0) : null;
    const wrap = document.createElement('div');
    wrap.innerHTML = html;
    const node = wrap.firstChild;
    if (range && edContent().contains(range.commonAncestorContainer)) {
      range.insertNode(node);
      range.setStartAfter(node);
      sel.removeAllRanges();
      sel.addRange(range);
    } else {
      edContent().appendChild(node);
    }
  }

  function openModal() {
    document.getElementById('modalTitle').innerText = 'Ajouter un chapitre';
    document.getElementById('chapterForm').reset();
    document.getElementById('chapterId').value = '';
    document.getElementById('order_index').value = '0';
    document.getElementById('quiz_enabled').checked = false;
    document.getElementById('chapter_published').checked = true;
    setEditorHTML('<p>Commencez à écrire le contenu ou insérez un bloc depuis la barre d\'outils…</p>');
    if (editorView === 'code') switchView('preview');
    document.getElementById('chapterModal').classList.add('open');
  }

  function closeModal() {
    document.getElementById('chapterModal').classList.remove('open');
  }

  function editChapterFromData(c) {
    document.getElementById('modalTitle').innerText = 'Modifier le chapitre';
    document.getElementById('chapterId').value = c.id;
    document.getElementById('title').value = c.title || '';
    document.getElementById('video_url').value = c.video_url || '';
    document.getElementById('image_url').value = c.image_url || '';
    document.getElementById('quiz_enabled').checked = c.quiz_enabled == 1;
    document.getElementById('description').value = c.description || '';
    document.getElementById('order_index').value = c.order_index || 0;
    setEditorHTML(c.content || '');
    if (editorView === 'code') switchView('preview');
    document.getElementById('chapterModal').classList.add('open');
  }

  function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
  }

  function loadChapters() {
    fetch('manage_content.php?action=list_chapters&module_id=' + moduleId)
      .then(function (r) {
        return r.text().then(function (t) {
          try { return JSON.parse(t); } catch (e) { throw new Error('Réponse invalide'); }
        });
      })
      .then(function (data) {
        const list = document.getElementById('chapterList');
        const count = document.getElementById('chapterCount');
        if (!Array.isArray(data) || !data.length) {
          count.textContent = '0 chapitre';
          list.innerHTML = '<div class="empty-state"><i class="fas fa-book-open"></i><p>Aucun chapitre. Cliquez sur <strong>Ajouter un chapitre</strong> pour commencer.</p></div>';
          return;
        }
        count.textContent = data.length + ' chapitre' + (data.length > 1 ? 's' : '');
        list.innerHTML = data.map(function (c, i) {
          return '<div class="chapter-item" data-chapter=\'' + JSON.stringify(c).replace(/'/g, '&#39;') + '\'>' +
            '<div class="chapter-left"><div class="chapter-index">' + (i + 1) + '</div><div>' +
            '<div class="chapter-title">' + escHtml(c.title) + '</div>' +
            '<div class="chapter-badges">' +
            (c.video_url ? '<span class="badge badge-video"><i class="fas fa-video"></i> Vidéo</span>' : '') +
            (c.image_url ? '<span class="badge badge-img"><i class="fas fa-image"></i> Image</span>' : '') +
            (c.quiz_enabled == 1 ? '<span class="badge badge-quiz"><i class="fas fa-circle-question"></i> Quiz</span>' : '') +
            '</div></div></div>' +
            '<div class="chapter-actions">' +
            '<button type="button" class="btn-edit" data-action="edit"><i class="fas fa-pen"></i> Modifier</button>' +
            '<button type="button" class="btn-del" data-action="delete" data-id="' + c.id + '"><i class="fas fa-trash"></i> Supprimer</button>' +
            '</div></div>';
        }).join('');
      })
      .catch(function (err) { console.error('Erreur:', err); });
  }

  function deleteChapter(id) {
    if (!confirm('Supprimer ce chapitre définitivement ?')) return;
    fetch('manage_content.php?action=delete_chapter', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id })
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) loadChapters();
        else alert(data.error || 'Erreur.');
      })
      .catch(function (err) { alert('Erreur : ' + err.message); });
  }

  document.addEventListener('DOMContentLoaded', function () {
    const btnOpen = document.getElementById('btnOpenChapterModal');
    if (btnOpen) btnOpen.addEventListener('click', openModal);

    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const id = btn.getAttribute('data-modal-close');
        if (id) document.getElementById(id).classList.remove('open');
      });
    });

    document.querySelector('.editor-toolbar').addEventListener('click', function (e) {
      const btn = e.target.closest('.tb-btn, .view-btn');
      if (!btn) return;
      if (btn.dataset.cmd) execCmd(btn.dataset.cmd);
      if (btn.dataset.heading) insertHeading(btn.dataset.heading);
      if (btn.dataset.block) insBlock(btn.dataset.block);
      if (btn.dataset.view) switchView(btn.dataset.view);
    });

    document.getElementById('chapterList').addEventListener('click', function (e) {
      const editBtn = e.target.closest('[data-action="edit"]');
      const delBtn = e.target.closest('[data-action="delete"]');
      const item = e.target.closest('.chapter-item');
      if (editBtn && item) {
        editChapterFromData(JSON.parse(item.getAttribute('data-chapter')));
      }
      if (delBtn) deleteChapter(delBtn.getAttribute('data-id'));
    });

    document.getElementById('chapterForm').addEventListener('submit', function (e) {
      e.preventDefault();
      document.getElementById('contentHidden').value = getEditorHTML();
      const formData = new FormData(this);
      if (!document.getElementById('quiz_enabled').checked) formData.set('quiz_enabled', '0');
      const action = document.getElementById('chapterId').value ? 'update_chapter' : 'add_chapter';
      fetch('manage_content.php?action=' + action, { method: 'POST', body: new URLSearchParams(formData) })
        .then(function (r) {
          return r.text().then(function (t) {
            try { return JSON.parse(t); } catch (e) { throw new Error('Réponse invalide'); }
          });
        })
        .then(function (data) {
          if (data.success) { closeModal(); loadChapters(); }
          else alert(data.error || 'Erreur.');
        })
        .catch(function (err) { alert('Erreur : ' + err.message); });
    });

    const avatar = document.getElementById('userAvatar');
    const dropdown = document.getElementById('userDropdown');
    if (avatar && dropdown) {
      avatar.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
      });
      document.addEventListener('click', function () {
        dropdown.classList.remove('open');
      });
    }

    loadChapters();
  });
})();
