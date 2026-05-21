<?php
session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$email        = trim($_POST['email'] ?? '');
$userPassword = $_POST['password'] ?? null;

if ($email !== '' && $userPassword !== null) {
    try {
        $pdo = getDBConnection('secura');

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($userPassword, $user['password_hash'])) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id']        = $user['id'];
            $_SESSION['username']       = $user['username'];
            $_SESSION['user_role']      = $user['role'];
            $_SESSION['user_email']     = $user['email'];
            $_SESSION['user_avatar']    = $user['avatar'];

            $redirect = auth_safe_redirect(trim($_POST['redirect'] ?? ''));
            if ($redirect) {
                header('Location: ../' . $redirect);
                exit();
            }
            if ($user['role'] === 'admin') {
                header('Location: ../admin_dashboard.php');
                exit();
            }
            header('Location: ../index.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['login_error'] = 'Erreur de connexion à la base de données.';
        $failRedirect = auth_safe_redirect(trim($_POST['redirect'] ?? ''));
        header('Location: ../login.php' . ($failRedirect ? '?redirect=' . rawurlencode($failRedirect) : ''));
        exit();
    }

    $_SESSION['login_error'] = 'Nom d\'utilisateur ou mot de passe incorrect.';
    $failRedirect = auth_safe_redirect(trim($_POST['redirect'] ?? ''));
    header('Location: ../login.php' . ($failRedirect ? '?redirect=' . rawurlencode($failRedirect) : ''));
    exit();
}

header('Location: ../login.php');
exit();
