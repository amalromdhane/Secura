<?php
/**
 * Admin Dashboard
 * Only accessible by users with 'admin' role
 */

session_start();

// Check if logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Check if admin
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: index.html');
    exit();
}

$username = $_SESSION['username'] ?? 'Admin';
$user_email = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Secura</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        
        .navbar {
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar h1 { font-size: 24px; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-info span { opacity: 0.9; }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }
        .logout-btn:hover { background: rgba(255,255,255,0.3); }
        
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-card h3 { color: #666; font-size: 14px; margin-bottom: 10px; }
        .stat-card .number { font-size: 36px; font-weight: bold; color: #1a73e8; }
        
        .dashboard-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .dashboard-section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1a73e8;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .menu-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .menu-item:hover {
            background: white;
            border-color: #1a73e8;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .menu-item .icon { font-size: 32px; margin-bottom: 10px; }
        .menu-item .label { font-weight: 500; }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🔐 Admin Dashboard - Secura</h1>
        <div class="user-info">
            <span>Bienvenue, <?php echo htmlspecialchars($username); ?> (Admin)</span>
            <a href="login.php?action=logout" class="logout-btn">Déconnexion</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="alert alert-success">
            Connexion réussie! Vous êtes connecté en tant qu'administrateur.
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Utilisateurs</h3>
                <div class="number"><?php echo rand(50, 100); ?></div>
            </div>
            <div class="stat-card">
                <h3>Admins</h3>
                <div class="number">1</div>
            </div>
            <div class="stat-card">
                <h3>Utilisateurs Actifs</h3>
                <div class="number"><?php echo rand(30, 80); ?></div>
            </div>
        </div>
        
        <div class="dashboard-section">
            <h2>📋 Menu Administrateur</h2>
            <div class="menu-grid">
                <a href="pages/cloud.html" class="menu-item">
                    <div class="icon">☁️</div>
                    <div class="label">Gestion Cloud</div>
                </a>
                <a href="pages/passwords.html" class="menu-item">
                    <div class="icon">🔑</div>
                    <div class="label">Mots de passe</div>
                </a>
                <a href="pages/phishing.html" class="menu-item">
                    <div class="icon">🎣</div>
                    <div class="label">Phishing</div>
                </a>
                <a href="pages/ransomware.html" class="menu-item">
                    <div class="icon">💀</div>
                    <div class="label">Ransomware</div>
                </a>
                <a href="#" class="menu-item">
                    <div class="icon">👥</div>
                    <div class="label">Utilisateurs</div>
                </a>
                <a href="#" class="menu-item">
                    <div class="icon">⚙️</div>
                    <div class="label">Paramètres</div>
                </a>
            </div>
        </div>
        
        <div class="dashboard-section">
            <h2>📊 Activité Récente</h2>
            <p style="color: #666;">Aucune activité récente à afficher.</p>
        </div>
    </div>
</body>
</html>