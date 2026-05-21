<div class="auth-header">
  <div class="auth-logo"><i class="fas fa-user-plus"></i></div>
  <h1 class="auth-title">Inscription</h1>
  <p class="auth-subtitle">Créez votre compte sécurisé</p>
</div>

<?php if ($error): ?>
<div class="error-message"><i class="fas fa-exclamation-circle"></i>
  <span><?= htmlspecialchars($error) ?></span></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="success-message"><i class="fas fa-check-circle"></i>
  <span><?= $success ?></span></div>
<?php else: ?>
<form method="POST" action="">
  <div class="form-group">
    <label class="form-label" for="username">Nom d'utilisateur</label>
    <div class="form-input-wrapper">
      <i class="fas fa-user input-icon"></i>
      <input type="text" id="username" name="username" class="form-input"
             placeholder="Votre nom d'utilisateur"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
    </div>
  </div>

  <div class="form-group">
    <label class="form-label" for="email">Adresse Email</label>
    <div class="form-input-wrapper">
      <i class="fas fa-envelope input-icon"></i>
      <input type="email" id="email" name="email" class="form-input" required
             placeholder="votre@email.com"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
  </div>

  <div class="form-group">
    <label class="form-label" for="password">Mot de Passe</label>
    <div class="form-input-wrapper">
      <i class="fas fa-lock input-icon"></i>
      <input type="password" id="password" name="password" class="form-input" required
             placeholder="Min. 8 caractères">
      <button type="button" class="password-toggle" onclick="togglePassword(this)"><i class="fas fa-eye"></i></button>
    </div>
  </div>

  <div class="form-group">
    <label class="form-label" for="confirm_password">Confirmer le Mot de Passe</label>
    <div class="form-input-wrapper">
      <i class="fas fa-lock input-icon"></i>
      <input type="password" id="confirm_password" name="confirm_password" class="form-input" required
             placeholder="Répétez votre mot de passe">
      <button type="button" class="password-toggle" onclick="togglePassword(this)"><i class="fas fa-eye"></i></button>
    </div>
  </div>

  <button type="submit" class="btn-auth">S'inscrire</button>
</form>
<?php endif; ?>

<div class="auth-footer">
  <p>Déjà un compte? <a href="login.php">Se connecter</a></p>
</div>