(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('contactForm');
    if (!form) return;

    const alertBox = document.getElementById('contactAlert');
    const submitBtn = document.getElementById('contactSubmit');

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      const payload = {
        full_name: document.getElementById('contactName').value.trim(),
        email: document.getElementById('contactEmail').value.trim(),
        subject: document.getElementById('contactSubject').value,
        message: document.getElementById('contactMessage').value.trim(),
        newsletter: document.getElementById('newsletter').checked ? 1 : 0,
      };

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Envoi en cours…';
      }

      hideAlert();

      fetch('traitement/contact_traitement.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          if (data.success) {
            showAlert(data.message, 'success');
            form.reset();
            const subject = document.getElementById('contactSubject');
            if (subject) subject.selectedIndex = 0;
          } else {
            showAlert(data.error || 'Une erreur est survenue.', 'error');
          }
        })
        .catch(function () {
          showAlert('Erreur de connexion. Vérifiez que le serveur est démarré.', 'error');
        })
        .finally(function () {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-send-check me-2"></i> Envoyer le message';
          }
        });
    });

    function showAlert(text, type) {
      if (!alertBox) return;
      alertBox.className = 'contact-alert contact-alert-' + type;
      alertBox.textContent = '';
      const icon = document.createElement('i');
      icon.className = 'bi bi-' + (type === 'success' ? 'check-circle' : 'exclamation-triangle') + ' me-2';
      alertBox.appendChild(icon);
      alertBox.appendChild(document.createTextNode(' ' + text));
      alertBox.style.display = 'flex';
      alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideAlert() {
      if (!alertBox) return;
      alertBox.style.display = 'none';
      alertBox.innerHTML = '';
    }
  });
})();
