(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.password-toggle').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const wrapper = btn.closest('.form-input-wrapper');
        if (!wrapper) return;
        const input = wrapper.querySelector('input[type="password"], input[type="text"]');
        const icon = btn.querySelector('i');
        if (!input || !icon) return;
        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.remove('fa-eye');
          icon.classList.add('fa-eye-slash');
        } else {
          input.type = 'password';
          icon.classList.remove('fa-eye-slash');
          icon.classList.add('fa-eye');
        }
      });
    });
  });
})();
