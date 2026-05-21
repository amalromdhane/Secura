<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/request.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

$in = getRequestData();

$full_name  = trim($in['full_name'] ?? '');
$email      = trim($in['email'] ?? '');
$subject    = trim($in['subject'] ?? '');
$message    = trim($in['message'] ?? '');
$newsletter = !empty($in['newsletter']) ? 1 : 0;

$allowed_subjects = [
    'Question sur un module',
    'Problème technique',
    'Suggestion d\'amélioration',
    'Partenaire/Formation entreprise',
    'Autre demande',
];

if ($full_name === '' || $email === '' || $message === '') {
    echo json_encode(['success' => false, 'error' => 'Veuillez remplir tous les champs obligatoires.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Adresse email invalide.']);
    exit();
}

if ($subject === '' || !in_array($subject, $allowed_subjects, true)) {
    echo json_encode(['success' => false, 'error' => 'Veuillez sélectionner un sujet valide.']);
    exit();
}

if (strlen($message) < 10) {
    echo json_encode(['success' => false, 'error' => 'Le message doit contenir au moins 10 caractères.']);
    exit();
}

try {
    $pdo = getDBConnection('cyber');

    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(120) NOT NULL,
        message TEXT NOT NULL,
        newsletter TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $pdo->prepare(
        'INSERT INTO contact_messages (full_name, email, subject, message, newsletter) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$full_name, $email, $subject, $message, $newsletter]);

    echo json_encode([
        'success' => true,
        'message' => 'Merci ! Votre message a bien été envoyé. Nous vous répondrons rapidement.',
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'enregistrement du message.']);
}
