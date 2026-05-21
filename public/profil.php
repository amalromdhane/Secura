<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php'); exit();
}

$username   = $_SESSION['username']   ?? 'User';
$user_email = $_SESSION['user_email'] ?? '';
$user_id    = $_SESSION['user_id']    ?? 0;
$user_role  = $_SESSION['user_role']  ?? 'user';

// Ensure user avatar is set in session
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true && empty($_SESSION['user_avatar'])) {
    $pdo = getDBConnection('secura');
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $avatar = $stmt->fetchColumn();
    if ($avatar) {
        $_SESSION['user_avatar'] = $avatar;
    }
}

// Include database configuration
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $pdo = getDBConnection('secura');
    $errors = [];

    if ($_POST['action'] === 'update_profile') {
        $new_username = trim($_POST['username'] ?? '');
        $new_email = trim($_POST['email'] ?? '');

        if (empty($new_username)) $errors[] = 'Nom d\'utilisateur requis.';
        if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email valide requis.';

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmt->execute([$new_username, $new_email, $user_id]);

                // Update session
                $_SESSION['username'] = $new_username;
                $_SESSION['user_email'] = $new_email;

                $success = 'Profil mis à jour avec succès.';
            } catch (Exception $e) {
                $errors[] = 'Erreur lors de la mise à jour.';
            }
        }
    } elseif ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password)) $errors[] = 'Mot de passe actuel requis.';
        if (empty($new_password)) $errors[] = 'Nouveau mot de passe requis.';
        if ($new_password !== $confirm_password) $errors[] = 'Les mots de passe ne correspondent pas.';
        if (strlen($new_password) < 6) $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';

        if (empty($errors)) {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($current_password, $user['password_hash'])) {
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$new_hash, $user_id]);
                    $success = 'Mot de passe changé avec succès.';
                } else {
                    $errors[] = 'Mot de passe actuel incorrect.';
                }
            } catch (Exception $e) {
                $errors[] = 'Erreur lors du changement de mot de passe.';
            }
        }
    } elseif ($_POST['action'] === 'upload_avatar') {
        // Check if user is still logged in
        if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
            $errors[] = 'Session expirée. Veuillez vous reconnecter.';
        } elseif ($user_id == 0) {
            $errors[] = 'Utilisateur non identifié.';
        } else {
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
                $errors[] = 'Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.';
            } elseif ($_FILES['avatar']['size'] > $max_size) {
                $errors[] = 'Fichier trop volumineux. Maximum 2MB.';
            } else {
                $upload_dir = 'uploads/avatars/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_extension;
                $filepath = $upload_dir . $filename;

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filepath)) {

                    try {
                        // Check if avatar column exists, if not, add it
                        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'avatar'");
                        $stmt->execute();
                        if ($stmt->rowCount() == 0) {
                            $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");
                        }

                        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                        $stmt->execute([$filepath, $user_id]);

                        // Update session
                        $_SESSION['user_avatar'] = $filepath;

                        $success = 'Photo de profil mise à jour avec succès.';
                    } catch (Exception $e) {
                        $errors[] = 'Erreur lors de la sauvegarde de la photo.';
                    }
                } else {
                    $errors[] = 'Erreur lors du téléchargement du fichier.';
                }
            }
        } else {
            $upload_error = isset($_FILES['avatar']) ? $_FILES['avatar']['error'] : 'No file data';
            $errors[] = 'Aucun fichier sélectionné ou erreur de téléchargement.';
        }
        }
    }
}

