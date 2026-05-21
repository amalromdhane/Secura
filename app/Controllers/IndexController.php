<?php
namespace App\Controllers;

use App\Core\Database;

class IndexController
{
    public function home(): void
    {
        session_start();

        $isLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
        $userRole   = $_SESSION['user_role']   ?? '';

        require __DIR__ . '/../public/views/index.php';
    }
}
