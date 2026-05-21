<?php
session_start();

require_once __DIR__ . '/includes/auth.php';

$to = trim($_GET['to'] ?? '');
if ($to === '' || !preg_match('#^pages/[a-zA-Z0-9_\-]+\.html$#', $to)) {
    header('Location: index.php');
    exit();
}

auth_require_login('access_module.php?to=' . rawurlencode($to));

$path = __DIR__ . '/' . $to;
if (!is_file($path)) {
    header('Location: index.php');
    exit();
}

header('Content-Type: text/html; charset=utf-8');
readfile($path);
exit();
