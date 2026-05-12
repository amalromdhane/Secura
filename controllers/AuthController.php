<?php
require_once 'includes/config.php';
require_once 'models/User.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $pdo = getDBConnection();
        $this->userModel = new User($pdo);
    }

    public function login($email, $password) {
        $user = $this->userModel->authenticate($email, $password);
        if ($user) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_avatar'] = $user['avatar'];
            return true;
        }
        return false;
    }

    public function register($email, $password, $role = 'user') {
        return $this->userModel->register($email, $password, $role);
    }

    public function logout() {
        session_destroy();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'];
    }

    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }
}
?>