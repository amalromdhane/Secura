<?php
// ── login.php – entry point ──────────────────────────────────────────────
session_start();

require_once __DIR__ . '/../app/Core/Session.php';
require_once __DIR__ . '/../config/database.php';

use App\Core\Session;

// Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    Session::destroy();
    header('Location: login.php');
    exit();
}

// Already logged in → redirect
if (Session::isLoggedIn()) {
    header('Location: admin_dashboard.php');
    exit();
}

$error   = '';
$success = '';

// ── POST handler ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            require_once __DIR__ . '/../app/Models/User.php';
            require_once __DIR__ . '/../app/Controllers/AuthController.php';

            $pdo       = getDBConnection('secura');
            $auth      = new \App\Controllers\AuthController();
            $logged_in = $auth->login($email, $password);

            if ($logged_in) {
                $role = Session::get('user_role', 'user') ?? 'user';
                header('Location: ' . ($role === 'admin' ? 'admin_dashboard.php' : 'index.php'));
                exit();
            } else {
                $error = "Nom d'utilisateur ou mot de passe incorrect.";
            }
        } catch (Throwable $e) {
            $error = 'Erreur de connexion à la base de données.';
        }
    }
}

// ── VIEW ─────────────────────────────────────────────────────────────────
$pageTitle      = 'Connexion';
$pageSubtitle   = 'Accédez à votre espace sécurisé';
$submitLabel    = 'Se Connecter';
$footerText     = "Pas encore de compte?";
$footerLinkLabel= "S'inscrire";
$footerLink     = 'register.php';

// Form body for the auth layout
$formBody = '
<div class="form-group">
    <label class="form-label" for="email">Adresse Email</label>
    <div class="form-input-wrapper">
        <i class="fas fa-envelope input-icon"></i>
        <input type="email" id="email" name="email" class="form-input" required
               placeholder="votre@email.com"
               value="' . htmlspecialchars($_POST['email'] ?? '') . '">
    </div>
</div>

<div class="form-group">
    <label class="form-label" for="password">Mot de Passe</label>
    <div class="form-input-wrapper">
        <i class="fas fa-lock input-icon"></i>
        <input type="password" id="password" name="password" class="form-input" required
               placeholder="••••••••">
        <button type="button" class="password-toggle" onclick="togglePassword(this)">
            <i class="fas fa-eye"></i></button>
    </div>
</div>';

include __DIR__ . '/../views/layouts/auth_layout.php';
