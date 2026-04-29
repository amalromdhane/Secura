<?php
// Start session
session_start();

// Include database configuration
require_once 'api/config.php';

// Initialize variables
$errors = [];
$success = false;
$form_data = [
    'firstName' => '',
    'lastName' => '',
    'email' => '',
    'phone' => '',
    'newsletter' => false
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $form_data = [
        'firstName' => trim($_POST['firstName'] ?? ''),
        'lastName' => trim($_POST['lastName'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirmPassword' => $_POST['confirmPassword'] ?? '',
        'newsletter' => isset($_POST['newsletter']),
        'terms' => isset($_POST['terms'])
    ];

    // Validation
    if (empty($form_data['firstName'])) {
        $errors[] = 'Le prénom est requis';
    }
    if (empty($form_data['lastName'])) {
        $errors[] = 'Le nom est requis';
    }
    if (empty($form_data['email'])) {
        $errors[] = 'L\'email est requis';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'email n\'est pas valide';
    }
    if (empty($form_data['password'])) {
        $errors[] = 'Le mot de passe est requis';
    } elseif (strlen($form_data['password']) < 8) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
    } elseif (!preg_match('/[A-Z]/', $form_data['password'])) {
        $errors[] = 'Le mot de passe doit contenir au moins une majuscule';
    } elseif (!preg_match('/[a-z]/', $form_data['password'])) {
        $errors[] = 'Le mot de passe doit contenir au moins une minuscule';
    } elseif (!preg_match('/[0-9]/', $form_data['password'])) {
        $errors[] = 'Le mot de passe doit contenir au moins un chiffre';
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $form_data['password'])) {
        $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial';
    }
    if ($form_data['password'] !== $form_data['confirmPassword']) {
        $errors[] = 'Les mots de passe ne correspondent pas';
    }
    if (!empty($form_data['phone']) && !preg_match('/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/', $form_data['phone'])) {
        $errors[] = 'Le numéro de téléphone n\'est pas valide';
    }
    if (!$form_data['terms']) {
        $errors[] = 'Vous devez accepter les conditions d\'utilisation';
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $form_data['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = 'Cet email est déjà utilisé';
        } else {
            // Hash password
            $hashed_password = password_hash($form_data['password'], PASSWORD_DEFAULT);
            
            // Create username from email
            $username = explode('@', $form_data['email'])[0];
            $username = preg_replace('/[^a-zA-Z0-9]/', '', $username);
            
            // Check if username exists, if so, add number
            $original_username = $username;
            $counter = 1;
            while (true) {
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows == 0) {
                    break;
                }
                $username = $original_username . $counter;
                $counter++;
            }
            
            // Insert user into database
            $stmt = $conn->prepare("INSERT INTO users (username, first_name, last_name, email, password, phone, newsletter) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $newsletter_val = $form_data['newsletter'] ? 1 : 0;
            $stmt->bind_param("ssssssi", $username, $form_data['firstName'], $form_data['lastName'], $form_data['email'], $hashed_password, $form_data['phone'], $newsletter_val);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $form_data['email'];
                $_SESSION['first_name'] = $form_data['firstName'];
                $_SESSION['last_name'] = $form_data['lastName'];
                $_SESSION['logged_in'] = true;
                
                $success = true;
                
                // Redirect to home page after successful registration
                header('Location: index.html');
                exit;
            } else {
                $errors[] = 'Erreur lors de l\'inscription: ' . $stmt->error;
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
  <title>Inscription - Secura</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="./css/style.css">
  <style>
    .signin-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #0a0e17 0%, #121826 100%);
      position: relative;
      overflow: hidden;
      padding: 2rem 1rem;
    }

    .signin-bg {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('https://images.unsplash.com/photo-1563013544-824ae1b704d3?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80') center/cover;
      opacity: 0.1;
      z-index: 1;
    }

    .signin-overlay {
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

    .signin-card {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 2.5rem;
      width: 100%;
      max-width: 500px;
      z-index: 10;
      position: relative;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }

    .signin-header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .signin-logo {
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

    .signin-logo i {
      font-size: 2.5rem;
      color: white;
    }

    .signin-title {
      font-size: 2rem;
      font-weight: 700;
      color: white;
      margin-bottom: 0.5rem;
    }

    .signin-subtitle {
      color: rgba(255, 255, 255, 0.7);
      font-size: 1rem;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group.full-width {
      grid-column: 1 / -1;
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

    .password-strength {
      margin-top: 0.5rem;
      height: 4px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 2px;
      overflow: hidden;
    }

    .password-strength-bar {
      height: 100%;
      width: 0;
      transition: all 0.3s ease;
      border-radius: 2px;
    }

    .password-strength-bar.weak {
      width: 33%;
      background: #dc3545;
    }

    .password-strength-bar.medium {
      width: 66%;
      background: #ffc107;
    }

    .password-strength-bar.strong {
      width: 100%;
      background: #28a745;
    }

    .password-requirements {
      margin-top: 0.5rem;
      font-size: 0.8rem;
      color: rgba(255, 255, 255, 0.6);
    }

    .password-requirements ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .password-requirements li {
      padding: 0.2rem 0;
      transition: color 0.3s ease;
    }

    .password-requirements li.valid {
      color: #28a745;
    }

    .password-requirements li::before {
      content: '○';
      margin-right: 0.5rem;
    }

    .password-requirements li.valid::before {
      content: '✓';
      color: #28a745;
    }

    .form-check {
      display: flex;
      align-items: flex-start;
      margin-bottom: 1.5rem;
    }

    .form-check-input {
      width: 1.2rem;
      height: 1.2rem;
      margin-right: 0.5rem;
      margin-top: 0.2rem;
      accent-color: #0d6efd;
    }

    .form-check-label {
      color: rgba(255, 255, 255, 0.8);
      font-size: 0.9rem;
      line-height: 1.4;
    }

    .form-check-label a {
      color: #0d6efd;
      text-decoration: none;
    }

    .form-check-label a:hover {
      color: #00d4ff;
      text-decoration: underline;
    }

    .signin-btn {
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

    .signin-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 40px rgba(13, 110, 253, 0.4);
    }

    .signin-btn:active {
      transform: translateY(0);
    }

    .signin-btn:disabled {
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

    .social-signin {
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

    .login-link {
      text-align: center;
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.9rem;
    }

    .login-link a {
      color: #0d6efd;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s ease;
    }

    .login-link a:hover {
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
      margin: 0.5rem 0 0 0;
      padding-left: 1.5rem;
    }

    .alert-danger li {
      margin-bottom: 0.25rem;
    }

    @media (max-width: 768px) {
      .signin-card {
        margin: 1rem;
        padding: 2rem;
      }
      
      .form-row {
        grid-template-columns: 1fr;
      }
      
      .signin-title {
        font-size: 1.5rem;
      }
      
      .social-signin {
        flex-direction: column;
      }
    }
  </style>
</head>

<body>
  <div class="signin-container">
    <div class="signin-bg"></div>
    <div class="signin-overlay"></div>
    <div class="floating-shapes">
      <div class="shape"></div>
      <div class="shape"></div>
      <div class="shape"></div>
    </div>

    <a href="index.html" class="back-to-home">
      <i class="bi bi-arrow-left"></i>
      Retour à l'accueil
    </a>

    <div class="signin-card">
      <div class="signin-header">
        <div class="signin-logo">
          <i class="bi bi-person-plus"></i>
        </div>
        <h1 class="signin-title">Inscription</h1>
        <p class="signin-subtitle">Créez votre compte pour accéder aux modules de formation</p>
      </div>

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
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="firstName">Prénom <span class="required-star">*</span></label>
            <div class="input-group">
              <span class="input-group-text">
                <i class="bi bi-person"></i>
              </span>
              <input type="text" class="form-control" id="firstName" name="firstName" placeholder="Jean" value="<?php echo htmlspecialchars($form_data['firstName']); ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="lastName">Nom <span class="required-star">*</span></label>
            <div class="input-group">
              <span class="input-group-text">
                <i class="bi bi-person"></i>
              </span>
              <input type="text" class="form-control" id="lastName" name="lastName" placeholder="Dupont" value="<?php echo htmlspecialchars($form_data['lastName']); ?>" required>
            </div>
          </div>
        </div>

        <div class="form-group full-width">
          <label class="form-label" for="email">Adresse email <span class="required-star">*</span></label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-envelope"></i>
            </span>
            <input type="email" class="form-control" id="email" name="email" placeholder="jean.dupont@email.com" value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
          </div>
        </div>

        <div class="form-group full-width">
          <label class="form-label" for="phone">Téléphone</label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-telephone"></i>
            </span>
            <input type="tel" class="form-control" id="phone" name="phone" placeholder="+33 6 12 34 56 78" value="<?php echo htmlspecialchars($form_data['phone']); ?>">
          </div>
        </div>

        <div class="form-group full-width">
          <label class="form-label" for="password">Mot de passe <span class="required-star">*</span></label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-lock"></i>
            </span>
            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
            <button type="button" class="password-toggle" onclick="togglePassword('password')">
              <i class="bi bi-eye" id="passwordIcon"></i>
            </button>
          </div>
          <div class="password-strength">
            <div class="password-strength-bar" id="passwordStrength"></div>
          </div>
          <div class="password-requirements">
            <ul>
              <li id="lengthCheck">Au moins 8 caractères</li>
              <li id="uppercaseCheck">Une majuscule</li>
              <li id="lowercaseCheck">Une minuscule</li>
              <li id="numberCheck">Un chiffre</li>
              <li id="specialCheck">Un caractère spécial</li>
            </ul>
          </div>
        </div>

        <div class="form-group full-width">
          <label class="form-label" for="confirmPassword">Confirmer le mot de passe <span class="required-star">*</span></label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-lock-fill"></i>
            </span>
            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="••••••••" required>
            <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
              <i class="bi bi-eye" id="confirmPasswordIcon"></i>
            </button>
          </div>
        </div>

        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
          <label class="form-check-label" for="terms">
            J'accepte les <a href="#">conditions d'utilisation</a> et la <a href="#">politique de confidentialité</a> <span class="required-star">*</span>
          </label>
        </div>

        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter" <?php echo $form_data['newsletter'] ? 'checked' : ''; ?>>
          <label class="form-check-label" for="newsletter">
            Je souhaite recevoir les actualités et les nouveaux modules par email
          </label>
        </div>

        <button type="submit" class="signin-btn">
          <i class="bi bi-person-plus me-2"></i>
          Créer mon compte
        </button>
      </form>

      <div class="divider">
        <span>ou s'inscrire avec</span>
      </div>

      <div class="social-signin">
        <a href="#" class="social-btn">
          <i class="bi bi-google"></i>
          Google
        </a>
        <a href="#" class="social-btn">
          <i class="bi bi-microsoft"></i>
          Microsoft
        </a>
      </div>

      <div class="login-link">
        Déjà un compte ? <a href="login.php">Se connecter</a>
      </div>
    </div>
  </div>

  <script>
    function togglePassword(fieldId) {
      const passwordInput = document.getElementById(fieldId);
      const passwordIcon = document.getElementById(fieldId + 'Icon');
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.className = 'bi bi-eye-slash';
      } else {
        passwordInput.type = 'password';
        passwordIcon.className = 'bi bi-eye';
      }
    }

    function checkPasswordStrength(password) {
      const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
      };

      // Update requirement indicators
      document.getElementById('lengthCheck').classList.toggle('valid', requirements.length);
      document.getElementById('uppercaseCheck').classList.toggle('valid', requirements.uppercase);
      document.getElementById('lowercaseCheck').classList.toggle('valid', requirements.lowercase);
      document.getElementById('numberCheck').classList.toggle('valid', requirements.number);
      document.getElementById('specialCheck').classList.toggle('valid', requirements.special);

      // Calculate strength
      const validRequirements = Object.values(requirements).filter(Boolean).length;
      const strengthBar = document.getElementById('passwordStrength');
      
      strengthBar.className = 'password-strength-bar';
      if (validRequirements <= 2) {
        strengthBar.classList.add('weak');
      } else if (validRequirements <= 4) {
        strengthBar.classList.add('medium');
      } else {
        strengthBar.classList.add('strong');
      }

      return validRequirements === 5;
    }

    // Real-time password strength checking
    document.getElementById('password').addEventListener('input', function() {
      checkPasswordStrength(this.value);
    });

    // Password confirmation validation
    document.getElementById('confirmPassword').addEventListener('input', function() {
      const password = document.getElementById('password').value;
      if (this.value && this.value !== password) {
        this.style.borderColor = '#dc3545';
      } else {
        this.style.borderColor = '';
      }
    });
  </script>
</body>

</html>
