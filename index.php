<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    $is_logged_in = false;
    $user_role = '';
} else {
    $is_logged_in = true;
    $user_role = $_SESSION['user_role'] ?? '';
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

$home_modules = [];
try {
    $pdo = getDBConnection('cyber');
    $stmt = $pdo->query(
        'SELECT id, title, description, category, duration, image, page, quiz_enabled
         FROM modules WHERE active = 1 ORDER BY id DESC'
    );
    $home_modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $home_modules = [];
}

function home_module_image(array $m): string
{
    if (!empty($m['image'])) {
        return $m['image'];
    }
    return 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&w=480&q=80';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Secura - Sensibilisation à la Cybersécurité</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">

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
          <a class="nav-link" href="index.php">
            <i class="fas fa-home"></i> Accueil
          </a>
        </li>
        <li class="nav-item">

          <a class="nav-link" href="index.php#modules">

            <i class="fas fa-layer-group"></i> Modules
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="index.php#about">
            <i class="fas fa-info-circle"></i> À propos
          </a>
        </li>
        <li class="nav-item">

          <a class="nav-link" href="index.php#contact">

            <i class="bi bi-headset"></i> Contact
          </a>
        </li>
        <?php if (!$is_logged_in): ?>
          <li class="nav-item">
            <a class="nav-link" href="login.php">
              <i class="fas fa-sign-in-alt"></i> Connexion
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>


  <section class="cyber-hero">
    <div class="floating-shapes">
      <div class="shape"></div>
      <div class="shape"></div>
      <div class="shape"></div>
    </div>
    <div class="container">
      <div class="hero-content">
        <div class="hero-subtitle">SÉCURITÉ NUMÉRIQUE</div>
        <h1 class="hero-title">Protégez votre <span class="gradient-text">monde numérique</span></h1>
        <p class="hero-description">
          Formez-vous aux bonnes pratiques de cybersécurité avec des modules interactifs,
          des simulations réalistes et des conseils d'experts.
        </p>
        <div class="btn-group">
          <a href="#modules" class="btn btn-primary"><i class="bi bi-play-circle"></i> Commencer la formation</a>
          <a href="#objectifs" class="btn btn-outline"><i class="bi bi-info-circle"></i> En savoir plus</a>
        </div>
      </div>
    </div>
  </section>


  <section id="objectifs" class="py-5">
    <div class="container">
      <div class="text-center mb-5">
        <h2 class="display-5">Pourquoi <span
            style="background:var(--cyber-gradient);-webkit-background-clip:text;background-clip:text;color:transparent;">Secura</span>
          ?</h2>
        <p class="lead text-muted">Une approche simple, visuelle et éducative pour la cybersécurité</p>
      </div>
      <div class="row">
        <div class="col col-md-4">
          <div class="cyber-card text-center">

            <img src="https://i.pinimg.com/736x/47/3b/a6/473ba641121eae735636ce746a00e3db.jpg"
              alt="Apprentissage pédagogique"
              style="width: 320px; height: 200px; border-radius: 16px; object-fit: cover; border: 2px solid ;">
            <h5 class="mt-4 mb-3">Apprentissage pédagogique</h5>
            <p class="text-muted mb-0">Comprendre la cybersécurité sans prérequis techniques grâce à une approche
              progressive et accessible.</p>

          </div>
        </div>
        <div class="col col-md-4">
          <div class="cyber-card text-center">

            <img src="https://i.pinimg.com/1200x/d6/5f/5a/d65f5a0a1fd339365ec010297f0c037e.jpg"
              alt="Sensibilisation visuelle"
              style="width: 320px; height: 200px; border-radius: 16px; border: 2px solid">
            <h5 class="mt-4 mb-3">Sensibilisation visuelle</h5>
            <p class="text-muted mb-0">Identifier rapidement les menaces numériques courantes grâce à des exemples
              concrets et visuels.</p>

          </div>
        </div>
        <div class="col col-md-4">
          <div class="cyber-card text-center">

            <img src="https://i.pinimg.com/1200x/05/34/f7/0534f7df0b08edc70592d10d4bd908c0.jpg" alt="Bonnes pratiques"
              style="width: 320px; height: 200px; border-radius: 16px; object-fit: cover; border: 2px solid">
            <h5 class="mt-4 mb-3">Bonnes pratiques</h5>
            <p class="text-muted mb-0">Adopter les bons réflexes pour protéger ses données personnelles et
              professionnelles.</p>

          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="section-divider"></div>


  <section id="modules" class="py-5" style="background: rgba(18, 24, 38, 0.5);">
    <div class="container">
      <div class="text-center mb-5">
        <h2 class="display-5">Modules de <span
            style="background:var(--cyber-gradient);-webkit-background-clip:text;background-clip:text;color:transparent;">Sensibilisation</span>
        </h2>
        <p class="lead text-muted">Les principales menaces expliquées simplement</p>
      </div>
      <div class="row" id="homeModulesRow">
        <?php if (empty($home_modules)): ?>
        <div class="col-12 text-center py-4">
          <p class="text-muted mb-3">Aucun module publié pour le moment.</p>
          <?php if ($user_role === 'admin'): ?>
          <a href="admin_dashboard.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i> Ajouter un module</a>
          <?php else: ?>
          <a href="all_modules.php" class="btn btn-outline">Voir le catalogue</a>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <?php foreach ($home_modules as $mod): ?>
        <div class="col col-md-3 col-sm-6 mb-4">
          <div class="cyber-card text-center h-100">
            <img
              src="<?php echo htmlspecialchars(home_module_image($mod)); ?>"
              alt="<?php echo htmlspecialchars($mod['title']); ?>"
              style="width: 240px; max-width: 100%; height: 150px; border-radius: 16px; object-fit: cover; border: 2px solid var(--neon-cyan); box-shadow: var(--glow-cyan);">
            <h5 class="mt-4 mb-3"><?php echo htmlspecialchars($mod['title']); ?></h5>
            <p class="text-muted mb-4">
              <?php
              $desc = trim($mod['description'] ?? '');
              echo htmlspecialchars($desc !== '' ? $desc : 'Module de sensibilisation — ' . ($mod['category'] ?? 'Cybersécurité'));
              ?>
            </p>
            <a href="<?php echo htmlspecialchars(module_access_href($mod, $is_logged_in)); ?>" class="btn btn-primary<?php echo $is_logged_in ? '' : ' btn-module-locked'; ?>">
              <i class="bi bi-<?php echo $is_logged_in ? 'play-circle' : 'lock'; ?> me-2"></i>
              <?php echo $is_logged_in ? 'Accéder au cours' : 'Se connecter pour accéder'; ?>
            </a>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <?php if (!empty($home_modules)): ?>
      <div class="text-center mt-4">
        <a href="all_modules.php" class="btn btn-outline"><i class="bi bi-grid me-2"></i> Voir tous les modules</a>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <div class="section-divider"></div>


  <section id="about" class="py-5">
    <div class="container">
      <div class="row align-items-center">
        <div class="col col-md-6 mb-5">
          <h2 class="display-5 mb-4">À propos de <span
              style="background:var(--cyber-gradient);-webkit-background-clip:text;background-clip:text;color:transparent;">Secura</span>
          </h2>
          <p class="lead text-muted mb-4">
            Secura est une plateforme de sensibilisation à la cybersécurité conçue pour aider les utilisateurs à
            comprendre les menaces numériques actuelles et à adopter les bons réflexes pour protéger leurs données.
          </p>
          <p class="text-muted mb-4">
            Grâce à des modules visuels, des scénarios réalistes et des contenus pédagogiques clairs, Secura rend la
            cybersécurité accessible à tous, sans prérequis techniques.
          </p>
          <ul style="padding-left: 2rem; list-style: none;">
            <li class="mb-3"><strong>Plateforme éducative</strong> – Contenus pratiques basés sur des menaces réelles
            </li>
            <li class="mb-3"><strong>Approche visuelle</strong> – Simulations et exemples concrets</li>
            <li class="mb-3"><strong>Accessible à tous</strong> – Particuliers, étudiants et professionnels</li>
            <li class="mb-3"><strong>Bonnes pratiques</strong> – Alignées avec les standards de sécurité</li>
            <li><strong>Évolution continue</strong> – Nouveaux contenus et modules régulièrement ajoutés</li>
          </ul>
          <div class="mt-4">
            <a href="#modules" class="btn btn-primary me-3"><i class="bi bi-laptop me-2"></i> Explorer les modules</a>
            <a href="#contact" class="btn btn-outline"><i class="bi bi-envelope me-2"></i> Nous contacter</a>
          </div>
        </div>
        <div class="col col-md-6 text-center">
          <i class="bi bi-shield-shaded" style="font-size:9rem;color:#0d6efd;"></i>
          <div class="row text-center mt-4">
            <div class="col">
              <div style="font-size:2rem;font-weight:700;color:var(--cyber-primary);margin-left:170px;"><?php echo max(1, count($home_modules)); ?>+</div><small
                class="text-muted" style="margin-left:170px;">Modules</small>
            </div>
            <div class="col">
              <div style="font-size:2rem;font-weight:700;color:var(--cyber-success);">60+</div><small
                class="text-muted">Leçons</small>
            </div>
            <div class="col">
              <div style="font-size:2rem;font-weight:700;color:var(--cyber-warning);">8+</div><small
                class="text-muted">Quiz & scénarios</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>


  <section id="contact" class="py-5 contact-section"
    style="background: linear-gradient(135deg, rgba(10,14,23,0.98) 0%, rgba(18,24,38,0.95) 100%);">
    <div class="container">
      <div class="contact-header text-center mb-5">
        <div class="contact-icon mx-auto">
          <i class="bi bi-headset"></i>
        </div>
        <h2 class="display-5 mb-3"
          style="background: linear-gradient(135deg, #0d6efd 0%, #00d4ff 100%); -webkit-background-clip: text; background-clip: text; color: transparent;">
          Contactez-nous
        </h2>
        <p class="lead text-muted">Une question sur le module ? Besoin d'aide ? Notre équipe est là pour vous.</p>
      </div>

      <div class="row g-4 align-items-center">

        <div class="col col-lg-7">
          <div class="contact-card">
            <h4 class="mb-4" style="color:var(--cyber-primary);">
              <i class="bi bi-send me-2"></i>Envoyez-nous un message
            </h4>

            <div id="contactAlert" class="contact-alert" role="alert" style="display:none;"></div>

            <form id="contactForm" novalidate>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label" for="contactName">Nom complet <span class="required-star">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" id="contactName" name="full_name" class="form-control" placeholder="Votre nom" required>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label" for="contactEmail">Email <span class="required-star">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" id="contactEmail" name="email" class="form-control" placeholder="votre@email.com" required>
                  </div>
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label" for="contactSubject">Sujet <span class="required-star">*</span></label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-tag"></i></span>
                  <select id="contactSubject" name="subject" class="form-select" required>
                    <option value="" selected disabled>Sélectionnez un sujet</option>
                    <option value="Question sur un module">Question sur un module</option>
                    <option value="Problème technique">Problème technique</option>
                    <option value="Suggestion d'amélioration">Suggestion d'amélioration</option>
                    <option value="Partenaire/Formation entreprise">Partenaire/Formation entreprise</option>
                    <option value="Autre demande">Autre demande</option>
                  </select>
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label" for="contactMessage">Message <span class="required-star">*</span></label>
                <textarea id="contactMessage" name="message" class="form-textarea" placeholder="Décrivez votre question ou demande en détail..."
                  required minlength="10"></textarea>
              </div>

              <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter" value="1">
                <label class="form-check-label" for="newsletter">
                  Je souhaite recevoir les mises à jour et les nouveaux modules par email
                </label>
              </div>

              <div class="text-center">
                <button type="submit" id="contactSubmit" class="submit-btn">
                  <i class="bi bi-send-check me-2"></i>Envoyer le message
                </button>
              </div>
            </form>
          </div>
        </div>


        <div class="col col-lg-5">
          <div class="col col-lg-5  text-center">
            <img src="assets/contact-bg.png" alt="Contact" style="width:550px;height:600px;border-radius:12px;">
          </div>
        </div>
      </div>
    </div>
    </div>
  </section>


  <footer class="cyber-footer">
    <div class="container">
      <div class="row">
        <div class="col col-lg-8" style="margin:0 auto;">
          <div class="d-flex justify-content-center mb-4" style="gap:1.5rem;">
            <div
              style="width:50px;height:50px;background:rgba(13,110,253,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-shield-lock" style="font-size:1.8rem;color:var(--cyber-primary);"></i>
            </div>
            <div
              style="width:50px;height:50px;background:rgba(32,201,151,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-lock" style="font-size:1.8rem;color:var(--cyber-success);"></i>
            </div>
          </div>
          <h3 class="text-center mb-3"><span style="color:var(--cyber-accent);">Secura</span></h3>
          <p class="text-center text-muted mb-4">Plateforme de sensibilisation à la cybersécurité</p>
          <div class="d-flex justify-content-center mb-4" style="gap:1.5rem;">
            <a href="#" style="color:var(--cyber-primary);"><i class="bi bi-github" style="font-size:1.8rem;"></i></a>
            <a href="#" style="color:var(--cyber-primary);"><i class="bi bi-linkedin" style="font-size:1.8rem;"></i></a>
            <a href="#" style="color:var(--cyber-primary);"><i class="bi bi-twitter-x"
                style="font-size:1.8rem;"></i></a>
            <a href="#" style="color:var(--cyber-primary);"><i class="bi bi-envelope" style="font-size:1.8rem;"></i></a>
          </div>
          <hr style="opacity:0.25;margin:2rem 0;">
          <p class="text-center text-muted small mb-0">© 2026 Secura - Plateforme de Sensibilisation à la
            Cybersécurité</p>
        </div>
      </div>
    </div>
  </footer>

  <script src="js/contact.js"></script>
</body>

</html>