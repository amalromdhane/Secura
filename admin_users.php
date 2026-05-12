<?php
/**
 * Admin Users Management – Secura
 * User management: view, edit, delete users
 */
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php'); exit();
}
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: index.html'); exit();
}

$username = $_SESSION['username'] ?? 'Admin';
$user_email = $_SESSION['user_email'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

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
require_once 'includes/config.php';

$message = '';
$message_type = 'success';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $pdo = getDBConnection('secura');

    if ($_POST['action'] === 'delete_user' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];

        // Prevent admin from deleting themselves
        if ($user_id === $_SESSION['user_id']) {
            $message = 'Vous ne pouvez pas vous supprimer vous-même.';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = 'Utilisateur supprimé avec succès.';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Erreur lors de la suppression de l\'utilisateur.';
                $message_type = 'error';
            }
        }
    } elseif ($_POST['action'] === 'update_user' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        $new_username = trim($_POST['username'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $new_role = $_POST['role'] ?? 'user';

        if (empty($new_username) || empty($new_email)) {
            $message = 'Nom d\'utilisateur et email requis.';
            $message_type = 'error';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Email invalide.';
            $message_type = 'error';
        } elseif (!in_array($new_role, ['admin', 'user'])) {
            $message = 'Rôle invalide.';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                $stmt->execute([$new_username, $new_email, $new_role, $user_id]);
                $message = 'Utilisateur mis à jour avec succès.';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Erreur lors de la mise à jour de l\'utilisateur.';
                $message_type = 'error';
            }
        }
    } elseif ($_POST['action'] === 'create_user') {
        $new_username = trim($_POST['username'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $new_password = $_POST['password'] ?? '';
        $new_role = $_POST['role'] ?? 'user';

        if (empty($new_username) || empty($new_email) || empty($new_password)) {
            $message = 'Tous les champs sont requis.';
            $message_type = 'error';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Email invalide.';
            $message_type = 'error';
        } elseif (strlen($new_password) < 6) {
            $message = 'Le mot de passe doit contenir au moins 6 caractères.';
            $message_type = 'error';
        } elseif (!in_array($new_role, ['admin', 'user'])) {
            $message = 'Rôle invalide.';
            $message_type = 'error';
        } else {
            try {
                // Check if username or email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$new_username, $new_email]);
                if ($stmt->rowCount() > 0) {
                    $message = 'Nom d\'utilisateur ou email déjà utilisé.';
                    $message_type = 'error';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$new_username, $new_email, $hashed_password, $new_role]);
                    $message = 'Utilisateur créé avec succès.';
                    $message_type = 'success';
                }
            } catch (Exception $e) {
                $message = 'Erreur lors de la création de l\'utilisateur.';
                $message_type = 'error';
            }
        }
    }
}