// Get current user data
$pdo = getDBConnection('secura');
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <base href="/Secura/public/">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mon Profil – Secura</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --cyber-primary: #0d6efd;
      --cyber-secondary: #6f42c1;
      --cyber-accent: #00d4ff;
      --cyber-success: #20c997;
      --cyber-warning: #ffc107;
      --cyber-danger: #ff4757;
      --cyber-dark: #f8fafc;
      --cyber-card: #ffffff;
      --cyber-border: rgba(13, 110, 253, 0.2);
      --cyber-gradient: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
      --cyber-glow: 0 0 20px rgba(13, 110, 253, 0.4);
      --text-primary: #333333;
      --text-secondary: #666666;
      --neon-cyan: #00d4ff;
      --glow-cyan: 0 0 20px rgba(0, 212, 255, 0.4);
    }
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
      background: var(--cyber-dark);
      color: var(--text-primary);
      min-height: 100vh;
      margin: 0;
      display: flex;
      background-image:
        radial-gradient(circle at 10% 20%, rgba(13, 110, 253, 0.03) 0%, transparent 20%),
        radial-gradient(circle at 90% 80%, rgba(111, 66, 193, 0.03) 0%, transparent 20%),
        linear-gradient(135deg, rgba(30,58,138,0.3) 0%, rgba(30,64,175,0.25) 25%, rgba(16,185,129,0.2) 50%, rgba(245,158,11,0.25) 75%, rgba(239,68,68,0.3) 100%);
    }



    /* Sidebar */
    .sidebar {
    width: 250px;
    background: var(--cyber-card);
    background-image: linear-gradient(135deg, rgba(30, 58, 138, 0.3) 0%, rgba(30, 64, 175, 0.25) 25%, rgba(16, 185, 129, 0.2) 50%, rgba(245, 158, 11, 0.25) 75%, rgba(239, 68, 68, 0.3) 100%);
    border-right: 1px solid rgba(0, 0, 0, 0.1);
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    padding: 20px;
    box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
    z-index: 100;
}
    .sidebar-header {
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 1px solid rgba(0, 255, 255, 0.2);
    }
    .sidebar-header h2 {
      color: var(--neon-cyan);
      font-size: 18px;
      margin: 0;
    }
    .sidebar-menu {
      list-style: none;
      padding: 0;
    }
    .sidebar-menu li {
      margin-bottom: 10px;
    }
    .sidebar-menu a {
      display: flex;
      align-items: center;
      padding: 12px 15px;
      color: var(--text-secondary);
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.3s ease;
      font-weight: 500;
    }
    .sidebar-menu a:hover, .sidebar-menu a.active {
      background: rgba(0, 255, 255, 0.1);
      color: var(--neon-cyan);
      border-left: 3px solid var(--neon-cyan);
    }
    .sidebar-menu a i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }

    /* navbar2*/
    .navbar2{
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(12px);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
      color: var(--text-primary);
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-radius: 12px;
      margin-left: 150px;
    }
    .sidebar-header {
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 1px solid rgba(0, 255, 255, 0.2);
    }
    .sidebar-header h2 {
      color: var(--neon-cyan);
      font-size: 18px;
      margin: 0;
    }
    .sidebar-menu {
      list-style: none;
      padding: 0;
    }
    .sidebar-menu li {
      margin-bottom: 10px;
    }
    .sidebar-menu a {
      display: flex;
      align-items: center;
      padding: 12px 15px;
      color: var(--text-secondary);
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.3s ease;
      font-weight: 500;
    }
    .sidebar-menu a:hover, .sidebar-menu a.active {
      background: rgba(0, 255, 255, 0.1);
      color: var(--neon-cyan);
      border-left: 3px solid var(--neon-cyan);
    }
    .sidebar-menu a i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }

    /* navbar2*/
    .navbar2{
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(12px);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
      color: var(--text-primary);
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-radius: 12px;
    }

    .navbar2hover {
      box-shadow: var(--shadow-strong), 0 0 60px rgba(13, 110, 253, 0.15);
    }

    .navbar2h1 {
      font-size: 24px;
      color: var(--cyber-accent);
      margin: 0;
      font-weight: 700;
    }
    .user-info { display:flex; align-items:center; gap:14px; position: relative; }
    .user-avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      cursor: pointer;
      border: 3px solid var(--cyber-accent);
      transition: all 0.3s ease;
      object-fit: cover;
      background: var(--cyber-card);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    .user-avatar:hover {
      border-color: var(--cyber-primary);
      transform: scale(1.1);
      box-shadow: 0 4px 16px rgba(0, 212, 255, 0.3);
    }

    .user-dropdown {
      position: absolute;
      top: 100%;
      right: 0;
      margin-top: 8px;
      background: rgba(255, 255, 255, 0.98);
      border: 1px solid rgba(0, 0, 0, 0.1);
      border-radius: 12px;
      min-width: 200px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
      backdrop-filter: blur(15px);
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s ease;
      z-index: 9999;
      padding: 8px 0;
    }

    .user-dropdown.open {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .user-dropdown-item {
      display: block;
      padding: 12px 20px;
      color: var(--text-secondary);
      text-decoration: none;
      transition: all 0.3s ease;
      border-radius: 6px;
      margin: 2px 8px;
    }
    .user-dropdown-item:hover {
      background: rgba(0, 212, 255, 0.1);
      color: var(--text-primary);
      transform: translateX(4px);
    }

    .user-dropdown-item i {
      width: 18px;
      text-align: center;
      font-size: 16px;
    }

    /* Main content */
    .main-content {
      margin-left: 150px;
      margin-top: 20px;
      flex: 1;
      padding: 30px 20px;
      min-height: 100vh;
      position: relative;
    }

    .container {
      max-width: 900px;
      margin: 0 auto;
      padding: 0;
    }

    /* Profile sections */
    .profile-section {
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(0, 0, 0, 0.1);
      border-radius: var(--border-radius-large);
      backdrop-filter: blur(10px);
      padding: 30px;
      margin-bottom: 30px;
      transition: var(--transition);
      position: relative;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .profile-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--cyber-gradient);
      border-radius: var(--border-radius-large) var(--border-radius-large) 0 0;
    }

    .profile-section:hover {
      border-color: rgba(0, 0, 0, 0.2);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      transform: translateY(-2px);
    }

    .profile-section h2 {
      font-size: 22px;
      color: var(--text-primary);
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 2px solid var(--cyber-accent);
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 600;
    }

    .profile-section h2 i {
      color: var(--cyber-accent);
      font-size: 24px;
    }

    /* Avatar section */
    .avatar-section {
      text-align: center;
      margin-bottom: 30px;
      position: relative;
    }

    .current-avatar {
      width: 140px;
      height: 140px;
      border-radius: 50%;
      border: 4px solid var(--cyber-accent);
      object-fit: cover;
      margin-bottom: 20px;
      transition: var(--transition);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }

    .current-avatar:hover {
      transform: scale(1.05);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    .avatar-upload {
      display: flex;
      gap: 10px;
      align-items: center;
      flex-wrap: wrap;
      margin-top: 20px;
    }

    /* Input file is now hidden with display: none in HTML */

    .avatar-upload-btn {
      background: var(--cyber-gradient);
      color: #fff;
      border: none;
      padding: 14px 28px;
      border-radius: var(--border-radius);
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 10px;
      box-shadow: var(--shadow-medium), 0 0 20px rgba(13, 110, 253, 0.2);
      position: relative;
      overflow: hidden;
    }

    .avatar-upload-btn::before {
      content: '';
      position: absolute;
      inset: 0;
      background: var(--cyber-gradient-secondary);
      opacity: 0;
      transition: var(--transition);
    }

    .avatar-upload-btn:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-strong), 0 0 30px rgba(13, 110, 253, 0.3);
    }

    .avatar-upload-btn:hover::before {
      opacity: 0.2;
    }

    .avatar-upload-btn i {
      font-size: 18px;
    }

    /* Form styles */
    .form-group {
      margin-bottom: 24px;
      position: relative;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      font-size: 14px;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .form-group label i {
      color: var(--cyber-accent);
      font-size: 16px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 14px 18px;
      background: rgba(255, 255, 255, 0.9);
      border: 2px solid var(--cyber-border);
      border-radius: var(--border-radius);
      color: var(--text-primary);
      font-size: 15px;
      transition: var(--transition);
      backdrop-filter: blur(10px);
      box-shadow: var(--shadow-light);
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--cyber-accent);
      box-shadow: var(--shadow-medium), 0 0 0 4px rgba(0, 212, 255, 0.1);
      background: rgba(255, 255, 255, 1);
      transform: translateY(-1px);
    }

    .form-group input[readonly] {
      background: rgba(18, 24, 38, 0.5);
      cursor: not-allowed;
      border-color: var(--text-muted);
      color: var(--text-muted);
    }

    /* Buttons */
    .btn {
      background: var(--cyber-gradient);
      color: #fff;
      border: none;
      padding: 14px 28px;
      border-radius: var(--border-radius);
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 10px;
      box-shadow: var(--shadow-medium);
      position: relative;
      overflow: hidden;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .btn::before {
      content: '';
      position: absolute;
      inset: 0;
      background: var(--cyber-gradient-secondary);
      opacity: 0;
      transition: var(--transition);
    }

    .btn:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-strong);
    }

    .btn:hover::before {
      opacity: 0.2;
    }

    .btn:active {
      transform: translateY(-1px);
    }

    .btn-secondary {
      background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
      color: var(--text-secondary);
      border: 2px solid var(--cyber-border);
      box-shadow: var(--shadow-light);
    }

    .btn-secondary:hover {
      background: linear-gradient(135deg, rgba(13, 110, 253, 0.1) 0%, rgba(111, 66, 193, 0.1) 100%);
      border-color: var(--cyber-accent);
      color: var(--cyber-accent);
      box-shadow: var(--shadow-medium), 0 0 20px rgba(0, 212, 255, 0.1);
    }

    .btn i {
      font-size: 18px;
    }

    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes glow {
      0%, 100% {
        box-shadow: var(--shadow-medium), 0 0 20px rgba(13, 110, 253, 0.1);
      }
      50% {
        box-shadow: var(--shadow-medium), 0 0 30px rgba(13, 110, 253, 0.2);
      }
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-20px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .profile-section {
      animation: fadeInUp 0.6s ease-out forwards;
      opacity: 0;
    }

    .profile-section:nth-child(1) { animation-delay: 0.1s; }
    .profile-section:nth-child(2) { animation-delay: 0.2s; }
    .profile-section:nth-child(3) { animation-delay: 0.3s; }

    .form-group {
      animation: slideIn 0.4s ease-out forwards;
      opacity: 0;
    }

    .profile-section:nth-child(1) .form-group { animation-delay: 0.3s; }
    .profile-section:nth-child(2) .form-group { animation-delay: 0.4s; }
    .profile-section:nth-child(3) .form-group { animation-delay: 0.5s; }

    .current-avatar {
      animation: glow 3s ease-in-out infinite;
    }



    /* Toast notifications */
    .toast {
      position: fixed;
      top: 100px;
      right: 20px;
      padding: 12px 20px;
      border-radius: var(--border-radius);
      color: white;
      font-weight: 500;
      box-shadow: var(--shadow-strong);
      z-index: 10000;
      transform: translateX(400px);
      transition: transform 0.3s ease;
      backdrop-filter: blur(10px);
    }

    .toast.toast-success {
      background: var(--cyber-success);
      border-left: 4px solid rgba(255, 255, 255, 0.3);
    }

    .toast.toast-error {
      background: var(--cyber-danger);
      border-left: 4px solid rgba(255, 255, 255, 0.3);
    }

    .toast:not(.toast-hide) {
      transform: translateX(0);
    }

    .toast-hide {
      transform: translateX(400px);
    }

    /* Alert */
    .alert {
      padding: 16px 20px;
      border-radius: var(--border-radius);
      margin-bottom: 24px;
      font-size: 15px;
      backdrop-filter: blur(10px);
      border: 1px solid transparent;
      box-shadow: var(--shadow-medium);
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 500;
    }

    .alert i {
      font-size: 18px;
    }

    .alert-success {
      background: rgba(32, 201, 151, 0.1);
      color: var(--cyber-success);
      border-color: rgba(32, 201, 151, 0.3);
      box-shadow: var(--shadow-medium);
    }

    .alert-error {
      background: rgba(255, 71, 87, 0.1);
      color: var(--cyber-danger);
      border-color: rgba(255, 71, 87, 0.3);
      box-shadow: var(--shadow-medium);
    }

    /* Back button */
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      background: rgba(255, 255, 255, 0.9);
      color: var(--text-secondary);
      text-decoration: none;
      padding: 12px 20px;
      border-radius: var(--border-radius);
      border: 2px solid rgba(0, 0, 0, 0.1);
      margin-bottom: 30px;
      transition: var(--transition);
      box-shadow: var(--shadow-light);
      font-weight: 500;
      position: relative;
      overflow: hidden;
    }

    .back-btn::before {
      content: '';
      position: absolute;
      inset: 0;
      background: var(--cyber-gradient);
      opacity: 0;
      transition: var(--transition);
    }

    .back-btn:hover {
      background: rgba(0, 0, 0, 0.05);
      border-color: var(--cyber-accent);
      color: var(--cyber-accent);
      box-shadow: var(--shadow-medium);
      transform: translateX(-5px);
    }

    .back-btn:hover::before {
      opacity: 0.1;
    }

    .back-btn i {
      font-size: 16px;
      transition: var(--transition);
    }

    .back-btn:hover i {
      transform: translateX(-3px);
    }
  </style>
