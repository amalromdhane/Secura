<?php
/**
 * Admin Login System
 * Supports multi-user roles: admin and user
 */

session_start();

// Database configuration
$db_host = '127.0.0.1';
$db_name = 'secura_cyber';
$db_user = 'root';
$db_pass = '';

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Check if already logged in
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: admin_dashboard.php');
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            // Connect to MySQL database
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Fetch user from database using email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful - store user info in session
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: admin_dashboard.php');
                } else {
                    header('Location: index.html');
                }
                exit();
            } else {
                $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $error = 'Erreur de connexion à la base de données.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Secura</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cyberaware.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            overflow: hidden;
            background: var(--cyber-dark);
        }

        .auth-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(13, 110, 253, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(111, 66, 193, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 50% 50%, rgba(0, 212, 255, 0.05) 0%, transparent 30%);
            pointer-events: none;
        }

        .floating-shapes {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 1;
        }

        .shape {
            position: absolute;
            background: rgba(13, 110, 253, 0.1);
            border: 1px solid rgba(13, 110, 253, 0.2);
            border-radius: 12px;
            animation: float 8s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
        }

        .shape:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        .auth-card {
            background: rgba(18, 24, 36, 0.9);
            border: 1px solid rgba(13, 110, 253, 0.3);
            border-radius: 20px;
            padding: 3rem;
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 2;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 40px rgba(13, 110, 253, 0.1);
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--cyber-primary), var(--cyber-accent), transparent);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .auth-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.2), rgba(0, 212, 255, 0.1));
            border: 2px solid rgba(13, 110, 253, 0.4);
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 20px rgba(13, 110, 253, 0.3); }
            50% { box-shadow: 0 0 40px rgba(13, 110, 253, 0.6); }
        }

        .auth-logo i {
            font-size: 2.5rem;
            color: var(--cyber-accent);
        }

        .auth-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .auth-subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1.25rem;
            padding-left: 3rem;
            background: rgba(10, 14, 23, 0.8);
            border: 1px solid rgba(13, 110, 253, 0.3);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--cyber-accent);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.15);
            background: rgba(10, 14, 23, 0.95);
        }

        .form-input::placeholder {
            color: rgba(173, 181, 189, 0.5);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--cyber-primary);
            font-size: 1.1rem;
        }

        .btn-auth {
            width: 100%;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--cyber-primary), var(--cyber-secondary));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .btn-auth::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .btn-auth:hover::before {
            left: 100%;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(13, 110, 253, 0.4);
        }

        .btn-auth:active {
            transform: translateY(0);
        }

        .error-message {
            background: rgba(255, 71, 87, 0.1);
            border: 1px solid rgba(255, 71, 87, 0.3);
            color: #ff6b6b;
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .error-message i {
            font-size: 1.2rem;
        }

        .success-message {
            background: rgba(32, 201, 151, 0.1);
            border: 1px solid rgba(32, 201, 151, 0.3);
            color: #51cf66;
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .success-message i {
            font-size: 1.2rem;
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .auth-footer p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .auth-footer a {
            color: var(--cyber-accent);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .auth-footer a:hover {
            color: #fff;
            text-decoration: underline;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1rem;
            padding: 0.25rem;
        }

        .password-toggle:hover {
            color: var(--cyber-accent);
        }

        @media (max-width: 576px) {
            .auth-card {
                padding: 2rem 1.5rem;
            }

            .auth-title {
                font-size: 1.5rem;
            }

            .auth-logo {
                width: 70px;
                height: 70px;
            }

            .auth-logo i {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-page">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>

        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1 class="auth-title">Connexion</h1>
                <p class="auth-subtitle">Accédez à votre espace sécurisé</p>
            </div>

            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="email">Adresse Email</label>
                    <div class="form-input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-input" required
                               placeholder="votre@email.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Mot de Passe</label>
                    <div class="form-input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-input" required
                               placeholder="••••••••">
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-auth">Se Connecter</button>
            </form>

            <div class="auth-footer">
                <p>Pas encore de compte? <a href="register.php">S'inscrire</a></p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = input.nextElementSibling.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>