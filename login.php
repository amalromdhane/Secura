<?php
// Start session
session_start();

// Include database configuration
require_once 'api/config.php';

// Initialize variables
$errors = [];
$success = false;
$form_data = [
    'email' => '',
    'remember' => false
];

// Check if user is already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // User is already logged in, show message instead of redirect
    $alreadyLoggedIn = true;
} else {
    $alreadyLoggedIn = false;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $form_data = [
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'remember' => isset($_POST['remember'])
    ];

    // Validation
    if (empty($form_data['email'])) {
        $errors[] = 'L\'email est requis';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'email n\'est pas valide';
    }
    
    if (empty($form_data['password'])) {
        $errors[] = 'Le mot de passe est requis';
    }

    // If no errors, proceed with login
    if (empty($errors)) {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $form_data['email']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $errors[] = 'Email ou mot de passe incorrect';
        } else {
            $user = $result->fetch_assoc();

            // Verify password
            if (!password_verify($form_data['password'], $user['password'])) {
                $errors[] = 'Email ou mot de passe incorrect';
            } else {
                // Password is correct, start session
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['logged_in'] = true;

                // Set remember me cookie if requested
                if ($form_data['remember']) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (30 * 24 * 60 * 60); // 30 days
                    setcookie('remember_token', $token, $expires, '/', '', false, true);
                }

                $success = true;
                
                // Redirect to home page after successful login
                header('Location: index.php?login=success');
                exit;
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion - Secura</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="./css/style.css">
  <style>
    .login-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #0a0e17 0%, #121826 100%);
      position: relative;
      overflow: hidden;
    }

    .login-bg {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('https://images.unsplash.com/photo-1550745165-9bc0b252726a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80') center/cover;
      opacity: 0.1;
      z-index: 1;
    }

    .login-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(10,14,23,0.95) 0%, rgba(18,24,38,0.9) 100%);
      z-index: 2;
    }

    .floating-shapes {
      position: absolute;
      width: 100%;
      height: 100%;
      z-index: 3;
    }

    .floating-shapes .shape {
      position: absolute;
      border-radius: 50%;
      background: linear-gradient(45deg, #0d6efd, #00d4ff);
      opacity: 0.1;
      animation: float 6s ease-in-out infinite;
    }

    .floating-shapes .shape:nth-child(1) {
      width: 80px;
      height: 80px;
      top: 10%;
      left: 10%;
      animation-delay: 0s;
    }

    .floating-shapes .shape:nth-child(2) {
      width: 120px;
      height: 120px;
      top: 70%;
      right: 10%;
      animation-delay: 2s;
    }

    .floating-shapes .shape:nth-child(3) {
      width: 60px;
      height: 60px;
      bottom: 10%;
      left: 30%;
      animation-delay: 4s;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-20px) rotate(180deg); }
    }

    .login-card {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 3rem;
      width: 100%;
      max-width: 450px;
      z-index: 10;
      position: relative;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }

    .login-header {
      text-align: center;
      margin-bottom: 2.5rem;
    }

    .login-logo {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #0d6efd 0%, #00d4ff 100%);
      border-radius: 20px;
      margin-bottom: 1.5rem;
      box-shadow: 0 10px 30px rgba(13, 110, 253, 0.3);
    }

    .login-logo i {
      font-size: 2.5rem;
      color: white;
    }

    .login-title {
      font-size: 2rem;
      font-weight: 700;
      color: white;
      margin-bottom: 0.5rem;
    }

    .login-subtitle {
      color: rgba(255, 255, 255, 0.7);
      font-size: 1rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-label {
      display: block;
      color: rgba(255, 255, 255, 0.9);
      font-weight: 500;
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
    }

    .required-star {
      color: #dc3545;
    }

    .input-group {
      position: relative;
    }

    .input-group-text {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.6);
      z-index: 5;
    }

    .form-control {
      width: 100%;
      padding: 1rem 1rem 1rem 3rem;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      color: white;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .form-control:focus {
      outline: none;
      border-color: #0d6efd;
      background: rgba(255, 255, 255, 0.08);
      box-shadow: 0 0 20px rgba(13, 110, 253, 0.2);
    }

    .form-control::placeholder {
      color: rgba(255, 255, 255, 0.5);
    }

    .form-control.error {
      border-color: #dc3545;
      box-shadow: 0 0 20px rgba(220, 53, 69, 0.2);
    }

    .password-toggle {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.6);
      cursor: pointer;
      z-index: 5;
      transition: color 0.3s ease;
    }

    .password-toggle:hover {
      color: rgba(255, 255, 255, 0.9);
    }

    .form-check {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.5rem;
    }

    .form-check-input {
      width: 1.2rem;
      height: 1.2rem;
      margin-right: 0.5rem;
      accent-color: #0d6efd;
    }

    .form-check-label {
      color: rgba(255, 255, 255, 0.8);
      font-size: 0.9rem;
    }

    .forgot-password {
      color: #0d6efd;
      text-decoration: none;
      font-size: 0.9rem;
      transition: color 0.3s ease;
    }

    .forgot-password:hover {
      color: #00d4ff;
      text-decoration: underline;
    }

    .login-btn {
      width: 100%;
      padding: 1rem;
      background: linear-gradient(135deg, #0d6efd 0%, #00d4ff 100%);
      border: none;
      border-radius: 12px;
      color: white;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 10px 30px rgba(13, 110, 253, 0.3);
    }

    .login-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 40px rgba(13, 110, 253, 0.4);
    }

    .login-btn:active {
      transform: translateY(0);
    }

    .login-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    .divider {
      text-align: center;
      margin: 2rem 0;
      position: relative;
    }

    .divider::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 0;
      right: 0;
      height: 1px;
      background: rgba(255, 255, 255, 0.1);
    }

    .divider span {
      background: rgba(255, 255, 255, 0.05);
      padding: 0 1rem;
      color: rgba(255, 255, 255, 0.6);
      font-size: 0.9rem;
      position: relative;
    }

    .social-login {
      display: flex;
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .social-btn {
      flex: 1;
      padding: 0.8rem;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      color: rgba(255, 255, 255, 0.8);
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      text-decoration: none;
      font-size: 0.9rem;
    }

    .social-btn:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: rgba(255, 255, 255, 0.2);
      color: white;
      transform: translateY(-2px);
    }

    .register-link {
      text-align: center;
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.9rem;
    }

    .register-link a {
      color: #0d6efd;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s ease;
    }

    .register-link a:hover {
      color: #00d4ff;
      text-decoration: underline;
    }

    .back-to-home {
      position: absolute;
      top: 2rem;
      left: 2rem;
      color: rgba(255, 255, 255, 0.7);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      z-index: 10;
      transition: color 0.3s ease;
    }

    .back-to-home:hover {
      color: white;
    }

    .alert {
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
    }

    .alert-danger {
      background: rgba(220, 53, 69, 0.1);
      border: 1px solid rgba(220, 53, 69, 0.3);
      color: #ff6b6b;
    }

    .alert-danger ul {
      margin: 0.5rem 0 0;
      padding-left: 1.5rem;
    }

    .alert-danger li {
      margin-bottom: 0.25rem;
    }

    .alert-info {
      background: rgba(13, 110, 253, 0.1);
      border: 1px solid rgba(13, 110, 253, 0.3);
      color: white;
    }

    .alert-info i {
      margin-right: 0.5rem;
    }

    @media (max-width: 768px) {
      .login-card {
        margin: 1rem;
        padding: 2rem;
      }
      
      .login-title {
        font-size: 1.5rem;
      }
      
      .social-login {
        flex-direction: column;
      }
    }
  </style>