</head>
<body>

   <aside class="sidebar">
    <div class="sidebar-header">
      <h2>🔐 Secura</h2>
    </div>
    <ul class="sidebar-menu">
      <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
      <li><a href="all_modules.php"><i class="fas fa-layer-group"></i> Modules</a></li>
      <li><a href="#"><i class="fas fa-cog"></i> Paramètres</a></li>
      <li><a href="admin_users.php"><i class="fas fa-users"></i> Utilisateurs</a></li>
    </ul>
  </aside>

<!-- Main Content -->
  <div class="main-content">
    <nav class="navbar2">
      <h1>Admin Dashboard</h1>
      <div class="user-info">
        <img src="<?php echo !empty($_SESSION['user_avatar']) ? htmlspecialchars($_SESSION['user_avatar']) : 'assets/images/default-avatar.svg'; ?>"
              alt="Avatar" class="user-avatar" id="userAvatar">
        <div class="user-dropdown" id="userDropdown">
          <a href="profil.php" class="user-dropdown-item">
            <i class="fas fa-user"></i> Mon Profil
          </a>
          <a href="admin_users.php" class="user-dropdown-item">
            <i class="fas fa-users"></i> Gestion Utilisateurs
          </a>
          <a href="login.php?action=logout" class="user-dropdown-item">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
          </a>
        </div>
      </div>
    </nav>

  <!-- Main Content -->
  <div class="main-content">
    <div class="container">
     

      <?php if (!empty($success)): ?>
        <div class="alert alert-success">
          ✅ <?php echo htmlspecialchars($success); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          ❌ <?php echo htmlspecialchars(implode(' ', $errors)); ?>
        </div>
      <?php endif; ?>

      <!-- Avatar Section -->
      <div class="profile-section">
        <h2><i class="fas fa-camera"></i> Photo de Profil</h2>
        <div class="avatar-section">
          <img src="<?php echo !empty($user_data['avatar']) ? htmlspecialchars($user_data['avatar']) : 'assets/images/default-avatar.svg'; ?>" 
               alt="Avatar actuel" class="current-avatar" id="currentAvatar">
          <form method="post" enctype="multipart/form-data" style="margin-top: 20px;">
            <input type="hidden" name="action" value="upload_avatar">
            <div class="avatar-upload">
              <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display: none;">
              <button type="button" class="avatar-upload-btn" id="changePhotoBtn">
                <i class="fas fa-upload"></i> Changer la photo
              </button>
              <button type="submit" class="avatar-upload-btn" id="uploadBtn" style="display: none;">
                <i class="fas fa-check"></i> Télécharger
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Profile Information -->
      <div class="profile-section">
        <h2><i class="fas fa-user-edit"></i> Informations Personnelles</h2>
        <form method="post">
          <input type="hidden" name="action" value="update_profile">
          
          <div class="form-group">
            <label for="username"><i class="fas fa-user"></i> Nom d'utilisateur *</label>
            <input type="text" id="username" name="username" 
                   value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" required>
          </div>
          
          <div class="form-group">
            <label for="email"><i class="fas fa-envelope"></i> Email *</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
          </div>
          
          <div class="form-group">
            <label for="role"><i class="fas fa-shield-alt"></i> Rôle</label>
            <input type="text" value="<?php echo htmlspecialchars(ucfirst($user_data['role'] ?? 'user')); ?>" readonly 
                   style="background: rgba(18, 24, 38, 0.5); cursor: not-allowed;">
          </div>
          
          <button type="submit" class="btn">
            <i class="fas fa-save"></i> Mettre à jour le profil
          </button>
        </form>
      </div>

      <!-- Change Password -->
      <div class="profile-section">
        <h2><i class="fas fa-lock"></i> Changer le Mot de Passe</h2>
        <form method="post">
          <input type="hidden" name="action" value="change_password">
          
          <div class="form-group">
            <label for="current_password"><i class="fas fa-key"></i> Mot de passe actuel *</label>
            <input type="password" id="current_password" name="current_password" required>
          </div>
          
          <div class="form-group">
            <label for="new_password"><i class="fas fa-key"></i> Nouveau mot de passe *</label>
            <input type="password" id="new_password" name="new_password" required minlength="6">
          </div>
          
          <div class="form-group">
            <label for="confirm_password"><i class="fas fa-key"></i> Confirmer le nouveau mot de passe *</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
          </div>
          
          <button type="submit" class="btn">
            <i class="fas fa-key"></i> Changer le mot de passe
          </button>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
      // User dropdown functionality
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

