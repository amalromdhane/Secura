<?php
/**
 * Populate Ransomware Module Chapters
 * Converts static HTML content into database chapters
 */
require_once 'includes/config.php';

$pdo = getDBConnection('cyber');

// Find the Ransomware module
$stmt = $pdo->prepare("SELECT id FROM modules WHERE title LIKE '%Ransomware%'");
$stmt->execute();
$module = $stmt->fetch();

if (!$module) {
    die("Ransomware module not found. Please create it first in admin dashboard.\n");
}

$module_id = $module['id'];

echo "Found module ID: $module_id\n";

// Clear existing chapters
$pdo->prepare("DELETE FROM course_chapters WHERE module_id = ?")->execute([$module_id]);

// Chapter 1: Introduction
$chapter1_content = '<p>Les malwares (logiciels malveillants) sont des programmes conçus pour infiltrer, endommager ou prendre le contrôle de systèmes informatiques sans le consentement de l\'utilisateur. Les ransomwares sont une catégorie particulièrement dangereuse qui chiffre vos fichiers et exige une rançon pour les déverrouiller.</p>

<div class="row mt-4">
  <div class="col-md-6">
    <div class="warning-box">
      <h6 class="text-danger mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Statistiques alarmantes</h6>
      <ul class="mb-0 small">
        <li class="mb-2">Coût mondial estimé : +57 milliards $ par an</li>
        <li class="mb-2">Une entreprise est attaquée toutes les 11 secondes</li>
        <li class="mb-2">75% des intrusions système liées aux ransomwares</li>
        <li>Ne jamais payer : cela finance le crime organisé</li>
      </ul>
    </div>
  </div>
  <div class="col-md-6">
    <div class="tip-box">
      <h6 class="text-success mb-3"><i class="fas fa-check-circle me-2"></i>Protection essentielle</h6>
      <ul class="mb-0 small">
        <li class="mb-2">Sauvegardes régulières et externalisées</li>
        <li class="mb-2">Mises à jour système et logiciels</li>
        <li class="mb-2">Antivirus/anti-malware à jour</li>
        <li>Formation des utilisateurs à la vigilance</li>
      </ul>
    </div>
  </div>
</div>';

