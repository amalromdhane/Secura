<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

$admin_username = $_SESSION['username'] ?? 'Admin';
$admin_email    = $_SESSION['user_email'] ?? '';
$admin_avatar   = !empty($_SESSION['user_avatar'])
    ? $_SESSION['user_avatar']
    : 'assets/images/default-avatar.svg';

function admin_menu_active(string $key, string $current): string
{
    return $key === $current ? ' active' : '';
}
