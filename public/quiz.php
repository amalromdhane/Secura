<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
$user_role = $_SESSION['user_role'] ?? '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.html');
    exit();
}

try {
    $pdo = getDBConnection('cyber');
    
    // Récupérer les infos du module
    $stmt = $pdo->prepare("SELECT * FROM modules WHERE id = ?");
    $stmt->execute([$id]);
    $module = $stmt->fetch();
    
    if (!$module || !$module['quiz_enabled']) {
        header('Location: index.html');
        exit();
    }
    
    // Récupérer les questions et leurs options
    $stmt = $pdo->prepare("SELECT q.id as q_id, q.question_text, o.id as o_id, o.option_text, o.is_correct 
                          FROM quiz_questions q 
                          LEFT JOIN quiz_options o ON q.id = o.question_id 
                          WHERE q.module_id = ? 
                          ORDER BY q.order_index ASC, o.id ASC");
    $stmt->execute([$id]);
    $results = $stmt->fetchAll();
    
    // Organiser les données
    $quiz = [];
    foreach ($results as $row) {
        $q_id = $row['q_id'];
        if (!isset($quiz[$q_id])) {
            $quiz[$q_id] = [
                'text' => $row['question_text'],
                'options' => []
            ];
        }
        if ($row['o_id']) {
            $quiz[$q_id]['options'][] = [
                'id' => $row['o_id'],
                'text' => $row['option_text'],
                'is_correct' => $row['is_correct']
            ];
        }
    }
    
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <base href="/Secura/public/">
  <meta charset="UTF-8">
  <title>Quiz <?php echo htmlspecialchars($module['title']); ?> - Secura</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/quizStyle.css">
  <style>
    /* Assurer que le style correspond à l'original */
    .correct { background-color: rgba(40, 167, 69, 0.2) !important; border: 1px solid #28a745 !important; }
    .incorrect { background-color: rgba(220, 53, 69, 0.2) !important; border: 1px solid #dc3545 !important; }
    #quiz-results {
      display: none;
      margin-top: 3rem;
      padding: 3rem;
      border-radius: 24px;
      background: rgba(15, 23, 42, 0.85);
      border: 1px solid rgba(71, 85, 105, 0.2);
      backdrop-filter: blur(25px);
      box-shadow:
        0 25px 80px rgba(0, 0, 0, 0.4),
        0 0 60px rgba(100, 149, 237, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.05);
      position: relative;
    }

    #quiz-results::before {
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

    /* Styles pour l'avatar et le dropdown */
    .user-info {
        position: relative;
        display: flex;
        align-items: center;
        margin-left: 20px;
    }
    .user-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid #00d4ff;
        transition: all 0.3s ease;
        object-fit: cover;
    }
    .user-avatar:hover {
        border-color: #00ff88;
        box-shadow: 0 0 15px rgba(0, 212, 255, 0.5);
    }
    .user-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background: rgba(18, 24, 38, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        min-width: 180px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 9999;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }
    .user-dropdown.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    .user-dropdown-item {
        display: block;
        padding: 12px 16px;
        color: #e9ecef;
        text-decoration: none;
        transition: all 0.3s ease;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    .user-dropdown-item:last-child {
        border-bottom: none;
    }
    .user-dropdown-item:hover {
        background: rgba(0, 212, 255, 0.1);
        color: #00d4ff;
    }
    .user-dropdown-item i {
        margin-right: 8px;
        width: 16px;
    }
  </style>
</head>
<body>

<nav class="navbar">
  <div class="nav-container">
    <a class="nav-brand" href="index.html">
      <i class="fas fa-shield-alt"></i>
       Sec<span>ura</span>
    </a>
    <ul class="nav-menu">
      <li class="nav-item"><a class="nav-link" href="module.php?id=<?php echo $id; ?>"><i class="fas fa-arrow-left"></i> Retour au cours</a></li>
    </ul>
    <?php if ($is_logged_in): ?>
      <div class="user-info">
        <img src="<?php echo !empty($_SESSION['user_avatar']) ? htmlspecialchars($_SESSION['user_avatar']) : 'assets/images/default-avatar.svg'; ?>"
             alt="Avatar" class="user-avatar" id="userAvatar">
        <div class="user-dropdown" id="userDropdown">
          <a href="profil.php" class="user-dropdown-item">
            <i class="fas fa-user"></i> Mon Profil
          </a>
          <a href="login.php?action=logout" class="user-dropdown-item">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</nav>

<section class="quiz-header">
  <div class="container">
    <div class="row">
      <div class="col-lg-8 mx-auto text-center">
        <h1 class="mb-3">Quiz : <?php echo htmlspecialchars($module['title']); ?></h1>
        <p class="lead mb-4">Testez vos connaissances sur ce module.</p>
        <div class="quiz-progress" id="quizProgress">
          <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
          </div>
          <div class="progress-text">
            <span id="currentQuestion">1</span> / <span id="totalQuestions"><?php echo count($quiz); ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="container my-5">
    <div id="quiz-section">
      <form class="quiz" id="dynamicQuiz">
        <?php $count = 1; foreach ($quiz as $q_id => $q_data): ?>
        <div class="question-card" data-q-id="<?php echo $q_id; ?>">
          <div class="card-header">
            <div class="d-flex align-items-center">
              <span class="question-number"><?php echo $count++; ?></span>
              <span><?php echo htmlspecialchars($q_data['text']); ?></span>
            </div>
          </div>
          <div class="card-body">
            <?php foreach ($q_data['options'] as $option): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="q<?php echo $q_id; ?>" id="o<?php echo $option['id']; ?>" value="<?php echo $option['is_correct']; ?>">
              <label class="form-check-label" for="o<?php echo $option['id']; ?>">
                <?php echo htmlspecialchars($option['text']); ?>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>

        <div class="text-center mt-5">
          <button type="button" id="submitQuiz" class="btn-access" style="width: auto; padding: 15px 40px;">Valider mes réponses</button>
        </div>
      </form>
    </div>

    <div id="quiz-results" class="text-center">
        <div style="margin-bottom: 2rem;">
            <div style="font-size: 1.5rem; font-weight: 600; color: rgba(148, 163, 184, 0.8); margin-bottom: 1rem;">
                Votre score final
            </div>
            <div id="score-text"></div>
        </div>

        <div id="feedback-text" style="margin-bottom: 2.5rem;"></div>

        <div class="results-actions" style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="module.php?id=<?php echo $id; ?>" class="btn-access">
                <i class="fas fa-arrow-left"></i> Retour au cours
            </a>
            <button onclick="window.location.reload();" class="btn-quiz">
                <i class="fas fa-redo"></i> Recommencer
            </button>
        </div>
    </div>
</div>

<script>
// Progress tracking
function updateProgress() {
    const questions = document.querySelectorAll('.question-card');
    const answered = document.querySelectorAll('.question-card input[type="radio"]:checked').length;
    const total = questions.length;
    const percentage = (answered / total) * 100;

    document.getElementById('currentQuestion').textContent = answered;
    document.getElementById('progressFill').style.width = percentage + '%';
}

// Add progress tracking to radio buttons
document.addEventListener('change', function(e) {
    if (e.target.type === 'radio') {
        updateProgress();
    }
});

// Initialize progress
document.addEventListener('DOMContentLoaded', function() {
    updateProgress();
});

document.getElementById('submitQuiz')?.addEventListener('click', function() {
    const questions = document.querySelectorAll('.question-card');
    let score = 0;
    let total = questions.length;
    let answered = 0;

    questions.forEach(card => {
        const selected = card.querySelector('input[type="radio"]:checked');
        if (selected) {
            answered++;
            if (selected.value === "1") {
                score++;
                card.classList.add('correct');
            } else {
                card.classList.add('incorrect');
            }
        } else {
            card.classList.add('incorrect'); // Marquer comme incorrect si non répondu
        }
    });

    if (answered < total) {
        if (!confirm("Vous n'avez pas répondu à toutes les questions. Voulez-vous quand même valider ?")) {
            return;
        }
    }

    // Afficher les résultats avec animation
    document.getElementById('quiz-section').style.display = 'none';
    const resultsDiv = document.getElementById('quiz-results');
    resultsDiv.style.display = 'block';
    resultsDiv.style.animation = 'fadeIn 0.8s ease forwards';

    const percentage = Math.round((score / total) * 100);
    document.getElementById('score-text').innerHTML = `
        <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">
            ${score} / ${total}
        </div>
        <div style="font-size: 1.2rem; color: rgba(148, 163, 184, 0.8);">
            ${percentage}%
        </div>
    `;

    let feedback = "";
    let feedbackColor = "";
    if (percentage === 100) {
        feedback = "🎉 Parfait ! Vous maîtrisez parfaitement ce sujet.";
        feedbackColor = "#22c55e";
    } else if (percentage >= 80) {
        feedback = "⭐ Très bien ! Vous avez une excellente compréhension du module.";
        feedbackColor = "#16a34a";
    } else if (percentage >= 70) {
        feedback = "👍 Bien ! Vous avez une bonne compréhension du module.";
        feedbackColor = "#65a30d";
    } else if (percentage >= 60) {
        feedback = "📚 Pas mal, mais vous devriez relire certaines parties du cours.";
        feedbackColor = "#ca8a04";
    } else {
        feedback = "📖 Vous devriez revoir le cours avant de retenter le quiz.";
        feedbackColor = "#dc2626";
    }

    document.getElementById('feedback-text').innerHTML = `
        <div style="color: ${feedbackColor}; font-weight: 500; font-size: 1.1rem;">
            ${feedback}
        </div>
    `;

    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Avatar dropdown functionality
const userAvatar = document.getElementById('userAvatar');
const userDropdown = document.getElementById('userDropdown');

if (userAvatar && userDropdown) {
    userAvatar.addEventListener('click', function(e) {
        e.stopPropagation();
        userDropdown.classList.toggle('show');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!userAvatar.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.remove('show');
        }
    });
}
</script>

</body>
</html>