$stmt = $pdo->prepare("INSERT INTO course_chapters (module_id, title, content, video_url, order_index) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$module_id, 'Introduction', $chapter1_content, '', 1]);

// Chapter 2: Types de malwares
$chapter2_content = '<h4 class="mt-5 mb-3 text-cyan">Les principales familles de malwares</h4>
<div class="row">
  <div class="col-md-6">
    <div class="content-box">
      <h6 class="text-danger">Ransomware</h6>
      <p class="small mb-2">Chiffre les fichiers et demande une rançon pour les déverrouiller.</p>
      <span class="badge badge-danger small">Ex: WannaCry, LockBit</span>
    </div>
  </div>
  <div class="col-md-6">
    <div class="content-box">
      <h6 class="text-warning">Spyware</h6>
      <p class="small mb-2">Espionne les activités, vole mots de passe et données personnelles.</p>
      <span class="badge badge-warning small">Ex: keyloggers, stalkerware</span>
    </div>
  </div>
  <div class="col-md-6">
    <div class="content-box">
      <h6 class="text-info">Cheval de Troie</h6>
      <p class="small mb-2">Se fait passer pour un logiciel légitime pour infiltrer le système.</p>
      <span class="badge badge-info small">Ex: Emotet, Zeus</span>
    </div>
  </div>
  <div class="col-md-6">
    <div class="content-box">
      <h6 class="text-primary">Virus/Vers</h6>
      <p class="small mb-2">Se réplique et se propage à d\'autres systèmes, causant des dommages.</p>
      <span class="badge badge-primary small">Ex: ILOVEYOU, Conficker</span>
    </div>
  </div>
</div>

<h4 class="mt-5 mb-3 text-cyan">Comment les malwares se propagent</h4>
<div class="row">
  <div class="col-md-6">
    <div class="content-box">
      <h6><i class="fas fa-envelope-exclamation text-cyan me-2"></i>Pièces jointes email</h6>
      <p class="small mb-0">Fichiers .exe, .zip, documents Office avec macros malveillantes</p>
    </div>
  </div>
  <div class="col-md-6">
    <div class="content-box">
      <h6><i class="fas fa-link text-cyan me-2"></i>Liens frauduleux</h6>
      <p class="small mb-0">URLs malicieuses dans emails, SMS ou réseaux sociaux</p>
    </div>
  </div>
  <div class="col-md-6">
    <div class="content-box">
      <h6><i class="fas fa-download text-cyan me-2"></i>Téléchargements</h6>
      <p class="small mb-0">Logiciels piratés, extensions navigateur, applications douteuses</p>
    </div>
  </div>
  <div class="col-md-6">
    <div class="content-box">
      <h6><i class="fas fa-usb text-cyan me-2"></i>Supports externes</h6>
      <p class="small mb-0">Clés USB, disques durs externes infectés</p>
    </div>
  </div>
</div>';

$stmt->execute([$module_id, 'Leçon 1 : Types de malwares', $chapter2_content, 'https://www.youtube-nocookie.com/embed/G_lFrgwjw9E', 2]);

// Chapter 3: Protection et prévention
$chapter3_content = '<h4 class="mt-5 mb-3 text-cyan">Stratégie de défense en profondeur</h4>

<div class="content-box">
  <h5 class="text-cyan"><i class="fas fa-cloud-arrow-up me-2"></i>Règle 3-2-1 des sauvegardes</h5>
  <p>La meilleure défense contre les ransomwares :</p>
  <div class="row mt-3 text-center">
    <div class="col-md-4">
      <div class="p-3">
        <h1 class="text-cyan">3</h1>
        <p class="small mb-0">Copies de données</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="p-3">
        <h1 class="text-cyan">2</h1>
        <p class="small mb-0">Supports différents (cloud + externe)</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="p-3">
        <h1 class="text-cyan">1</h1>
        <p class="small mb-0">Copie hors ligne (air-gapped)</p>
      </div>
    </div>
  </div>
</div>

<div class="row mt-4">
  <div class="col-md-6">
    <div class="content-box">
      <h6><i class="fas fa-patch-check text-success me-2"></i>Mises à jour</h6>
      <ul class="small mb-0">
        <li>Système d\'exploitation</li>
        <li>Logiciels et applications</li>
        <li>Antivirus et pare-feu</li>
        <li>Firmware des périphériques</li>
      </ul>
    </div>
  </div>
  <div class="col-md-6">
    <div class="content-box">
      <h6><i class="fas fa-user-shield text-primary me-2"></i>Formation utilisateur</h6>
      <ul class="small mb-0">
        <li>Reconnaître les emails suspects</li>
        <li>Vérifier les expéditeurs</li>
        <li>Ne pas cliquer sur liens douteux</li>
        <li>Signaler les incidents</li>
      </ul>
    </div>
  </div>
</div>

<h4 class="mt-5 mb-3 text-cyan">Outils de protection recommandés</h4>
<table class="comparison-table">
  <thead>
    <tr>
      <th>Type d\'outil</th>
      <th>Exemples</th>
      <th>Fonction principale</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>Antivirus/Anti-malware</strong></td>
      <td>Malwarebytes, Bitdefender, Windows Defender</td>
      <td>Détection et suppression des menaces connues</td>
    </tr>
    <tr>
      <td><strong>Pare-feu</strong></td>
      <td>Windows Firewall, pfSense, ZoneAlarm</td>
      <td>Contrôle du trafic réseau entrant/sortant</td>
    </tr>
    <tr>
      <td><strong>Outils de sauvegarde</strong></td>
      <td>Acronis, Veeam, Backup Exec</td>
      <td>Sauvegarde automatique et restauration</td>
    </tr>
    <tr>
      <td><strong>Protection email</strong></td>
      <td>Proofpoint, Mimecast, SpamTitan</td>
      <td>Filtrage des emails malveillants</td>
    </tr>
  </tbody>
</table>';

$stmt->execute([$module_id, 'Leçon 2 : Protection et prévention', $chapter3_content, 'https://www.youtube-nocookie.com/embed/LUGyTKmv5SY', 3]);

// Chapter 4: Réponse aux incidents
$chapter4_content = '<div class="content-box">
  <h5 class="text-cyan">Procédure d\'urgence en cas d\'attaque</h5>
  <div class="row mt-3">
    <div class="col-md-4 text-center p-3">
      <div class="mb-3">
        <i class="fas fa-power-off fs-1 text-danger"></i>
      </div>
      <h6>1. Isoler</h6>
      <p class="small">Déconnecter l\'appareil du réseau (Wi-Fi, Ethernet)</p>
    </div>
    <div class="col-md-4 text-center p-3">
      <div class="mb-3">
        <i class="fas fa-exclamation-triangle fs-1 text-warning"></i>
      </div>
      <h6>2. Signaler</h6>
      <p class="small">Alerter votre service informatique ou une autorité compétente</p>
    </div>
    <div class="col-md-4 text-center p-3">
      <div class="mb-3">
        <i class="fas fa-cloud-arrow-down fs-1 text-success"></i>
      </div>
      <h6>3. Restaurer</h6>
      <p class="small">Utiliser vos sauvegardes pour restaurer les systèmes</p>
    </div>
  </div>
</div>

<h4 class="mt-5 mb-3 text-cyan">Ne jamais payer la rançon</h4>
<div class="warning-box">
  <h6 class="text-danger"><i class="fas fa-times-circle me-2"></i>Pourquoi ne pas payer ?</h6>
  <ul class="mb-0 small">
    <li class="mb-2">Aucune garantie de récupération des fichiers</li>
    <li class="mb-2">Financement du crime organisé</li>
    <li class="mb-2">Identification comme cible facile pour de futures attaques</li>
    <li>Possibilité de réinfection</li>
  </ul>
</div>

<h4 class="mt-5 mb-3 text-cyan">Organismes à contacter en France</h4>
<div class="row">
  <div class="col-md-6">
    <div class="content-box">
      <h6><i class="fas fa-shield-check text-primary me-2"></i>ANSSI</h6>
      <p class="small mb-3">Agence Nationale de la Sécurité des Systèmes d\'Information</p>
      <a href="https://www.ssi.gouv.fr" class="small text-decoration-none text-info">www.ssi.gouv.fr</a>
    </div>
  </div>
  <div class="col-md-6">
    <div class="content-box">
      <h6><i class="fas fa-phone text-success me-2"></i>Cybermalveillance.gouv.fr</h6>
      <p class="small mb-3">Plateforme d\'assistance aux victimes</p>
      <a href="https://www.cybermalveillance.gouv.fr" class="small text-decoration-none text-info">www.cybermalveillance.gouv.fr</a>
    </div>
  </div>
</div>

<div class="tip-box mt-4">
  <h6 class="text-success"><i class="fas fa-lightbulb me-2"></i>Conseil pratique</h6>
  <p class="mb-0">Testez régulièrement votre plan de reprise après sinistre (PRA) pour vous assurer que vos sauvegardes sont fonctionnelles et que votre équipe sait comment restaurer les systèmes.</p>
</div>';

$stmt->execute([$module_id, 'Leçon 3 : Réponse aux incidents', $chapter4_content, '', 4]);

echo "Chapters populated successfully for Ransomware module!\n";
?>