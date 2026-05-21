(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
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

    const alert = document.querySelector('.alert');
    if (alert) {
      setTimeout(function () {
        alert.style.opacity = '0';
        setTimeout(function () { alert.remove(); }, 500);
      }, 5000);
    }

    const btnCreate = document.getElementById('btnOpenCreate');
    if (btnCreate) {
      btnCreate.addEventListener('click', function () {
        document.getElementById('createModal').classList.add('show');
      });
    }

    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const id = btn.getAttribute('data-modal-close');
        if (id) document.getElementById(id).classList.remove('show');
      });
    });

    document.querySelectorAll('.user-row [data-action]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const action = btn.getAttribute('data-action');
        if (action === 'edit') {
          document.getElementById('edit_user_id').value = btn.getAttribute('data-id');
          document.getElementById('edit_username').value = btn.getAttribute('data-username');
          document.getElementById('edit_email').value = btn.getAttribute('data-email');
          document.getElementById('edit_role').value = btn.getAttribute('data-role');
          document.getElementById('editModal').classList.add('show');
        }
        if (action === 'delete') {
          document.getElementById('delete_user_id').value = btn.getAttribute('data-id');
          document.getElementById('delete_username').textContent = btn.getAttribute('data-username');
          document.getElementById('deleteModal').classList.add('show');
        }
      });
    });

    document.addEventListener('click', function (e) {
      if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
      }
    });

    const search = document.getElementById('userSearch');
    if (search) {
      search.addEventListener('input', function () {
        const filter = this.value.toLowerCase();
        document.querySelectorAll('.user-row').forEach(function (row) {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(filter) ? '' : 'none';
        });
      });
    }
  });
})();
