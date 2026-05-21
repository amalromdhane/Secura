<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;

class UserController
{
    public function profile(): void
    {
        require __DIR__ . '/../public/profil.php';
    }

    public function updateProfile(): void
    {
        // POST → keep existing logic in profil.php for now
        require __DIR__ . '/../public/profil.php';
    }

    public function uploadAvatar(): void
    {
        // POST upload avatar
        require __DIR__ . '/../public/profil.php';
    }
}
