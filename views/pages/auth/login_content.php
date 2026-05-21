<?php
/** @var string|null $error */
/** @var string|null $success */
extract(get_defined_vars());
?>
<div class="auth-card">
    <div class="auth-header">
        <div class="auth-logo"><i class="fas fa-shield-alt"></i></div>
        <h1 class="auth-title">Connexion</h1>
        <p class="auth-subtitle">Accédez à votre espace sécurisé</p>
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
                       placeholder="••••••••">
                <button type="button" class="password-toggle" onclick="togglePassword()">
                    <i class="fas fa-eye"></i></button>
            </div>
        </div>

        <button type="submit" class="btn-auth">Se Connecter</button>
    </form>
    <?php endif; ?>

    <div class="auth-footer">
        <p>Pas encore de compte? <a href="<?= $registerUrl ?? 'register.php' ?>">S'inscrire</a></p>
    </div>
</div>
