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
  });
})();
