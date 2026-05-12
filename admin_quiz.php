<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php'); exit();
}
require_once 'includes/config.php';

$module_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($module_id <= 0) { header('Location: admin_dashboard.php'); exit(); }

try {
    $pdo = getDBConnection('cyber');
    $stmt = $pdo->prepare("SELECT title FROM modules WHERE id = ?");
    $stmt->execute([$module_id]);
    $module = $stmt->fetch();
    if (!$module) { header('Location: admin_dashboard.php'); exit(); }
} catch (PDOException $e) { die($e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion du Quiz - <?php echo htmlspecialchars($module['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
          --admin-primary: #1e293b;
          --admin-secondary: #334155;
          --admin-accent: #3b82f6;
          --admin-success: #10b981;
          --admin-warning: #f59e0b;
          --admin-danger: #ef4444;
          --admin-dark: #f8fafc;
          --admin-card: #ffffff;
          --admin-light: #f1f5f9;
          --admin-border: rgba(71, 85, 105, 0.2);
          --admin-gradient: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
          --admin-glow: 0 0 20px rgba(59, 130, 246, 0.4);
          --text-primary: #1e293b;
          --text-secondary: #64748b;
          --text-muted: #94a3b8;
          --text-light: #f8fafc;
          --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
          --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
          --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body {
          font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
          background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
          color: var(--text-primary);
          min-height: 100vh;
          margin: 0;
          position: relative;
          overflow-x: hidden;
        }

        body::before {
          content: '';
          position: fixed;
          inset: 0;
          background:
            radial-gradient(circle at 20% 80%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
            radial-gradient(circle at 80% 20%, rgba(99, 102, 241, 0.04) 0%, transparent 50%),
            radial-gradient(circle at 40% 40%, rgba(16, 185, 129, 0.03) 0%, transparent 50%);
          pointer-events: none;
          z-index: 0;
        }

        /* Navbar */
        .navbar {
          background: rgba(255, 255, 255, 0.95);
          backdrop-filter: blur(20px);
          box-shadow: var(--shadow-sm);
          border: 1px solid rgba(255, 255, 255, 0.2);
          color: var(--text-primary);
          padding: 1rem 2rem;
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 2rem;
          border-radius: 16px;
          position: relative;
          z-index: 10;
        }

        .navbar::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          height: 1px;
          background: linear-gradient(90deg,
            transparent 0%,
            rgba(59, 130, 246, 0.3) 50%,
            transparent 100%);
        }
        .navbar h1 {
          font-size: 1.5rem;
          font-weight: 600;
          margin: 0;
          background: linear-gradient(135deg, var(--admin-accent), var(--admin-secondary));
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
          letter-spacing: -0.025em;
        }
        .user-info { display:flex; align-items:center; gap:14px; position: relative; }
        .user-avatar {
          width: 44px;
          height: 44px;
          border-radius: 12px;
          cursor: pointer;
          border: 2px solid var(--admin-accent);
          transition: all 0.3s ease;
          object-fit: cover;
          box-shadow: var(--shadow-sm);
        }
        .user-avatar:hover {
          border-color: var(--admin-primary);
          transform: translateY(-2px);
          box-shadow: var(--shadow-md);
        }
        .user-dropdown {
          position: absolute;
          top: 100%;
          right: 0;
          background: rgba(255, 255, 255, 0.98);
          border: 1px solid rgba(0, 0, 0, 0.08);
          border-radius: 16px;
          min-width: 200px;
          box-shadow: var(--shadow-lg);
          backdrop-filter: blur(20px);
          opacity: 0;
          visibility: hidden;
          transform: translateY(-8px) scale(0.95);
          transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
          z-index: 9999;
          overflow: hidden;
        }
        .user-dropdown.open {
          opacity: 1;
          visibility: visible;
          transform: translateY(0) scale(1);
        }
        .user-dropdown-item {
          display: flex;
          align-items: center;
          padding: 0.875rem 1.25rem;
          color: var(--text-secondary);
          text-decoration: none;
          border-bottom: 1px solid rgba(0, 0, 0, 0.06);
          transition: all 0.2s ease;
          font-weight: 500;
        }
        .user-dropdown-item:hover {
          background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(99, 102, 241, 0.06));
          color: var(--text-primary);
          transform: translateX(4px);
        }
        .user-dropdown-item:last-child { border-bottom: none; }
        .user-dropdown-item i {
          margin-right: 0.75rem;
          width: 18px;
          text-align: center;
          opacity: 0.8;
        }

        /* Sidebar */
        .sidebar {
          width: 280px;
          background: var(--admin-card);
          background-image: linear-gradient(180deg, rgba(241, 245, 249, 0.8) 0%, rgba(255, 255, 255, 0.9) 100%);
          border-right: 1px solid rgba(226, 232, 240, 0.8);
          height: 100vh;
          position: fixed;
          left: 0;
          top: 0;
          padding: 2rem 1.5rem;
          box-shadow: var(--shadow-lg);
          z-index: 100;
          backdrop-filter: blur(10px);
        }
        .sidebar-header {
          text-align: center;
          margin-bottom: 2.5rem;
          padding-bottom: 1.5rem;
          border-bottom: 2px solid var(--admin-border);
          position: relative;
        }
        .sidebar-header::after {
          content: '';
          position: absolute;
          bottom: 0;
          left: 50%;
          transform: translateX(-50%);
          width: 40px;
          height: 2px;
          background: linear-gradient(90deg, var(--admin-accent), var(--admin-secondary));
          border-radius: 1px;
        }
        .sidebar-header h2 {
          color: var(--text-primary);
          font-size: 1.25rem;
          font-weight: 700;
          margin: 0;
          letter-spacing: -0.025em;
        }
        .sidebar-menu {
          list-style: none;
          padding: 0;
          margin: 0;
        }
        .sidebar-menu li {
          margin-bottom: 0.5rem;
        }
        .sidebar-menu a {
          display: flex;
          align-items: center;
          padding: 0.875rem 1rem;
          color: var(--text-secondary);
          text-decoration: none;
          border-radius: 12px;
          transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
          font-weight: 500;
          position: relative;
          overflow: hidden;
        }
        .sidebar-menu a::before {
          content: '';
          position: absolute;
          left: 0;
          top: 0;
          width: 0;
          height: 100%;
          background: linear-gradient(135deg, var(--admin-accent), var(--admin-secondary));
          transition: width 0.3s ease;
          z-index: -1;
        }
        .sidebar-menu a:hover::before,
        .sidebar-menu a.active::before {
          width: 100%;
        }
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
          color: var(--text-light);
          transform: translateX(4px);
          box-shadow: var(--shadow-md);
        }
        .sidebar-menu a i {
          margin-right: 0.75rem;
          width: 20px;
          text-align: center;
          opacity: 0.8;
        }

        /* Main content */
        .main-content {
          margin-left: 250px;
          flex: 1;
          padding: 20px;
          min-height: 100vh;
        }
        .container { max-width:1200px; margin:0 auto; padding:0; }
        .container { max-width:1200px; margin:0 auto; padding:0; }
        /* Dashboard section */
        .dash-section {
          background: rgba(255, 255, 255, 0.95);
          border: 1px solid rgba(226, 232, 240, 0.8);
          border-radius: 20px;
          backdrop-filter: blur(20px);
          padding: 2rem;
          margin-bottom: 2rem;
          transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
          position: relative;
          box-shadow: var(--shadow-sm);
          z-index: 1;
        }
        .dash-section::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          height: 3px;
          background: linear-gradient(90deg,
            transparent 0%,
            var(--admin-accent) 20%,
            var(--admin-secondary) 50%,
            var(--admin-accent) 80%,
            transparent 100%);
          border-radius: 20px 20px 0 0;
        }
        .dash-section:hover {
          border-color: var(--admin-border);
          box-shadow: var(--shadow-lg);
          transform: translateY(-2px);
        }
        .dash-section h2 {
          font-size: 1.5rem;
          font-weight: 600;
          color: var(--text-primary);
          margin-bottom: 1.5rem;
          padding-bottom: 1rem;
          border-bottom: 2px solid var(--admin-accent);
          letter-spacing: -0.025em;
          display: flex;
          align-items: center;
          gap: 0.5rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            border: none;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .btn-primary {
            background: var(--admin-gradient);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
        }
        .btn-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--admin-danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            transform: translateY(-1px);
        }
        .btn:hover {
            transform: translateY(-1px);
        }
        .question-item {
            border-bottom: 1px solid var(--admin-border);
            padding: 1.5rem 0;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            position: relative;
        }
        .question-item:hover {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.04), rgba(99, 102, 241, 0.02));
            transform: translateX(4px);
        }
        .question-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .option-item {
            margin-left: 1.5rem;
            font-size: 0.9em;
            color: var(--text-secondary);
            padding: 0.25rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .correct {
            color: var(--admin-success);
            font-weight: 600;
            position: relative;
        }
        .correct::before {
            content: '✓';
            color: var(--admin-success);
            font-weight: bold;
            margin-right: 0.25rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
            letter-spacing: 0.025em;
            text-transform: none;
        }
        label::before {
            content: '';
            width: 6px;
            height: 6px;
            background: var(--admin-accent);
            border-radius: 50%;
            flex-shrink: 0;
        }
        input {
          width: 100%;
          padding: 0.875rem 1rem;
          background: rgba(255, 255, 255, 0.8);
          border: 2px solid var(--admin-border);
          border-radius: 12px;
          color: var(--text-primary);
          font-size: 0.875rem;
          transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
          backdrop-filter: blur(8px);
          font-family: inherit;
        }
        input:focus {
          outline: none;
          border-color: var(--admin-accent);
          box-shadow:
            0 0 0 3px rgba(59, 130, 246, 0.1),
            var(--shadow-md);
          background: #ffffff;
          transform: translateY(-1px);
        }
        input::placeholder {
          color: var(--text-muted);
        }
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            overflow-y: auto;
            backdrop-filter: blur(12px);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }
        .modal.open { display: flex; }
        .modal-content {
          background: rgba(255, 255, 255, 0.98);
          border: 1px solid rgba(226, 232, 240, 0.8);
          border-radius: 24px;
          max-width: 700px;
          width: 95%;
          max-height: 90vh;
          overflow-y: auto;
          box-shadow:
            0 25px 80px rgba(0, 0, 0, 0.15),
            0 0 60px rgba(59, 130, 246, 0.08);
          backdrop-filter: blur(25px);
          padding: 2.5rem;
          position: relative;
          animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .modal-content::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          height: 3px;
          background: linear-gradient(90deg,
            transparent 0%,
            var(--admin-accent) 20%,
            var(--admin-secondary) 50%,
            var(--admin-accent) 80%,
            transparent 100%);
          border-radius: 24px 24px 0 0;
        }
        .option-input-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background: rgba(241, 245, 249, 0.6);
            border: 2px solid var(--admin-border);
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .option-input-row:hover {
            border-color: var(--admin-accent);
            box-shadow: var(--shadow-sm);
            background: rgba(59, 130, 246, 0.04);
            transform: translateX(2px);
        }
        .option-input-row input[type="text"] {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            background: #fff;
            transition: all 0.3s ease;
        }
        .option-input-row input[type="text"]:focus {
            outline: none;
            border-color: var(--cyber-accent);
            box-shadow: 0 0 0 2px rgba(0, 212, 255, 0.15);
        }
        .option-input-row input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: var(--cyber-primary);
            cursor: pointer;
        }
        .option-input-row button {
            padding: 6px 10px;
            background: rgba(255, 71, 87, 0.1);
            color: var(--cyber-danger);
            border: 1px solid rgba(255, 71, 87, 0.3);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        .option-input-row button:hover {
            background: rgba(255, 71, 87, 0.2);
        }
        /* Animations */
        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }

        @keyframes slideIn {
          from {
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
          }
          to {
            opacity: 1;
            transform: translateY(0) scale(1);
          }
        }

        /* Floating shapes */
        .floating-shapes {
          position: fixed;
          inset: 0;
          pointer-events: none;
          z-index: 0;
          overflow: hidden;
        }
        .shape {
          position: absolute;
          background: linear-gradient(135deg, rgba(59, 130, 246, 0.06), rgba(99, 102, 241, 0.04));
          border: 1px solid rgba(59, 130, 246, 0.12);
          border-radius: 50%;
          animation: float 15s ease-in-out infinite;
          backdrop-filter: blur(4px);
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
          animation-delay: 5s;
        }
        .shape:nth-child(3) {
          width: 75px;
          height: 75px;
          bottom: 25%;
          left: 18%;
          animation-delay: 10s;
        }
        @keyframes float {
          0%, 100% {
            transform: translateY(0) rotate(0deg) scale(1);
            opacity: 0.3;
          }
          50% {
            transform: translateY(-25px) rotate(180deg) scale(1.1);
            opacity: 0.6;
          }
        }
        .modal-footer {
          display: flex;
          gap: 1rem;
          justify-content: flex-end;
          margin-top: 2rem;
          padding-top: 1.5rem;
          border-top: 1px solid var(--admin-border);
        }
        .btn-cancel {
          padding: 0.75rem 1.5rem;
          background: rgba(148, 163, 184, 0.1);
          color: var(--text-secondary);
          border: 1px solid var(--admin-border);
          border-radius: 12px;
          cursor: pointer;
          font-size: 0.875rem;
          font-weight: 600;
          transition: all 0.3s ease;
        }
        .btn-cancel:hover {
          background: rgba(148, 163, 184, 0.15);
          border-color: var(--admin-secondary);
          transform: translateY(-1px);
        }
        .btn-submit {
          padding: 0.75rem 1.5rem;
          background: var(--admin-gradient);
          color: white;
          border: none;
          border-radius: 12px;
          cursor: pointer;
          font-size: 0.875rem;
          font-weight: 600;
          transition: all 0.3s ease;
          position: relative;
          overflow: hidden;
        }
        .btn-submit::before {
          content: '';
          position: absolute;
          top: 0;
          left: -100%;
          width: 100%;
          height: 100%;
          background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
          transition: left 0.5s ease;
        }
        .btn-submit:hover::before {
          left: 100%;
        }
        .btn-submit:hover {
          transform: translateY(-1px);
          box-shadow: var(--shadow-md);
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
      <li><a href="admin_users.php"><i class="fas fa-users"></i> Utilisateurs</a></li>
    </ul>
  </aside>

<!-- Main Content -->
  <div class="main-content">
    <nav class="navbar">
      <h1>Quiz : <?php echo htmlspecialchars($module['title']); ?></h1>
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

<div class="container">

  <!-- Module management -->
  <div class="dash-section">
    <h2>❓ Gestion du Quiz</h2>

    <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
        <a href="admin_dashboard.php" class="btn" style="background: rgba(30, 41, 59, 0.8); color: var(--text-secondary); border: 1px solid var(--admin-border);">← Retour Dashboard</a>
        <a href="admin_course.php?id=<?php echo $module_id; ?>" class="btn" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: var(--admin-primary); border: 1px solid rgba(59, 130, 246, 0.2);">📚 Cours</a>
        <button onclick="openModal()" class="btn btn-primary" style="margin-left: auto;">➕ Ajouter une question</button>
    </div>

    <div class="dash-section" id="questionList">
        <!-- Liste chargée en JS -->
    </div>
    </div>

</div> <!-- End main-content -->

    <div id="questionModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle" style="font-size: 1.5rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1.5rem; letter-spacing: -0.025em;">Ajouter une question</h2>
            <form id="questionForm">
                <input type="hidden" id="questionId">
                <div class="form-group">
                    <label>Texte de la question</label>
                    <input type="text" id="question_text" required>
                </div>
                <div class="form-group">
                    <label>Ordre</label>
                    <input type="number" id="order_index" value="0">
                </div>
                <div class="form-group">
                    <label>Options (Cochez la bonne réponse)</label>
                    <div id="optionsContainer">
                        <!-- Options générées ici -->
                    </div>
                    <button type="button" onclick="addOptionInput()" class="btn btn-primary" style="margin-top: 1rem;">
                        <span>➕</span> Ajouter une option
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Annuler</button>
                    <button type="submit" class="btn-submit">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function loadQuestions() {
            fetch('manage_content.php?action=list_quiz&module_id=<?php echo $module_id; ?>')
                .then(r => r.json())
                .then(data => {
                    const list = document.getElementById('questionList');
                    if (data.length === 0) {
                        list.innerHTML = `
                            <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                                <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">📝</div>
                                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-secondary);">Aucune question</h3>
                                <p style="margin: 0; font-size: 0.875rem;">Cliquez sur "Ajouter une question" pour commencer à créer votre quiz.</p>
                            </div>
                        `;
                        return;
                    }
                    list.innerHTML = data.map(q => `
                        <div class="question-item">
                            <div style="display:flex; justify-content:space-between; align-items: flex-start; margin-bottom: 1rem;">
                                <div style="flex: 1; margin-right: 1rem;">
                                    <h4 style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin: 0 0 0.5rem 0; line-height: 1.4;">${q.question_text}</h4>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <span style="background: var(--admin-light); color: var(--text-secondary); padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 500;">${q.options.length} options</span>
                                        <span style="background: var(--admin-success); color: white; padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 500;">1 bonne réponse</span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem; flex-shrink: 0;">
                                    <button onclick='editQuestion(${JSON.stringify(q).replace(/'/g, "&apos;")})' class="btn" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 0.5rem 1rem; font-size: 0.8rem;">Modifier</button>
                                    <button onclick="deleteQuestion(${q.id})" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.8rem;">Supprimer</button>
                                </div>
                            </div>
                            <div style="background: rgba(241, 245, 249, 0.5); border-radius: 8px; padding: 1rem;">
                                ${q.options.map(o => `<div class="option-item ${o.is_correct == 1 ? 'correct' : ''}" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0;">${o.option_text}</div>`).join('')}
                            </div>
                        </div>
                    `).join('');
                });
        }

        function addOptionInput(text = '', isCorrect = false) {
            const container = document.getElementById('optionsContainer');
            const div = document.createElement('div');
            div.className = 'option-input-row';
            div.innerHTML = `
                <input type="radio" name="is_correct" ${isCorrect ? 'checked' : ''} style="width:auto;">
                <input type="text" class="option-text" value="${text}" placeholder="Texte de l'option" required>
                <button type="button" onclick="this.parentElement.remove()" class="btn btn-danger" style="padding: 0.375rem 0.75rem; font-size: 1rem; min-width: auto;">×</button>
            `;
            container.appendChild(div);
        }

        function openModal() {
            document.getElementById('modalTitle').innerText = "Ajouter une question";
            document.getElementById('questionId').value = "";
            document.getElementById('question_text').value = "";
            document.getElementById('order_index').value = "0";
            document.getElementById('optionsContainer').innerHTML = "";
            addOptionInput(); addOptionInput(); // Commencer avec 2 options
            document.getElementById('questionModal').classList.add('open');
        }

        function closeModal() {
            document.getElementById('questionModal').classList.remove('open');
        }

        function editQuestion(q) {
            document.getElementById('modalTitle').innerText = "Modifier la question";
            document.getElementById('questionId').value = q.id;
            document.getElementById('question_text').value = q.question_text;
            document.getElementById('order_index').value = q.order_index;
            const container = document.getElementById('optionsContainer');
            container.innerHTML = "";
            q.options.forEach(o => addOptionInput(o.option_text, o.is_correct == 1));
            document.getElementById('questionModal').classList.add('open');
        }

        function deleteQuestion(id) {
            if (confirm('Supprimer cette question ?')) {
                fetch('manage_content.php?action=delete_quiz_question', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}`
                }).then(() => loadQuestions());
            }
        }

        document.getElementById('questionForm').onsubmit = function(e) {
            e.preventDefault();
            const options = [];
            const rows = document.querySelectorAll('.option-input-row');
            rows.forEach(row => {
                options.push({
                    text: row.querySelector('.option-text').value,
                    is_correct: row.querySelector('input[type="radio"]').checked
                });
            });

            const body = new URLSearchParams();
            body.append('id', document.getElementById('questionId').value);
            body.append('module_id', '<?php echo $module_id; ?>');
            body.append('question_text', document.getElementById('question_text').value);
            body.append('order_index', document.getElementById('order_index').value);
            body.append('options', JSON.stringify(options));

            fetch('manage_content.php?action=save_quiz_question', {
                method: 'POST',
                body: body
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    closeModal();
                    loadQuestions();
                } else {
                    alert(data.error);
                }
            });
        };

        // ─── User Dropdown ─────────────────────────────────────────────────────────
        const userAvatar = document.getElementById('userAvatar');
        const userDropdown = document.getElementById('userDropdown');

        userAvatar.addEventListener('click', function (e) {
          e.stopPropagation();
          userDropdown.classList.toggle('open');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function () {
          userDropdown.classList.remove('open');
        });

        loadQuestions();
    </script>
</body>
</html>
