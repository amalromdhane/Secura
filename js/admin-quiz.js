(function () {
  'use strict';

  const moduleId = window.SECURA_MODULE_ID || 0;

  function apiPost(url, payload) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function (r) { return r.json(); });
  }

  function loadQuestions() {
    fetch('manage_content.php?action=list_quiz&module_id=' + moduleId)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        const list = document.getElementById('questionList');
        if (!data.length) {
          list.innerHTML = '<div style="text-align:center;padding:3rem 1rem;color:var(--text-muted);"><div style="font-size:3rem;margin-bottom:1rem;opacity:0.5;">📝</div><h3 style="font-size:1.25rem;font-weight:600;margin-bottom:0.5rem;color:var(--text-secondary);">Aucune question</h3><p style="margin:0;font-size:0.875rem;">Cliquez sur « Ajouter une question » pour commencer.</p></div>';
          return;
        }
        list.innerHTML = data.map(function (q) {
          return '<div class="question-item" data-question=\'' + JSON.stringify(q).replace(/'/g, '&#39;') + '\'>' +
            '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;">' +
            '<div style="flex:1;margin-right:1rem;"><h4 style="font-size:1rem;font-weight:600;color:var(--text-primary);margin:0 0 0.5rem 0;line-height:1.4;">' + q.question_text + '</h4>' +
            '<div style="display:flex;gap:0.5rem;flex-wrap:wrap;"><span style="background:var(--admin-light);color:var(--text-secondary);padding:0.25rem 0.5rem;border-radius:6px;font-size:0.75rem;font-weight:500;">' + q.options.length + ' options</span></div></div>' +
            '<div style="display:flex;gap:0.5rem;flex-shrink:0;">' +
            '<button type="button" class="btn" data-action="edit" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:white;padding:0.5rem 1rem;font-size:0.8rem;">Modifier</button>' +
            '<button type="button" class="btn btn-danger" data-action="delete" data-id="' + q.id + '" style="padding:0.5rem 1rem;font-size:0.8rem;">Supprimer</button>' +
            '</div></div>' +
            '<div style="background:rgba(241,245,249,0.5);border-radius:8px;padding:1rem;">' +
            q.options.map(function (o) {
              return '<div class="option-item' + (o.is_correct == 1 ? ' correct' : '') + '" style="display:flex;align-items:center;gap:0.5rem;padding:0.25rem 0;">' + o.option_text + '</div>';
            }).join('') +
            '</div></div>';
        }).join('');
      });
  }

  function addOptionInput(text, isCorrect) {
    text = text || '';
    isCorrect = !!isCorrect;
    const container = document.getElementById('optionsContainer');
    const div = document.createElement('div');
    div.className = 'option-input-row';
    div.innerHTML = '<input type="radio" name="is_correct"' + (isCorrect ? ' checked' : '') + ' style="width:auto;">' +
      '<input type="text" class="option-text" value="' + text.replace(/"/g, '&quot;') + '" placeholder="Texte de l\'option" required>' +
      '<button type="button" class="btn btn-danger btn-remove-option" style="padding:0.375rem 0.75rem;font-size:1rem;min-width:auto;">×</button>';
    container.appendChild(div);
    div.querySelector('.btn-remove-option').addEventListener('click', function () {
      div.remove();
    });
  }

  function openModal() {
    document.getElementById('modalTitle').innerText = 'Ajouter une question';
    document.getElementById('questionId').value = '';
    document.getElementById('question_text').value = '';
    document.getElementById('order_index').value = '0';
    document.getElementById('optionsContainer').innerHTML = '';
    addOptionInput();
    addOptionInput();
    document.getElementById('questionModal').classList.add('open');
  }

  function closeModal() {
    document.getElementById('questionModal').classList.remove('open');
  }

  function editQuestion(q) {
    document.getElementById('modalTitle').innerText = 'Modifier la question';
    document.getElementById('questionId').value = q.id;
    document.getElementById('question_text').value = q.question_text;
    document.getElementById('order_index').value = q.order_index;
    document.getElementById('optionsContainer').innerHTML = '';
    q.options.forEach(function (o) {
      addOptionInput(o.option_text, o.is_correct == 1);
    });
    document.getElementById('questionModal').classList.add('open');
  }

  function deleteQuestion(id) {
    if (!confirm('Supprimer cette question ?')) return;
    apiPost('manage_content.php?action=delete_quiz_question', { id: id })
      .then(function () { loadQuestions(); });
  }

  document.addEventListener('DOMContentLoaded', function () {
    const btnOpen = document.getElementById('btnOpenQuizModal');
    if (btnOpen) btnOpen.addEventListener('click', openModal);

    const btnAddOpt = document.getElementById('btnAddQuizOption');
    if (btnAddOpt) btnAddOpt.addEventListener('click', function () { addOptionInput(); });

    document.querySelectorAll('[data-modal-close="questionModal"]').forEach(function (btn) {
      btn.addEventListener('click', closeModal);
    });

    document.getElementById('questionList').addEventListener('click', function (e) {
      const editBtn = e.target.closest('[data-action="edit"]');
      const delBtn = e.target.closest('[data-action="delete"]');
      const item = e.target.closest('.question-item');
      if (editBtn && item) {
        const q = JSON.parse(item.getAttribute('data-question'));
        editQuestion(q);
      }
      if (delBtn) deleteQuestion(delBtn.getAttribute('data-id'));
    });

    document.getElementById('questionForm').addEventListener('submit', function (e) {
      e.preventDefault();
      const options = [];
      document.querySelectorAll('.option-input-row').forEach(function (row) {
        options.push({
          text: row.querySelector('.option-text').value,
          is_correct: row.querySelector('input[type="radio"]').checked
        });
      });
      apiPost('manage_content.php?action=save_quiz_question', {
        id: document.getElementById('questionId').value,
        module_id: moduleId,
        question_text: document.getElementById('question_text').value,
        order_index: document.getElementById('order_index').value,
        options: options
      }).then(function (data) {
        if (data.success) {
          closeModal();
          loadQuestions();
        } else {
          alert(data.error);
        }
      });
    });

    const userAvatar = document.getElementById('userAvatar');
    const userDropdown = document.getElementById('userDropdown');
    if (userAvatar && userDropdown) {
      userAvatar.addEventListener('click', function (e) {
        e.stopPropagation();
        userDropdown.classList.toggle('open');
      });
      document.addEventListener('click', function () {
        userDropdown.classList.remove('open');
      });
    }

    loadQuestions();
  });
})();
