<?php
session_start();

require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit();
}

$registerUsername = trim($_POST['username'] ?? '');
$email            = trim($_POST['email'] ?? '');
$userPassword     = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role             = 'user';

if ($registerUsername === '' || empty($email) || empty($userPassword) || empty($confirm_password)) {
    $_SESSION['register_error'] = 'Veuillez remplir tous les champs obligatoires.';
    header('Location: ../register.php');
    exit();
}

if ($userPassword !== $confirm_password) {
    $_SESSION['register_error'] = 'Les mots de passe ne correspondent pas.';
    header('Location: ../register.php');
    exit();
}

if (strlen($userPassword) < 8) {
    $_SESSION['register_error'] = 'Le mot de passe doit contenir au moins 8 caractères.';
    header('Location: ../register.php');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_error'] = 'Veuillez entrer une adresse email valide.';
    header('Location: ../register.php');
    exit();
}

try {
    $pdo = getDBConnection('cyber');

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['register_error'] = 'Cette adresse email est déjà utilisée.';
        header('Location: ../register.php');
        exit();
    }

    $password_hash = password_hash($userPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$registerUsername, $email, $password_hash, $role]);

    $_SESSION['register_success'] = 'Compte créé avec succès! <a href="login.php">Se connecter</a>';
    header('Location: ../register.php');
    exit();
} catch (PDOException $e) {
    $_SESSION['register_error'] = 'Erreur de connexion à la base de données.';
    header('Location: ../register.php');
    exit();
}
