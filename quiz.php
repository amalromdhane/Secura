<?php
require_once 'config.php';

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
  <meta charset="UTF-8">
  <title>Quiz <?php echo htmlspecialchars($module['title']); ?> - Secura</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/quizStyle.css">
  <style>
    /* Assurer que le style correspond à l'original */
    .correct { background-color: rgba(40, 167, 69, 0.2) !important; border: 1px solid #28a745 !important; }
    .incorrect { background-color: rgba(220, 53, 69, 0.2) !important; border: 1px solid #dc3545 !important; }
    #quiz-results { display: none; margin-top: 20px; padding: 20px; border-radius: 12px; background: rgba(18, 24, 38, 0.9); }
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
  </div>
</nav>

<section class="quiz-header">
  <div class="container">
    <div class="row">
      <div class="col-lg-8 mx-auto text-center">
        <h1 class="mb-3">Quiz : <?php echo htmlspecialchars($module['title']); ?></h1>
        <p class="lead mb-4">Testez vos connaissances sur ce module.</p>
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
        <h2 id="score-text"></h2>
        <p id="feedback-text"></p>
        <div class="mt-4">
            <a href="module.php?id=<?php echo $id; ?>" class="btn-access" style="display: inline-block; width: auto;">Retour au cours</a>
            <button onclick="window.location.reload();" class="btn-quiz" style="display: inline-block; width: auto; margin-left: 10px;">Recommencer</button>
        </div>
    </div>
</div>

<script>
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

    // Afficher les résultats
    document.getElementById('quiz-section').style.display = 'none';
    const resultsDiv = document.getElementById('quiz-results');
    resultsDiv.style.display = 'block';
    
    const percentage = (score / total) * 100;
    document.getElementById('score-text').innerText = `Votre score : ${score} / ${total} (${percentage}%)`;
    
    let feedback = "";
    if (percentage === 100) feedback = "Parfait ! Vous maîtrisez parfaitement ce sujet.";
    else if (percentage >= 70) feedback = "Très bien ! Vous avez une bonne compréhension du module.";
    else if (percentage >= 50) feedback = "Pas mal, mais vous devriez relire certaines parties du cours.";
    else feedback = "Vous devriez revoir le cours avant de retenter le quiz.";
    
    document.getElementById('feedback-text').innerText = feedback;
    window.scrollTo(0, 0);
});
</script>

</body>
</html>
