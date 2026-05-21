<?php
/**
 * En-tête layout admin partagé (sidebar + navbar + container).
 * Variables attendues : $page_title, $active_menu, optionnel $page_heading, $extra_css[]
 */
$page_title   = $page_title ?? 'Admin';
$page_heading = $page_heading ?? $page_title;
$active_menu  = $active_menu ?? '';
$extra_css    = $extra_css ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?> – Secura</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/admin-layout.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<?php foreach ($extra_css as $css): ?>
  <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
<?php endforeach; ?>
</head>
<body>
  <div class="floating-shapes">
    <div class="shape"></div>
    <div class="shape"></div>
    <div class="shape"></div>
  </div>

  <aside class="sidebar">
    <div class="sidebar-header">
      <h2>🔐 Secura</h2>
    </div>
    <ul class="sidebar-menu">
      <li><a href="admin_dashboard.php"<?php echo admin_menu_active('dashboard', $active_menu); ?>><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
      <li><a href="all_modules.php"<?php echo admin_menu_active('modules', $active_menu); ?>><i class="fas fa-layer-group"></i> Modules</a></li>
      <li><a href="profil.php"<?php echo admin_menu_active('settings', $active_menu); ?>><i class="fas fa-cog"></i> Paramètres</a></li>
      <li><a href="admin_users.php"<?php echo admin_menu_active('users', $active_menu); ?>><i class="fas fa-users"></i> Utilisateurs</a></li>
    </ul>
  </aside>

  <div class="main-content">
    <nav class="navbar">
      <h1><?php echo $page_heading; ?></h1>
      <div class="user-info">
        <img src="<?php echo htmlspecialchars($admin_avatar); ?>" alt="Avatar" class="user-avatar" id="userAvatar">
        <div class="user-dropdown" id="userDropdown">
          <a href="profil.php" class="user-dropdown-item"><i class="fas fa-user"></i> Mon Profil</a>
          <a href="admin_users.php" class="user-dropdown-item"><i class="fas fa-users"></i> Gestion Utilisateurs</a>
          <a href="login.php?action=logout" class="user-dropdown-item"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </div>
      </div>
    </nav>

    <div class="container">
