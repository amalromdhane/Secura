<?php
session_start();

require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        header('Content-Type: application/json');
        try {
            $pdo = getDBConnection('cyber');
            $stmt = $pdo->query("SELECT * FROM modules ORDER BY id DESC");
            $modules = $stmt->fetchAll();
            echo json_encode(['success' => true, 'modules' => $modules]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();

    case 'add':
    case 'update':
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Non autorisé - Session: ' . (isset($_SESSION['user_logged_in']) ? 'yes' : 'no') . ', Role: ' . ($_SESSION['user_role'] ?? 'none')]);
            exit();
        }

        try {
            $pdo = getDBConnection('cyber');

            $data = [
                'title' => $_POST['title'] ?? '',
                'category' => $_POST['category'] ?? '',
                'duration' => (int)($_POST['duration'] ?? 30),
                'description' => $_POST['description'] ?? '',
                'image' => $_POST['image'] ?? '',
                'page' => $_POST['page'] ?? '',
                'video_url' => $_POST['video_url'] ?? '',
                'content' => $_POST['content'] ?? '',
                'quiz_enabled' => isset($_POST['quiz_enabled']) ? (int)$_POST['quiz_enabled'] : 0,
                'quiz_page' => $_POST['quiz_page'] ?? '',
                'active' => isset($_POST['active']) ? (int)$_POST['active'] : 1,
            ];

            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO modules (title, category, duration, description, image, page, video_url, content, quiz_enabled, quiz_page, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $success = $stmt->execute(array_values($data));
                $insertId = $pdo->lastInsertId();
            } else {
                $data['id'] = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE modules SET title=?, category=?, duration=?, description=?, image=?, page=?, video_url=?, content=?, quiz_enabled=?, quiz_page=?, active=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
                $success = $stmt->execute(array_values($data));
                $insertId = $data['id'];
            }

            echo json_encode(['success' => $success, 'id' => $insertId ?? null, 'data' => $data]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        exit();

    case 'delete':
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            exit();
        }

        try {
            $pdo = getDBConnection('cyber');
            $stmt = $pdo->prepare("DELETE FROM modules WHERE id=?");
            $success = $stmt->execute([$_POST['id']]);
            echo json_encode(['success' => $success]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();

    default:
        // Fall through to display mode
        break;
}

// Display mode - existing code
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
    
    if (!$module) {
        header('Location: index.html');
        exit();
    }
    
    // Récupérer les chapitres
    $stmt = $pdo->prepare("SELECT * FROM course_chapters WHERE module_id = ? ORDER BY order_index ASC");
    $stmt->execute([$id]);
    $chapters = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($module['title']); ?> - Secura</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/cyberaware.css">
  <style>
    /* Styles pour les modules dynamiques */
    .video-wrapper {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
        border-radius: 12px;
        margin-bottom: 15px;
    }
    .video-wrapper iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
    }
    .card-module {
        background: rgba(18, 24, 38, 0.85);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 18px;
        padding: 30px;
        margin-bottom: 30px;
    }
    .section-title {
        color: var(--neon-cyan);
        margin-bottom: 20px;
        font-weight: 700;
        display: flex;
        align-items: center;
    }
    .text-cyan { color: var(--neon-cyan); }
    .module-hero-placeholder {
        width: 100%;
        max-width: 500px;
        height: 400px;
        background: linear-gradient(135deg, rgba(13,110,253,0.1), rgba(0,212,255,0.1));
        border: 3px solid var(--neon-cyan);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: var(--glow-cyan);
    }
    .module-hero-placeholder i {
        font-size: 8rem;
        color: var(--neon-cyan);
        opacity: 0.7;
    }
    .text-danger { color: #dc3545; }
    .text-warning { color: #ffc107; }
    .text-info { color: #0dcaf0; }
    .text-primary { color: #0d6efd; }
    .text-success { color: #198754; }
    .content-box {
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.05);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 15px;
    }
    .content-box h6 {
        margin-bottom: 10px;
        font-weight: 600;
    }
    .warning-box {
        background: rgba(220,53,69,0.1);
        border: 1px solid rgba(220,53,69,0.2);
        border-radius: 8px;
        padding: 20px;
    }
    .tip-box {
        background: rgba(25,135,84,0.1);
        border: 1px solid rgba(25,135,84,0.2);
        border-radius: 8px;
        padding: 20px;
    }
    .comparison-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        background: rgba(255,255,255,0.02);
        border-radius: 8px;
        overflow: hidden;
    }
    .comparison-table th,
    .comparison-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .comparison-table th {
        background: rgba(0,212,255,0.1);
        color: var(--neon-cyan);
        font-weight: 600;
    }
    .comparison-table tr:hover {
        background: rgba(255,255,255,0.02);
    }
    .badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
        margin-top: 5px;
    }
    .badge-danger { background: rgba(220,53,69,0.2); color: #dc3545; }
    .badge-warning { background: rgba(255,193,7,0.2); color: #ffc107; }
    .badge-info { background: rgba(13,202,240,0.2); color: #0dcaf0; }
    .badge-primary { background: rgba(13,110,253,0.2); color: #0d6efd; }
    .progress-sidebar .card-module {
        position: sticky;
        top: 20px;
    }
    .lesson-step {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .lesson-step:last-child { border-bottom: none; }
    .lesson-step.active .step-number {
        background: var(--neon-cyan);
        color: #000;
    }
    .step-number {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #adb5bd;
        flex-shrink: 0;
    }
    .quiz-cta {
        display: block;
        background: linear-gradient(135deg, #0d6efd, #00d4ff);
        color: white;
        text-decoration: none;
        border-radius: 12px;
        padding: 25px;
        text-align: center;
        margin: 30px 0;
        transition: transform 0.3s ease;
    }
    .quiz-cta:hover {
        transform: translateY(-2px);
        color: white;
    }
    .quiz-cta h3 { margin: 0; font-weight: 700; }
  </style>
</head>
<body>

<nav class="navbar">
  <div class="nav-container">
    <a class="nav-brand" href="index.html">
      <i class="fas fa-shield-alt"></i>
       Sec<span>ura</span>
    </a>
    
    <button class="nav-toggler" id="navToggler">
      <i class="fas fa-bars"></i>
    </button>
    
    <ul class="nav-menu" id="navMenu">
      <li class="nav-item">
        <a class="nav-link" href="all_modules.php">
          <i class="fas fa-layer-group"></i> Modules
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="index.html">
          <i class="fas fa-home"></i> Accueil
        </a>
      </li>
    </ul>
  </div>
</nav>

<section class="hero-section">
  <div class="container">
    <nav aria-label="breadcrumb" class="breadcrumb mb-4">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.html">Accueil</a></li>
        <li class="breadcrumb-item"><a href="all_modules.php">Modules</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($module['title']); ?></li>
      </ol>
    </nav>
    
    <div class="row align-items-center">
      <div class="col-lg-6">
        <div class="module-icon">
          <i class="<?php
            $categoryIcons = [
              'Sécurité' => 'fas fa-shield-alt',
              'Réseau' => 'fas fa-network-wired',
              'Données' => 'fas fa-database',
              'Cloud' => 'fas fa-cloud',
              'IA' => 'fas fa-brain',
              'Phishing' => 'fas fa-fish',
              'Ransomware' => 'fas fa-lock'
            ];
            echo htmlspecialchars($categoryIcons[$module['category']] ?? 'fas fa-graduation-cap');
          ?>"></i>
        </div>
        <h1 class="display-3" style="color: var(--neon-cyan); text-shadow: var(--glow-cyan);"><?php echo htmlspecialchars($module['title']); ?></h1>
        <p class="lead fs-5"><?php echo htmlspecialchars($module['description']); ?></p>
      </div>
      <div class="col-lg-6 text-center">
        <?php if ($module['image']): ?>
        <img src="<?php echo htmlspecialchars($module['image']); ?>" alt="<?php echo htmlspecialchars($module['title']); ?>" class="img-fluid rounded-4 shadow-lg" style="height: 350px; width: 750px; border: 3px solid var(--neon-cyan); box-shadow: var(--glow-cyan);">
        <?php else: ?>
        <div class="module-hero-placeholder">
          <i class="fas fa-graduation-cap"></i>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<div class="container py-5">
  <div class="row">
    <div class="col-lg-8">

      <?php
      $chapter_index = 1;
      foreach ($chapters as $chapter):
      ?>
      <div class="card-module">
        <h2 class="section-title">
          <?php
          // Try to extract icon from content or use default
          $icon_class = 'fas fa-bookmark';
          if (preg_match('/<i class="([^"]*fa-[^"]*)"/', $chapter['content'], $matches)) {
            $icon_class = $matches[1];
          }
          ?>
          <i class="<?php echo $icon_class; ?> me-2"></i><?php echo htmlspecialchars($chapter['title']); ?>
        </h2>

        <?php if ($chapter['video_url']): ?>
        <div class="video-wrapper">
          <iframe src="<?php echo htmlspecialchars($chapter['video_url']); ?>"
                  title="<?php echo htmlspecialchars($chapter['title']); ?>"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                  allowfullscreen>
          </iframe>
        </div>
        <p class="text-muted small">Tutoriel vidéo : <?php echo htmlspecialchars($chapter['title']); ?></p>
        <?php endif; ?>

        <div class="chapter-content">
            <?php echo $chapter['content']; // HTML content allowed from admin ?>
        </div>
      </div>
      <?php
      $chapter_index++;
      endforeach;
      ?>

      <?php if (empty($chapters)): ?>
      <div class="card-module text-center">
          <p>Aucun contenu disponible pour ce module pour le moment.</p>
      </div>
      <?php endif; ?>

      <?php if ($module['quiz_enabled']): ?>
      <a href="quiz.php?id=<?php echo $module['id']; ?>" class="quiz-cta">
        <h3 class="mb-3">Validez vos connaissances</h3>
      </a>
      <?php endif; ?>
    </div>

    <div class="col-lg-4">
      <div class="progress-sidebar">
        <div class="card-module">
          <h5 class="text-cyan mb-4"><i class="fas fa-list-check me-2"></i>Plan du module</h5>

          <?php
          $lesson_index = 1;
          foreach ($chapters as $chapter):
          ?>
          <div class="lesson-step <?php echo ($lesson_index == 1) ? 'active' : ''; ?>">
            <div class="step-number"><?php echo $lesson_index; ?></div>
            <div>
              <strong class="d-block"><?php echo htmlspecialchars($chapter['title']); ?></strong>
              <small class="text-muted"><?php echo htmlspecialchars(substr(strip_tags($chapter['content']), 0, 50)) . '...'; ?></small>
            </div>
          </div>
          <?php
          $lesson_index++;
          endforeach;
          ?>

          <?php if ($module['quiz_enabled']): ?>
          <div class="lesson-step">
            <div class="step-number"><?php echo $lesson_index; ?></div>
            <div>
              <strong class="d-block">Quiz d'évaluation</strong>
              <small class="text-muted">Test final et certification</small>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      
    </div>
  </div>
</div>

<footer>
  <div class="container">
    <div class="row">
      <div class="col-lg-8 mx-auto text-center">
        <div class="d-flex justify-content-center mb-4">
          <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
               style="width: 50px; height: 50px; background: rgba(0, 255, 255, 0.1);">
            <i class="fas fa-shield-alt text-cyan fs-4"></i>
          </div>
          <div class="rounded-circle d-flex align-items-center justify-content-center"
               style="width: 50px; height: 50px; background: rgba(255, 100, 100, 0.1);">
            <i class="fas fa-bug text-danger fs-4"></i>
          </div>
        </div>
        <h3 class="mb-3">Secura</h3>
        <p class="text-muted mb-4">Formation en ligne à la cybersécurité pour tous</p>
        <div class="d-flex justify-content-center gap-4 mb-4">
          <a href="#" class="text-cyan">
            <i class="fab fa-github fs-4"></i>
          </a>
          <a href="#" class="text-cyan">
            <i class="fab fa-linkedin fs-4"></i>
          </a>
          <a href="#" class="text-cyan">
            <i class="fab fa-envelope fs-4"></i>
          </a>
        </div>
        <hr style="border-color: rgba(255,255,255,0.1); margin: 1.5rem 0;">
        <p class="text-muted small mb-0">
          © 2026 Secura - Module <?php echo htmlspecialchars($module['title']); ?>
        </p>
      </div>
    </div>
  </div>
</footer>

<script>
    // Simple script for mobile nav if needed
    document.getElementById('navToggler')?.addEventListener('click', function() {
        document.getElementById('navMenu')?.classList.toggle('active');
    });
</script>
</body>
</html>