// Get all users
$pdo = getDBConnection('secura');
$stmt = $pdo->prepare("SELECT id, username, email, role, created_at, avatar FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion des Utilisateurs – Secura</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

    *, *::before, *::after {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

    /* navbar2 */
    .navbar2 {
      background: rgba(255, 255, 255, 0.95);
      background-image: linear-gradient(135deg, rgba(30,58,138,0.1) 0%, rgba(30,64,175,0.08) 25%, rgba(16,185,129,0.06) 50%, rgba(245,158,11,0.08) 75%, rgba(239,68,68,0.1) 100%);
      backdrop-filter: blur(12px);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      color: var(--text-primary);
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-radius: 12px;
      border: 1px solid rgba(0, 0, 0, 0.1);
    }
    .navbar2 h1 { font-size:20px; color: var(--cyber-accent); margin: 0; }
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
    .user-dropdown-item i { margin-right: 8px; }

    
    .sidebar {
      width: 250px;
      background: var(--cyber-card);
      background-image: linear-gradient(135deg, rgba(30,58,138,0.3) 0%, rgba(30,64,175,0.25) 25%, rgba(16,185,129,0.2) 50%, rgba(245,158,11,0.25) 75%, rgba(239,68,68,0.3) 100%);
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
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }
    .sidebar-header h2 {
      color: var(--text-primary);
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
      background: rgba(0, 0, 0, 0.1);
      color: var(--text-primary);
      border-left: 3px solid var(--cyber-primary);
    }
    .sidebar-menu a i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }

    /* Main content */
    .main-content {
      margin-left: 250px;
      flex: 1;
      padding: 20px;
      min-height: 100vh;
    }
    .container { max-width:1200px; margin:0 auto; padding:0; }

    /* Floating shapes */
    .floating-shapes {
      position: fixed;
      inset: 0;
      pointer-events: none;
      z-index: 1;
      overflow: hidden;
    }
    .shape {
      position: absolute;
      background: rgba(0, 212, 255, 0.08);
      border: 1px solid rgba(0, 212, 255, 0.2);
      border-radius: 12px;
      animation: float 8s ease-in-out infinite;
      box-shadow: 0 0 20px rgba(0, 212, 255, 0.1);
    }
    .shape:nth-child(1) { width: 80px; height: 80px; top: 20%; left: 10%; }
    .shape:nth-child(2) { width: 60px; height: 60px; top: 60%; right: 15%; animation-delay: 2s; }
    .shape:nth-child(3) { width: 100px; height: 100px; bottom: 20%; left: 20%; animation-delay: 4s; }
    @keyframes float {
      0%, 100% { transform: translateY(0) rotate(0deg); }
      50% { transform: translateY(-20px) rotate(5deg); }
    }



    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .search-container {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .search-container .form-input {
      padding: 0.5rem 1rem;
      font-size: 0.9rem;
    }

    .page-title {
      font-size: 2rem;
      font-weight: 700;
      color: var(--neon-cyan);
    }

    .btn {
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: var(--border-radius);
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-primary {
      background: var(--cyber-gradient);
      color: white;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: var(--cyber-glow);
    }

    .btn-success {
      background: var(--cyber-success);
      color: white;
    }

    .btn-success:hover {
      background: #1aa085;
    }

    .btn-danger {
      background: var(--cyber-danger);
      color: white;
    }

    .btn-danger:hover {
      background: #e84118;
    }

    .btn-secondary {
      background: var(--text-secondary);
      color: white;
    }

    .btn-secondary:hover {
      background: #6c757d;
    }
    .btn-cancel {
      padding: 12px 24px;
      background: rgba(108, 117, 125, 0.1);
      color: #6c757d;
      border: 2px solid rgba(108, 117, 125, 0.3);
      border-radius: 12px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    .btn-cancel:hover {
      background: rgba(108, 117, 125, 0.2);
      transform: translateY(-2px);
    }
    .btn-submit {
      padding: 12px 24px;
      background: var(--cyber-gradient);
      color: white;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(13, 110, 253, 0.4);
    }

    .text-danger {
      color: var(--cyber-danger);
    }

    /* Alert Messages */
    .alert {
      padding: 1rem 1.5rem;
      border-radius: var(--border-radius);
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-weight: 500;
      transition: opacity 0.5s ease;
      opacity: 1;
    }

    .alert-success {
      background: rgba(32, 201, 151, 0.1);
      border-left: 4px solid var(--cyber-success);
      color: var(--cyber-success);
    }

    .alert-error {
      background: rgba(255, 71, 87, 0.1);
      border-left: 4px solid var(--cyber-danger);
      color: var(--cyber-danger);
    }

    /* Users Table */
    .users-container {
      background: var(--cyber-card);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-strong);
      overflow: hidden;
    }

    .users-header {
      padding: 1.5rem;
      border-bottom: 1px solid var(--cyber-border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .users-container {
      border:1px solid rgba(13, 110, 253, 0.3);
      border-radius:12px;
      overflow:hidden;
      backdrop-filter: blur(10px);
    }
    .users-header {
      display:grid;
      grid-template-columns:80px 2fr 2fr 1fr 1fr 1fr;
      background: var(--cyber-gradient);
      color:var(--text-primary);
      padding:13px 18px;
      font-size:13px;
      font-weight:600;
      gap:10px;
    }
    .user-row {
      display:grid;
      grid-template-columns:80px 2fr 2fr 1fr 1fr 1fr;
      padding:14px 18px;
      border-bottom:1px solid rgba(13, 110, 253, 0.15);
      align-items:center;
      gap:10px;
      transition:background .2s;
      position: relative;
    }
    .user-row:hover {
      background: rgba(13, 110, 253, 0.05);
      border-left: 3px solid var(--cyber-accent);
      padding-left: 15px;
    }
    .user-row:last-child { border-bottom:none; }

    .col-username { font-weight:600; font-size:14px; color:var(--text-primary); }
    .col-email { color:var(--text-secondary); font-size:13px; }
    .col-date { text-align:center; color:var(--text-secondary); font-size:13px; }
    .col-actions { display:flex; gap:6px; justify-content:flex-end; }

    .users-table th,
    .users-table td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid var(--cyber-border);
    }

    .users-table th {
      background: rgba(13, 110, 253, 0.1);
      font-weight: 600;
      color: var(--neon-cyan);
    }

    .users-table tr:hover {
      background: rgba(13, 110, 253, 0.05);
    }

    .user-avatar-cell {
      width: 50px;
    }

    .user-avatar-small {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 2px solid var(--cyber-accent);
      object-fit: cover;
      transition: all 0.3s ease;
    }
    .user-avatar-small:hover {
      transform: scale(1.1);
    }

    .role-badge {
      display:inline-block;
      background: rgba(13, 110, 253, 0.15);
      color: var(--cyber-primary);
      border: 1px solid rgba(13, 110, 253, 0.3);
      padding:3px 10px;
      border-radius:20px;
      font-size:11px;
      font-weight:600;
      backdrop-filter: blur(5px);
    }

    .role-admin {
      background: rgba(255, 71, 87, 0.15);
      color: var(--cyber-danger);
      border: 1px solid rgba(255, 71, 87, 0.3);
    }

    .role-user {
      background: rgba(32, 201, 151, 0.15);
      color: var(--cyber-success);
      border: 1px solid rgba(32, 201, 151, 0.3);
    }

    .role-admin {
      background: var(--cyber-danger);
      color: white;
    }

    .role-user {
      background: var(--cyber-primary);
      color: white;
    }

    .action-buttons {
      display: flex;
      gap: 0.5rem;
    }

    .btn-sm {
      padding: 0.5rem 1rem;
      font-size: 0.9rem;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      z-index: 1000;
      animation: fadeIn 0.3s ease;
    }

    .modal.show {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background: rgba(255, 255, 255, 0.98);
      border: 2px solid rgba(0, 0, 0, 0.1);
      border-radius: 20px;
      max-width: 600px;
      width: 95%;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
      backdrop-filter: blur(20px);
      animation: modalFadeIn 0.3s ease-out;
      padding: 32px;
    }

    .modal-header {
      padding: 0 0 24px 0;
      border-bottom: 2px solid rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }

    .modal-title {
      margin: 0;
      color: var(--text-primary);
      font-size: 24px;
      font-weight: 700;
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 2rem;
      color: var(--text-secondary);
      cursor: pointer;
      transition: color 0.3s ease;
    }

    .modal-close:hover {
      color: var(--cyber-danger);
    }

    .modal-body {
      padding: 0;
    }

    .modal-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--neon-cyan);
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      color: var(--text-secondary);
      cursor: pointer;
      transition: color 0.3s ease;
    }

    .modal-close:hover {
      color: var(--cyber-danger);
    }

    .modal-body {
      padding: 1.5rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: var(--text-primary);
    }

    .form-group {
      margin-bottom: 24px;
      position: relative;
    }
    .form-group label {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--text-primary);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-size: 14px;
    }
    .form-group label::before {
      content: '';
      width: 16px;
      height: 16px;
      background: var(--cyber-accent);
      mask: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>') no-repeat center;
      mask-size: contain;
      opacity: 0.7;
    }
    .form-input {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid rgba(0, 0, 0, 0.1);
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.95);
      color: var(--text-primary);
      font-size: 14px;
      transition: all 0.3s ease;
      backdrop-filter: blur(5px);
    }

    .form-input:focus {
      outline: none;
      border-color: var(--cyber-accent);
      box-shadow: 0 0 0 4px rgba(0, 212, 255, 0.2);
      background: #fff;
      transform: translateY(-2px);
    }

    .form-input:focus {
      outline: none;
      border-color: var(--cyber-primary);
      box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2);
    }

    .form-select {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid var(--cyber-border);
      border-radius: var(--border-radius);
      background: var(--cyber-dark);
      color: var(--text-primary);
      font-size: 1rem;
      transition: border-color 0.3s ease;
    }

    .form-select:focus {
      outline: none;
      border-color: var(--cyber-primary);
      box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2);
    }

    .modal-footer {
      display: flex;
      gap: 16px;
      justify-content: flex-end;
      margin-top: 32px;
      padding-top: 24px;
      border-top: 2px solid rgba(0, 0, 0, 0.1);
    }

    /* Animations */
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes slideIn {
      from { transform: translateY(-50px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    /* Responsive */
    @media (max-width: 768px) {
      .main-content {
        padding: 1rem;
      }

      .users-header,
      .user-row {
        grid-template-columns: 60px 1fr;
        gap: 8px;
      }

      .users-header span:nth-child(n+2) { display: none; }
      .user-row .col-username,
      .user-row .col-email,
      .user-row .col-date { display: none; }

      .user-row::before {
        content: "<?php echo htmlspecialchars($user['username']); ?> - <?php echo htmlspecialchars($user['email']); ?>";
        font-weight: 600;
        grid-column: 2;
      }

      .col-actions {
        grid-column: 1 / -1;
        justify-content: center;
        margin-top: 8px;
      }
    }

      .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
      }

      .search-container {
        flex-direction: column;
        width: 100%;
      }

      .search-container .form-input {
        width: 100%;
      }

      .users-table {
        font-size: 0.9rem;
      }

      .users-table th,
      .users-table td {
        padding: 0.75rem 0.5rem;
      }

      .action-buttons {
        flex-direction: column;
        gap: 0.25rem;
      }
    }
  </style>
</head>
<body>
  <div class="floating-shapes">
    <div class="shape"></div>
    <div class="shape"></div>
    <div class="shape"></div>
  </div>
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <h2>🔐 Secura</h2>
    </div>
    <ul class="sidebar-menu">
      <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
      <li><a href="all_modules.php"><i class="fas fa-layer-group"></i> Modules</a></li>
      <li><a href="#"><i class="fas fa-cog"></i> Paramètres</a></li>
      <li><a href="admin_users.php" class="active"><i class="fas fa-users"></i> Utilisateurs</a></li>
    </ul>
  </aside>

<!-- Main Content -->
  <div class="main-content">
    <nav class="navbar2">
      <h1>👥 Gestion des Utilisateurs</h1>
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
<br><br>
<br><br>
<div class="container">
  <!-- Module management -->
  <div class="dash-section">

    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
        <input type="text" id="userSearch" placeholder="Rechercher un utilisateur..." class="form-input" style="padding: 10px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px;">
        <button class="btn btn-primary" onclick="openCreateModal()">
          ➕ Ajouter un Utilisateur
        </button>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert alert-<?php echo $message_type; ?>">
        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <div class="dash-section">
      <div class="users-container">
        <div class="users-header">
          <span>Avatar</span>
          <span>Nom d'utilisateur</span>
          <span>Email</span>
          <span>Rôle</span>
          <span>Date d'inscription</span>
          <span style="text-align:right">Actions</span>
        </div>
        <?php foreach ($users as $user): ?>
          <div class="user-row">
            <div class="user-avatar-cell">
              <img src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'assets/images/default-avatar.svg'; ?>"
                   alt="Avatar" class="user-avatar-small">
            </div>
            <span class="col-username"><?php echo htmlspecialchars($user['username']); ?></span>
            <span class="col-email"><?php echo htmlspecialchars($user['email']); ?></span>
            <span>
              <span class="role-badge role-<?php echo $user['role']; ?>">
                <?php echo $user['role'] === 'admin' ? 'Admin' : 'Utilisateur'; ?>
              </span>
            </span>
            <span class="col-date"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></span>
            <span class="col-actions">
              <button class="btn btn-success btn-sm" onclick="openEditModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>', '<?php echo addslashes($user['email']); ?>', '<?php echo $user['role']; ?>')">
                ✏️ Modifier
              </button>
              <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>')">
                  🗑️ Supprimer
                </button>
              <?php endif; ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    </div>

</div> <!-- End main-content -->

  <!-- Create User Modal -->
  <div class="modal" id="createModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Ajouter un Utilisateur</h2>
        <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="create_user">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label" for="create_username">Nom d'utilisateur</label>
            <input type="text" id="create_username" name="username" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="create_email">Email</label>
            <input type="email" id="create_email" name="email" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="create_password">Mot de passe</label>
            <input type="password" id="create_password" name="password" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="create_role">Rôle</label>
            <select id="create_role" name="role" class="form-select">
              <option value="user">Utilisateur</option>
              <option value="admin">Administrateur</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="closeModal('deleteModal')">Annuler</button>
          <button type="submit" class="btn-submit" style="background: var(--cyber-danger);">Supprimer</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit User Modal -->
  <div class="modal" id="editModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Modifier l'Utilisateur</h2>
        <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="update_user">
        <input type="hidden" name="user_id" id="edit_user_id">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label" for="edit_username">Nom d'utilisateur</label>
            <input type="text" id="edit_username" name="username" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit_email">Email</label>
            <input type="email" id="edit_email" name="email" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit_role">Rôle</label>
            <select id="edit_role" name="role" class="form-select">
              <option value="user">Utilisateur</option>
              <option value="admin">Administrateur</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Annuler</button>
          <button type="submit" class="btn-submit">Mettre à jour</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal" id="deleteModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Confirmer la suppression</h2>
        <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="delete_user">
        <input type="hidden" name="user_id" id="delete_user_id">
        <div class="modal-body">
          <p>Êtes-vous sûr de vouloir supprimer l'utilisateur <strong id="delete_username"></strong> ?</p>
          <p class="text-danger">Cette action est irréversible.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="closeModal('createModal')">Annuler</button>
          <button type="submit" class="btn-submit">Créer</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // User dropdown functionality
    document.addEventListener('DOMContentLoaded', function() {
      const userAvatar = document.getElementById('userAvatar');
      const userDropdown = document.getElementById('userDropdown');

      if (userAvatar && userDropdown) {
        userAvatar.addEventListener('click', function(e) {
          e.stopPropagation();
          userDropdown.classList.toggle('open');
        });

        document.addEventListener('click', function() {
          userDropdown.classList.remove('open');
        });
      }

      // Auto-hide alert after 5 seconds
      const alert = document.querySelector('.alert');
      if (alert) {
        setTimeout(() => {
          alert.style.opacity = '0';
          setTimeout(() => alert.remove(), 500);
        }, 5000);
      }
    });

    // Modal functions
    function openCreateModal() {
      document.getElementById('createModal').classList.add('show');
    }

    function openEditModal(userId, username, email, role) {
      document.getElementById('edit_user_id').value = userId;
      document.getElementById('edit_username').value = username;
      document.getElementById('edit_email').value = email;
      document.getElementById('edit_role').value = role;
      document.getElementById('editModal').classList.add('show');
    }

    function confirmDelete(userId, username) {
      document.getElementById('delete_user_id').value = userId;
      document.getElementById('delete_username').textContent = username;
      document.getElementById('deleteModal').classList.add('show');
    }

    function closeModal(modalId) {
      document.getElementById(modalId).classList.remove('show');
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
      }
    });

    // Update user count
    function updateUserCount() {
      const visibleRows = document.querySelectorAll('.users-table tbody tr:not([style*="display: none"])');
      document.getElementById('userCount').textContent = `Utilisateurs (${visibleRows.length})`;
    }

    // Search functionality
    document.getElementById('userSearch').addEventListener('input', function() {
      const filter = this.value.toLowerCase();
      const rows = document.querySelectorAll('.users-table tbody tr');
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
      });
      updateUserCount();
    });

    // Initial count
    updateUserCount();
  </script>
</body>
</html>