// Handle change photo button click
      const changePhotoBtn = document.getElementById('changePhotoBtn');
      const uploadBtn = document.getElementById('uploadBtn');
      const avatarInput = document.getElementById('avatarInput');

      if (changePhotoBtn && avatarInput) {
        changePhotoBtn.addEventListener('click', function() {
          avatarInput.click();
        });
      }

      // Preview avatar on file select and update button text
      if (avatarInput) {
        avatarInput.addEventListener('change', function (e) {
          const file = e.target.files[0];

          if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
              const currentAvatar = document.getElementById('currentAvatar');
              if (currentAvatar) {
                currentAvatar.src = e.target.result;
              }
            };
            reader.readAsDataURL(file);

            // Show upload button and hide change photo button
            if (changePhotoBtn && uploadBtn) {
              changePhotoBtn.style.display = 'none';
              uploadBtn.style.display = 'inline-block';
              uploadBtn.innerHTML = '<i class="fas fa-check"></i> Télécharger ' + file.name;
            }
          } else {
            // Reset buttons if no file selected
            if (changePhotoBtn && uploadBtn) {
              changePhotoBtn.style.display = 'inline-block';
              uploadBtn.style.display = 'none';
            }
          }
        });
      }

      // Validate form submission
      const avatarForm = document.querySelector('form[action*="upload_avatar"]');
      if (avatarForm) {
        avatarForm.addEventListener('submit', function (e) {
          const fileInput = document.getElementById('avatarInput');
          if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            e.preventDefault();
            showToast('Veuillez sélectionner une photo d\'abord', 'error');
            return false;
          }

          // Show loading message
          showToast('Téléchargement de la photo en cours...', 'success');
        });
      }
    });

    // Check for PHP errors and show appropriate alerts
    <?php if (!empty($errors) && isset($_POST['action']) && $_POST['action'] === 'upload_avatar'): ?>
      <?php foreach ($errors as $error): ?>
        showToast('<?php echo addslashes($error); ?>', 'error');
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($success) && isset($_POST['action']) && $_POST['action'] === 'upload_avatar'): ?>
      showToast('<?php echo addslashes($success); ?>', 'success');
    <?php endif; ?>

    // Toast function
    function showToast(msg, type = 'success') {
      document.querySelectorAll('.toast').forEach(t => t.remove());
      const el = document.createElement('div');
      el.className = `toast toast-${type}`;
      el.textContent = msg;
      document.body.appendChild(el);
      setTimeout(() => {
        el.classList.add('toast-hide');
        setTimeout(() => el.remove(), 350);
      }, 3000);
    }
  </script>
</body>
</html>


