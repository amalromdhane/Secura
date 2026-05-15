<?php
/**
 * User Registration System
 * Allows new users to register with role selection (admin/user)
 */

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: admin_dashboard.php');
    exit();
}

// Include database configuration
require_once 'config/database.php';

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = !empty($_POST['username']) ? trim($_POST['username']) : null;
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // New users are always 'user' role (admin is set manually in DB)
    $role = 'user';
    
    // Validate inputs
    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez entrer une adresse email valide.';
    } else {
        try {
            // Connect to MySQL database using config
            $pdo = getDBConnection('cyber');
            
            // Check if email already exists (email is unique for login)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Cette adresse email est déjà utilisée.';
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user (role is always 'user')
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $password_hash, $role]);
                
                $success = 'Compte créé avec succès! <a href="login.php">Se connecter</a>';
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
    <title>Inscription - Secura</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/cyberaware.css">
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
            background:
                radial-gradient(circle at 25% 25%, rgba(100, 149, 237, 0.08) 0%, transparent 35%),
                radial-gradient(circle at 75% 75%, rgba(138, 43, 226, 0.06) 0%, transparent 35%),
                radial-gradient(circle at 50% 10%, rgba(65, 105, 225, 0.04) 0%, transparent 40%),
                linear-gradient(135deg, rgba(10, 14, 23, 0.95), rgba(15, 23, 42, 0.98));
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
            background: linear-gradient(135deg, rgba(100, 149, 237, 0.08), rgba(138, 43, 226, 0.06));
            border: 1px solid rgba(100, 149, 237, 0.15);
            border-radius: 50%;
            animation: float 12s ease-in-out infinite;
            backdrop-filter: blur(8px);
        }

        .shape:nth-child(1) {
            width: 60px;
            height: 60px;
            top: 15%;
            left: 8%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 45px;
            height: 45px;
            top: 65%;
            right: 12%;
            animation-delay: 3s;
        }

        .shape:nth-child(3) {
            width: 75px;
            height: 75px;
            bottom: 25%;
            left: 18%;
            animation-delay: 6s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg) scale(1);
                opacity: 0.4;
            }
            50% {
                transform: translateY(-15px) rotate(2deg) scale(1.05);
                opacity: 0.7;
            }
        }

        .auth-card {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(100, 149, 237, 0.2);
            border-radius: 24px;
            padding: 3.5rem;
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 2;
            backdrop-filter: blur(25px);
            box-shadow:
                0 25px 80px rgba(0, 0, 0, 0.4),
                0 0 60px rgba(100, 149, 237, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg,
                transparent 0%,
                rgba(100, 149, 237, 0.6) 20%,
                rgba(65, 105, 225, 0.8) 50%,
                rgba(100, 149, 237, 0.6) 80%,
                transparent 100%);
            border-radius: 24px 24px 0 0;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .auth-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 90px;
            height: 90px;
            border-radius: 24px;
            background: linear-gradient(135deg,
                rgba(100, 149, 237, 0.15),
                rgba(65, 105, 225, 0.12),
                rgba(138, 43, 226, 0.08));
            border: 2px solid rgba(100, 149, 237, 0.3);
            margin-bottom: 2rem;
            position: relative;
            box-shadow: 0 8px 32px rgba(100, 149, 237, 0.2);
        }

        .auth-logo::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 26px;
            background: linear-gradient(135deg,
                rgba(100, 149, 237, 0.4),
                rgba(65, 105, 225, 0.3),
                rgba(138, 43, 226, 0.2));
            z-index: -1;
            opacity: 0;
            transition: opacity 0.6s ease;
        }

        .auth-card:hover .auth-logo::before {
            opacity: 1;
        }

        .auth-logo i {
            font-size: 2.8rem;
            color: #60a5fa;
            filter: drop-shadow(0 2px 8px rgba(96, 165, 250, 0.3));
        }

        .auth-title {
            font-size: 2rem;
            font-weight: 600;
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.75rem;
            letter-spacing: -0.025em;
        }

        .auth-subtitle {
            color: rgba(148, 163, 184, 0.8);
            font-size: 1rem;
            font-weight: 400;
            letter-spacing: 0.025em;
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
            width: 90%;
            padding: 1.125rem 1.25rem;
            padding-left: 3.25rem;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(71, 85, 105, 0.3);
            border-radius: 16px;
            color: #f1f5f9;
            font-size: 1rem;
            transition: all 0.4s ease;
            backdrop-filter: blur(8px);
        }

        .form-input:focus {
            outline: none;
            border-color: rgba(96, 165, 250, 0.6);
            box-shadow:
                0 0 0 3px rgba(96, 165, 250, 0.15),
                0 8px 32px rgba(96, 165, 250, 0.1);
            background: rgba(15, 23, 42, 0.9);
            transform: translateY(-1px);
        }

        .form-input::placeholder {
            color: rgba(173, 181, 189, 0.5);
        }

        .input-icon {
            position: absolute;
            left: 1.125rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(100, 149, 237, 0.8);
            font-size: 1.125rem;
            transition: color 0.3s ease;
        }

        .form-input-wrapper:focus-within .input-icon {
            color: #60a5fa;
        }

        .btn-auth {
            width: 100%;
            padding: 1.125rem 2rem;
            background: linear-gradient(135deg,
                rgba(100, 149, 237, 0.9),
                rgba(65, 105, 225, 0.9),
                rgba(138, 43, 226, 0.8));
            color: #ffffff;
            border: none;
            border-radius: 16px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            margin-top: 1rem;
            box-shadow:
                0 4px 20px rgba(100, 149, 237, 0.3),
                0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-auth::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg,
                transparent,
                rgba(255, 255, 255, 0.15),
                transparent);
            transition: left 0.6s ease;
        }

        .btn-auth:hover::before {
            left: 100%;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow:
                0 12px 40px rgba(100, 149, 237, 0.4),
                0 4px 16px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg,
                rgba(96, 165, 250, 1),
                rgba(59, 130, 246, 1),
                rgba(139, 92, 246, 0.9));
        }

        .btn-auth:active {
            transform: translateY(0);
            transition: transform 0.1s ease;
        }

        .error-message {
            background: linear-gradient(135deg,
                rgba(239, 68, 68, 0.1),
                rgba(220, 38, 38, 0.08));
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #fca5a5;
            padding: 1.125rem 1.375rem;
            border-radius: 16px;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            gap: 0.875rem;
            backdrop-filter: blur(8px);
            box-shadow: 0 4px 16px rgba(239, 68, 68, 0.1);
        }

        .error-message i {
            font-size: 1.25rem;
            color: #ef4444;
        }

        .success-message {
            background: linear-gradient(135deg,
                rgba(34, 197, 94, 0.1),
                rgba(22, 163, 74, 0.08));
            border: 1px solid rgba(34, 197, 94, 0.25);
            color: #86efac;
            padding: 1.125rem 1.375rem;
            border-radius: 16px;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            gap: 0.875rem;
            backdrop-filter: blur(8px);
            box-shadow: 0 4px 16px rgba(34, 197, 94, 0.1);
        }

        .success-message i {
            font-size: 1.25rem;
            color: #22c55e;
        }

        .success-message a {
            color: #60a5fa;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .success-message a:hover {
            color: #3b82f6;
            text-decoration: underline;
        }

        .auth-footer {
            text-align: center;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(71, 85, 105, 0.2);
            position: relative;
        }

        .auth-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 1px;
            background: linear-gradient(90deg,
                transparent,
                rgba(100, 149, 237, 0.4),
                transparent);
        }

        .auth-footer p {
            color: rgba(148, 163, 184, 0.7);
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .auth-footer a {
            color: #60a5fa;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .auth-footer a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: linear-gradient(90deg, #60a5fa, #3b82f6);
            transition: width 0.3s ease;
        }

        .auth-footer a:hover::after {
            width: 100%;
        }

        .auth-footer a:hover {
            color: #3b82f6;
            text-shadow: 0 0 8px rgba(96, 165, 250, 0.5);
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(71, 85, 105, 0.2);
            border: 1px solid rgba(71, 85, 105, 0.3);
            border-radius: 8px;
            color: rgba(148, 163, 184, 0.7);
            cursor: pointer;
            font-size: 0.875rem;
            padding: 0.375rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
        }

        .password-toggle:hover {
            background: rgba(96, 165, 250, 0.2);
            border-color: rgba(96, 165, 250, 0.4);
            color: #60a5fa;
            transform: translateY(-50%) scale(1.05);
        }

        @media (max-width: 576px) {
            .auth-page {
                padding: 1.5rem 1rem;
            }

            .auth-card {
                padding: 2.5rem 1.75rem;
                max-width: 420px;
                border-radius: 20px;
            }

            .auth-title {
                font-size: 1.75rem;
            }

            .auth-subtitle {
                font-size: 0.95rem;
            }

            .auth-logo {
                width: 80px;
                height: 80px;
            }

            .auth-logo i {
                font-size: 2.5rem;
            }

            .form-input {
                padding: 1rem 1.125rem;
                padding-left: 3rem;
                font-size: 0.95rem;
            }

            .input-icon {
                left: 1rem;
                font-size: 1rem;
            }

            .btn-auth {
                padding: 1rem 1.5rem;
                font-size: 0.95rem;
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
                <h1 class="auth-title">Inscription</h1>
                <p class="auth-subtitle">Créez votre compte sécurisé</p>
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
            <?php else: ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="username">Nom d'utilisateur</label>
                    <div class="form-input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" class="form-input"
                               placeholder="Votre nom d'utilisateur"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                </div>

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
                               placeholder="Min. 8 caractères">
                        <button type="button" class="password-toggle" onclick="togglePassword(this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirmer le Mot de Passe</label>
                    <div class="form-input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required
                               placeholder="Répétez votre mot de passe">
                        <button type="button" class="password-toggle" onclick="togglePassword(this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-auth">S'inscrire</button>
            </form>

            <?php endif; ?>

            <div class="auth-footer">
                <p>Déjà un compte? <a href="login.php">Se connecter</a></p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(btn) {
            const wrapper = btn.parentElement;
            const input = wrapper.querySelector('.form-input');
            const icon = btn.querySelector('i');
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