</head>

<body>
  <div class="login-container">
    <div class="login-bg"></div>
    <div class="login-overlay"></div>
    <div class="floating-shapes">
      <div class="shape"></div>
      <div class="shape"></div>
      <div class="shape"></div>
    </div>

    <a href="index.html" class="back-to-home">
      <i class="bi bi-arrow-left"></i>
      Retour à l'accueil
    </a>

    <div class="login-card">
      <div class="login-header">
        <div class="login-logo">
          <i class="bi bi-shield-lock"></i>
        </div>
        <h1 class="login-title">Connexion</h1>
        <p class="login-subtitle">Accédez à votre espace de formation</p>
      </div>

      <?php if ($alreadyLoggedIn): ?>
        <div class="alert alert-info">
          <i class="bi bi-info-circle me-2"></i>
          <strong>Information:</strong> Vous êtes déjà connecté. <a href="index.html" style="color: #0d6efd;">Retour à l'accueil</a>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <strong>Erreurs:</strong>
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label class="form-label" for="email">Adresse email <span class="required-star">*</span></label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-envelope"></i>
            </span>
            <input type="email" class="form-control" id="email" name="email" placeholder="votre@email.com" value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Mot de passe <span class="required-star">*</span></label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-lock"></i>
            </span>
            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
            <button type="button" class="password-toggle" onclick="togglePassword()">
              <i class="bi bi-eye" id="passwordIcon"></i>
            </button>
          </div>
        </div>

        <div class="form-check">
          <div>
            <input type="checkbox" class="form-check-input" id="remember" name="remember" <?php echo $form_data['remember'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="remember">
              Se souvenir de moi
            </label>
          </div>
          <a href="#" class="forgot-password">Mot de passe oublié ?</a>
        </div>

        <button type="submit" class="login-btn">
          <i class="bi bi-box-arrow-in-right me-2"></i>
          Se connecter
        </button>
      </form>

      <div class="divider">
        <span>ou continuer avec</span>
      </div>

      <div class="social-login">
        <a href="#" class="social-btn">
          <i class="bi bi-google"></i>
          Google
        </a>
        <a href="#" class="social-btn">
          <i class="bi bi-microsoft"></i>
          Microsoft
        </a>
      </div>

      <div class="register-link">
        Pas encore de compte ? <a href="signin.php">S'inscrire</a>
      </div>
    </div>
  </div>

  <script>
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const passwordIcon = document.getElementById('passwordIcon');
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.className = 'bi bi-eye-slash';
      } else {
        passwordInput.type = 'password';
        passwordIcon.className = 'bi bi-eye';
      }
    }
  </script>
</body>

</html>
