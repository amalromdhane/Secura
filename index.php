<?php
session_start();
require_once 'api/config.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username   = $_SESSION['username']   ?? '';
$firstName  = $_SESSION['first_name'] ?? '';

// Check for logout success message
$logoutSuccess = isset($_GET['logout']) && $_GET['logout'] === 'success';
// Check for login success message
$loginSuccess = isset($_GET['login']) && $_GET['login'] === 'success';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Secura - Sensibilisation à la Cybersécurité</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="./css/style.css">
  <style>
    /* ── User Dropdown ─────────────────────────────────────── */
    .user-dropdown { position: relative; }

    .user-dropdown-toggle {
      background: rgba(255,255,255,0.07);
      border: 1px solid rgba(255,255,255,0.15);
      color: var(--cyber-primary);
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.45rem 1rem;
      border-radius: 12px;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
      position: relative;
      overflow: hidden;
      font-size: 0.95rem;
    }

    .user-dropdown-toggle::before {
      content: '';
      position: absolute;
      top: 0; left: -100%;
      width: 100%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(13,110,253,0.18), transparent);
      transition: left 0.45s ease;
    }

    .user-dropdown-toggle:hover {
      background: rgba(13,110,253,0.13);
      border-color: var(--cyber-primary);
      transform: translateY(-2px);
      box-shadow: 0 5px 18px rgba(13,110,253,0.28);
    }

    .user-dropdown-toggle:hover::before { left: 100%; }

    .user-dropdown-toggle .bi-chevron-down {
      font-size: 0.75rem;
      transition: transform 0.3s ease;
    }

    .user-dropdown-toggle.open .bi-chevron-down {
      transform: rotate(180deg);
    }

    /* ── Dropdown Menu ─────────────────────────────────────── */
    .user-dropdown-menu {
      position: absolute;
      top: calc(100% + 8px);
      right: 0;
      background: rgba(10,14,23,0.97);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(13,110,253,0.25);
      border-radius: 16px;
      min-width: 210px;
      box-shadow: 0 16px 40px rgba(0,0,0,0.45);
      z-index: 1050;
      /* Hidden by default */
      display: none;
      opacity: 0;
      transform: translateY(-8px);
      transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.4,0,0.2,1);
      pointer-events: none;
    }

    .user-dropdown-menu.show {
      display: block;
      opacity: 1;
      transform: translateY(0);
      pointer-events: auto;
    }

    /* ── Dropdown Items ──────────────────────────────────────── */
    .user-dropdown-item {
      display: flex;
      align-items: center;
      padding: 0.9rem 1.2rem;
      color: rgba(255,255,255,0.88);
      text-decoration: none;
      transition: background 0.25s ease, transform 0.2s ease, color 0.2s;
      border-bottom: 1px solid rgba(255,255,255,0.07);
      position: relative;
      overflow: hidden;
      gap: 0.65rem;
      font-size: 0.92rem;
    }

    .user-dropdown-item:first-child { border-radius: 16px 16px 0 0; }
    .user-dropdown-item:last-child  { border-radius: 0 0 16px 16px; border-bottom: none; }

    .user-dropdown-item:hover {
      background: rgba(13,110,253,0.1);
      color: #fff;
      transform: translateX(4px);
    }

    .user-dropdown-item i { font-size: 1.05rem; }

    /* Logout row */
    .user-dropdown-item.logout {
      border-top: 1px solid rgba(220,53,69,0.25);
    }

    .user-dropdown-item.logout i  { color: #dc3545; }
    .user-dropdown-item.logout:hover { background: rgba(220,53,69,0.1); }

    /* ── Avatar ──────────────────────────────────────────────── */
    .user-avatar {
      width: 34px; height: 34px;
      border-radius: 50%;
      background: linear-gradient(135deg, #0d6efd 0%, #00d4ff 100%);
      display: flex; align-items: center; justify-content: center;
      color: #fff;
      font-weight: 700;
      font-size: 0.95rem;
      box-shadow: 0 3px 10px rgba(13,110,253,0.35);
      flex-shrink: 0;
      overflow: hidden;
      position: relative;
    }

    .user-avatar::after {
      content: '';
      position: absolute;
      top: -50%; left: -50%;
      width: 200%; height: 200%;
      background: linear-gradient(45deg, transparent, rgba(255,255,255,0.12), transparent);
      animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
      0%   { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
      100% { transform: translateX(100%)  translateY(100%)  rotate(45deg); }
    }

    .user-name {
      font-weight: 600;
      color: rgba(255,255,255,0.95);
      max-width: 110px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    /* ── Connexion button (guest) ─────────────────────────────── */
    .nav-link.btn-login {
      background: rgba(13,110,253,0.12);
      border: 1px solid rgba(13,110,253,0.35);
      border-radius: 10px;
      padding: 0.4rem 1rem;
      transition: all 0.3s ease;
    }

    .nav-link.btn-login:hover {
      background: rgba(13,110,253,0.22);
      border-color: var(--cyber-primary);
      box-shadow: 0 4px 14px rgba(13,110,253,0.28);
    }

    /* ── Logout toast ─────────────────────────────────────────── */
    .logout-toast {
      position: fixed;
      top: 80px; right: 20px;
      z-index: 2000;
      background: rgba(40,167,69,0.92);
      border: 1px solid rgba(40,167,69,0.4);
      color: #fff;
      border-radius: 10px;
      padding: 0.9rem 1.4rem;
      box-shadow: 0 10px 28px rgba(40,167,69,0.3);
      backdrop-filter: blur(10px);
      animation: slideIn 0.4s ease;
    }

    /* ── Login toast ─────────────────────────────────────────── */
    .login-toast {
      position: fixed;
      top: 80px; right: 20px;
      z-index: 2000;
      background: rgba(13,110,253,0.92);
      border: 1px solid rgba(13,110,253,0.4);
      color: #fff;
      border-radius: 10px;
      padding: 0.9rem 1.4rem;
      box-shadow: 0 10px 28px rgba(13,110,253,0.3);
      backdrop-filter: blur(10px);
      animation: slideIn 0.4s ease;
    }

    @keyframes slideIn {
      from { opacity: 0; transform: translateX(40px); }
      to   { opacity: 1; transform: translateX(0); }
    }
  </style>
</head>

<body>

  <!-- ══════════════════════ NAVBAR ════════════════════════════ -->
  <nav class="navbar">
    <div class="nav-container">

      <a class="nav-brand" href="index.php">
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

        <!--
          LOGIQUE CONDITIONNELLE
          ✅ Connecté  → avatar + prénom + dropdown (profil / paramètres / déconnexion)
          ✅ Non connecté → bouton "Connexion" uniquement
        -->
        <?php if ($isLoggedIn && $firstName !== ''): ?>

          <li class="nav-item user-dropdown" id="userDropdownWrapper">
            <button
              class="nav-link user-dropdown-toggle"
              id="dropdownToggleBtn"
              onclick="toggleDropdown(event)"
              aria-haspopup="true"
              aria-expanded="false"
            >
              <div class="user-avatar">
                <?php echo strtoupper(substr($firstName, 0, 1)); ?>
              </div>
              <span class="user-name"><?php echo htmlspecialchars($firstName); ?></span>
              <i class="bi bi-chevron-down"></i>
            </button>

            <div class="user-dropdown-menu" id="userDropdownMenu" role="menu">
              <a href="profile.php" class="user-dropdown-item" role="menuitem">
                <i class="bi bi-person-circle"></i> Mon profil
              </a>
              <a href="settings.php" class="user-dropdown-item" role="menuitem">
                <i class="bi bi-gear"></i> Paramètres
              </a>
              <a href="api/logout.php" class="user-dropdown-item logout" role="menuitem">
                <i class="bi bi-box-arrow-left"></i> Déconnexion
              </a>
            </div>
          </li>

        <?php else: ?>

          <li class="nav-item">
            <a class="nav-link btn-login" href="login.php">
              <i class="bi bi-person-circle"></i> Connexion
            </a>
          </li>

        <?php endif; ?>

      </ul>
    </div>
  </nav>

  <!-- ══════════════════════ LOGOUT TOAST ══════════════════════ -->
  <?php if ($logoutSuccess): ?>
    <div class="logout-toast" id="logoutToast">
      <i class="bi bi-check-circle-fill me-2"></i>
      <strong>Déconnexion réussie</strong><br>
      <small>Vous avez été déconnecté avec succès.</small>
    </div>
  <?php endif; ?>

  <!-- ══════════════════════ LOGIN TOAST ══════════════════════ -->
  <?php if ($loginSuccess): ?>
    <div class="login-toast" id="loginToast">
      <i class="bi bi-check-circle-fill me-2"></i>
      <strong>Connexion réussie</strong><br>
      <small>Bienvenue <?php echo htmlspecialchars($firstName); ?> !</small>
    </div>
  <?php endif; ?>

  <!-- ══════════════════════ HERO ══════════════════════════════ -->
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

  <!-- ══════════════════════ OBJECTIFS ═════════════════════════ -->
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
              style="width:320px;height:200px;border-radius:16px;object-fit:cover;border:2px solid;">
            <h5 class="mt-4 mb-3">Apprentissage pédagogique</h5>
            <p class="text-muted mb-0">Comprendre la cybersécurité sans prérequis techniques grâce à une approche
              progressive et accessible.</p>
          </div>
        </div>
        <div class="col col-md-4">
          <div class="cyber-card text-center">
            <img src="https://i.pinimg.com/1200x/d6/5f/5a/d65f5a0a1fd339365ec010297f0c037e.jpg"
              alt="Sensibilisation visuelle"
              style="width:320px;height:200px;border-radius:16px;border:2px solid">
            <h5 class="mt-4 mb-3">Sensibilisation visuelle</h5>
            <p class="text-muted mb-0">Identifier rapidement les menaces numériques courantes grâce à des exemples
              concrets et visuels.</p>
          </div>
        </div>
        <div class="col col-md-4">
          <div class="cyber-card text-center">
            <img src="https://i.pinimg.com/1200x/05/34/f7/0534f7df0b08edc70592d10d4bd908c0.jpg"
              alt="Bonnes pratiques"
              style="width:320px;height:200px;border-radius:16px;object-fit:cover;border:2px solid">
            <h5 class="mt-4 mb-3">Bonnes pratiques</h5>
            <p class="text-muted mb-0">Adopter les bons réflexes pour protéger ses données personnelles et
              professionnelles.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="section-divider"></div>

  <!-- ══════════════════════ MODULES ═══════════════════════════ -->
  <section id="modules" class="py-5" style="background:rgba(18,24,38,0.5);">
    <div class="container">
      <div class="text-center mb-5">
        <h2 class="display-5">Modules de <span
            style="background:var(--cyber-gradient);-webkit-background-clip:text;background-clip:text;color:transparent;">Sensibilisation</span>
        </h2>
        <p class="lead text-muted">Les principales menaces expliquées simplement</p>
      </div>
      <div class="row">
        <div class="col col-md-3">
          <div class="cyber-card text-center">
            <img src="https://thumbs.dreamstime.com/b/glowing-envelope-symbol-represents-phishing-attacks-illustration-email-scams-cyber-security-threats-digital-communication-risk-407759383.jpg"
              alt="Phishing & Hameçonnage"
              style="width:240px;height:150px;border-radius:16px;object-fit:cover;border:2px solid var(--neon-cyan);box-shadow:var(--glow-cyan);">
            <h5 class="mt-4 mb-3">Phishing & Hameçonnage</h5>
            <p class="text-muted mb-4">Reconnaître les emails et messages frauduleux pour éviter les pièges.</p>
            <a href="./pages/phishing.html" class="btn btn-primary"><i class="bi bi-play-circle me-2"></i> Accéder au cours</a>
          </div>
        </div>
        <div class="col col-md-3">
          <div class="cyber-card text-center">
            <img src="https://thumbs.dreamstime.com/b/glowing-neon-padlock-futuristic-cyber-security-concept-digital-protection-privacy-illustration-364017358.jpg"
              alt="Sécurité des mots de passe"
              style="width:240px;height:150px;border-radius:16px;object-fit:cover;border:2px solid var(--neon-cyan);box-shadow:var(--glow-cyan);">
            <h5 class="mt-4 mb-3">Sécurité des mots de passe</h5>
            <p class="text-muted mb-4">Créer et gérer des mots de passe robustes pour une sécurité optimale.</p>
            <a href="./pages/passwords.html" class="btn btn-primary"><i class="bi bi-play-circle me-2"></i> Accéder au cours</a>
          </div>
        </div>
        <div class="col col-md-3">
          <div class="cyber-card text-center">
            <img src="https://www.shutterstock.com/image-vector/vector-illustration-futuristic-cybersecurity-breach-600nw-2544403121.jpg"
              alt="Ransomware & Malwares"
              style="width:240px;height:150px;border-radius:16px;object-fit:cover;border:2px solid var(--neon-cyan);box-shadow:var(--glow-cyan);">
            <h5 class="mt-4 mb-3">Ransomware & Malwares</h5>
            <p class="text-muted mb-4">Comprendre les logiciels malveillants et comment s'en protéger.</p>
            <a href="./pages/ransomware.html" class="btn btn-primary"><i class="bi bi-play-circle me-2"></i> Accéder au cours</a>
          </div>
        </div>
        <div class="col col-md-3">
          <div class="cyber-card text-center">
            <img src="https://images.unsplash.com/photo-1677442136019-21780ecad995?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80"
              alt="Cloud & Intelligence Artificielle"
              style="width:240px;height:150px;border-radius:16px;object-fit:cover;border:2px solid var(--neon-cyan);box-shadow:var(--glow-cyan);">
            <h5 class="mt-4 mb-3">Cloud & Intelligence Artificielle</h5>
            <p class="text-muted mb-4">Sécurité des données dans le cloud et enjeux de l'IA.</p><br>
            <a href="./pages/cloud.html" class="btn btn-primary"><i class="bi bi-play-circle me-2"></i> Accéder au cours</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="section-divider"></div>

  <!-- ══════════════════════ ABOUT ═════════════════════════════ -->
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
          <ul style="padding-left:2rem;list-style:none;">
            <li class="mb-3"><strong>Plateforme éducative</strong> – Contenus pratiques basés sur des menaces réelles</li>
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
              <div style="font-size:2rem;font-weight:700;color:var(--cyber-primary);margin-left:170px;">12+</div>
              <small class="text-muted" style="margin-left:170px;">Modules</small>
            </div>
            <div class="col">
              <div style="font-size:2rem;font-weight:700;color:var(--cyber-success);">60+</div>
              <small class="text-muted">Leçons</small>
            </div>
            <div class="col">
              <div style="font-size:2rem;font-weight:700;color:var(--cyber-warning);">8+</div>
              <small class="text-muted">Quiz & scénarios</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ══════════════════════ CONTACT ═══════════════════════════ -->
  <section id="contact" class="py-5 contact-section"
    style="background:linear-gradient(135deg,rgba(10,14,23,0.98) 0%,rgba(18,24,38,0.95) 100%);">
    <div class="container">
      <div class="contact-header text-center mb-5">
        <div class="contact-icon mx-auto"><i class="bi bi-headset"></i></div>
        <h2 class="display-5 mb-3"
          style="background:linear-gradient(135deg,#0d6efd 0%,#00d4ff 100%);-webkit-background-clip:text;background-clip:text;color:transparent;">
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
            <form id="contactForm">
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Nom complet <span class="required-star">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" placeholder="Votre nom" required>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Email <span class="required-star">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" placeholder="votre@email.com" required>
                  </div>
                </div>
              </div>
              <div class="mb-4">
                <label class="form-label">Sujet</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-tag"></i></span>
                  <select class="form-select">
                    <option selected>Sélectionnez un sujet</option>
                    <option>Question sur un module</option>
                    <option>Problème technique</option>
                    <option>Suggestion d'amélioration</option>
                    <option>Partenaire/Formation entreprise</option>
                    <option>Autre demande</option>
                  </select>
                </div>
              </div>
              <div class="mb-4">
                <label class="form-label">Message <span class="required-star">*</span></label>
                <textarea class="form-textarea" placeholder="Décrivez votre question ou demande en détail..." required></textarea>
              </div>
              <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="newsletter">
                <label class="form-check-label" for="newsletter">
                  Je souhaite recevoir les mises à jour et les nouveaux modules par email
                </label>
              </div>
              <div class="text-center">
                <button type="submit" class="submit-btn">
                  <i class="bi bi-send-check me-2"></i>Envoyer le message
                </button>
              </div>
            </form>
          </div>
        </div>
        <div class="col col-lg-5 text-center">
          <img src="assets/contact-bg.png" alt="Contact" style="width:550px;height:600px;border-radius:12px;">
        </div>
      </div>
    </div>
  </section>

  <!-- ══════════════════════ FOOTER ════════════════════════════ -->
  <footer class="cyber-footer">
    <div class="container">
      <div class="row">
        <div class="col col-lg-8" style="margin:0 auto;">
          <div class="d-flex justify-content-center mb-4" style="gap:1.5rem;">
            <div style="width:50px;height:50px;background:rgba(13,110,253,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-shield-lock" style="font-size:1.8rem;color:var(--cyber-primary);"></i>
            </div>
            <div style="width:50px;height:50px;background:rgba(32,201,151,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-lock" style="font-size:1.8rem;color:var(--cyber-success);"></i>
            </div>
          </div>
          <h3 class="text-center mb-3"><span style="color:var(--cyber-accent);">Secura</span></h3>
          <p class="text-center text-muted mb-4">Plateforme de sensibilisation à la cybersécurité</p>
          <div class="d-flex justify-content-center mb-4" style="gap:1.5rem;">
            <a href="#" style="color:var(--cyber-primary);"><i class="bi bi-github" style="font-size:1.8rem;"></i></a>
            <a href="#" style="color:var(--cyber-primary);"><i class="bi bi-linkedin" style="font-size:1.8rem;"></i></a>
            <a href="#" style="color:var(--cyber-primary);"><i class="bi bi-twitter-x" style="font-size:1.8rem;"></i></a>
            <a href="#" style="color:var(--cyber-primary);"><i class="bi bi-envelope" style="font-size:1.8rem;"></i></a>
          </div>
          <hr style="opacity:0.25;margin:2rem 0;">
          <p class="text-center text-muted small mb-0">© 2026 Secura - Plateforme de Sensibilisation à la Cybersécurité</p>
        </div>
      </div>
    </div>
  </footer>

  <!-- ══════════════════════ SCRIPTS ═══════════════════════════ -->
  <script>
    // ── Dropdown ─────────────────────────────────────────────
    const dropdownBtn  = document.getElementById('dropdownToggleBtn');
    const dropdownMenu = document.getElementById('userDropdownMenu');

    function toggleDropdown(e) {
      e.stopPropagation();
      const isOpen = dropdownMenu && dropdownMenu.classList.contains('show');
      closeDropdown();
      if (!isOpen) openDropdown();
    }

    function openDropdown() {
      if (!dropdownMenu) return;
      dropdownMenu.classList.add('show');
      dropdownBtn && dropdownBtn.classList.add('open');
      dropdownBtn && dropdownBtn.setAttribute('aria-expanded', 'true');
    }

    function closeDropdown() {
      if (!dropdownMenu) return;
      dropdownMenu.classList.remove('show');
      dropdownBtn && dropdownBtn.classList.remove('open');
      dropdownBtn && dropdownBtn.setAttribute('aria-expanded', 'false');
    }

    // Close on outside click
    document.addEventListener('click', function (e) {
      const wrapper = document.getElementById('userDropdownWrapper');
      if (wrapper && !wrapper.contains(e.target)) closeDropdown();
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeDropdown();
    });

    // ── Auto-hide logout toast ────────────────────────────────
    const logoutToast = document.getElementById('logoutToast');
    if (logoutToast) {
      setTimeout(() => {
        logoutToast.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        logoutToast.style.opacity    = '0';
        logoutToast.style.transform  = 'translateX(40px)';
        setTimeout(() => logoutToast.remove(), 500);
      }, 4500);
    }

    // ── Auto-hide login toast ─────────────────────────────────
    const loginToast = document.getElementById('loginToast');
    if (loginToast) {
      setTimeout(() => {
        loginToast.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        loginToast.style.opacity    = '0';
        loginToast.style.transform  = 'translateX(40px)';
        setTimeout(() => loginToast.remove(), 500);
      }, 4500);
    }

    // ── Mobile nav toggler ────────────────────────────────────
    const navToggler = document.getElementById('navToggler');
    const navMenu    = document.getElementById('navMenu');
    if (navToggler) {
      navToggler.addEventListener('click', () => navMenu.classList.toggle('active'));
    }
  </script>
</body>
</html>
