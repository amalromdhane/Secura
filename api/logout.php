<?php
session_start();

// Destroy all session data
session_destroy();

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Redirect to home page with success message
header('Location: ../index.php?logout=success');
exit;
?>
