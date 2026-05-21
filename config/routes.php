<?php
/**
 * Route Definitions – Secura
 *
 * Every URL the application serves is declared here.
 * Use this file as the single source of truth when adding or
 * removing a page.
 *
 * Three protection levels:
 *   (none)   – publicly accessible
 *   ['auth'] – requires the user to be logged in
 *   ['admin']- requires the admin role
 *
 * Naming convention:
 *   GET  /login.php                 → login_get
 *   POST /login.php                 → login_post
 *   GET  /module.php?id=:id         → module_show
 *
 * Usage (access anywhere):
 *   route('admin_dashboard')        → /admin_dashboard.php
 *   route('module_show', ['id'=>3]) → /module.php?id=3
 */

use App\Core\Router;

/** @var Router $router */

// ═══════════════════════════════════════════════════════════════════════
//  ALL ROUTES — single source of truth
//  Convention: $router->method('/path.php', 'Controller@method', [middleware])
// ═══════════════════════════════════════════════════════════════════════

// ─── PUBLIC ─────────────────────────────────────────────────────────────

$router->get('/',              'index@home',          []);
$router->get('/index.php',     'index@home',          []);

$router->get('/login.php',     'auth@loginForm',      []);
$router->post('/login.php',    'auth@loginSubmit',    []);

$router->get('/register.php',  'auth@registerForm',   []);
$router->post('/register.php', 'auth@registerSubmit', []);

$router->get('/logout',        'auth@logout',         []);

// ─── AUTHENTICATED (require login) ──────────────────────────────────────

$router->get('/profil.php',            'user@profile',       ['auth']);
$router->post('/profil.php',           'user@updateProfile', ['auth']);
$router->post('/api/upload-avatar',    'user@uploadAvatar',  ['auth']);

$router->get('/all_modules.php',       'module@listPublic',  ['auth']);
$router->get('/module.php',            'module@show',        ['auth']);

$router->get('/quiz.php',              'quiz@form',          ['auth']);
$router->post('/quiz.php',             'quiz@submit',        ['auth']);

// ─── ADMIN ONLY ─────────────────────────────────────────────────────────

$router->get('/admin_dashboard.php',    'admin@dashboard',    ['auth', 'admin']);
$router->get('/admin_users.php',        'admin@users',        ['auth', 'admin']);
$router->get('/admin_course.php',       'admin@course',       ['auth', 'admin']);
$router->get('/admin_quiz.php',         'admin@quiz',         ['auth', 'admin']);
$router->get('/manage_content.php',     'admin@content',      ['auth', 'admin']);

$router->post('/admin_course.php',      'admin@saveCourse',   ['auth', 'admin']);
$router->post('/admin_quiz.php',        'admin@saveQuiz',     ['auth', 'admin']);

// Module CRUD API (used by admin_dashboard.js)
$router->get('/module.php?action=list',    'module@apiList',    ['auth', 'admin']);
$router->post('/module.php?action=add',    'module@apiCreate',  ['auth', 'admin']);
$router->post('/module.php?action=update', 'module@apiUpdate',  ['auth', 'admin']);
$router->post('/module.php?action=delete', 'module@apiDelete',  ['auth', 'admin']);

// ─── STATIC / PEDAGOGICAL PAGES ─────────────────────────────────────────

$router->get('/pages/phishing.html',     'content@page', ['page' => 'phishing.html']);
$router->get('/pages/passwords.html',    'content@page', ['page' => 'passwords.html']);
$router->get('/pages/ransomware.html',   'content@page', ['page' => 'ransomware.html']);
$router->get('/pages/cloud.html',        'content@page', ['page' => 'cloud.html']);
$router->get('/pages/PhishingQuiz.html',    'content@page', ['page' => 'PhishingQuiz.html']);
$router->get('/pages/PasswordQuiz.html',    'content@page', ['page' => 'PasswordQuiz.html']);
$router->get('/pages/RansomeQuiz.html',     'content@page', ['page' => 'RansomeQuiz.html']);
$router->get('/pages/CloudIAQuiz.html',     'content@page', ['page' => 'CloudIAQuiz.html']);

// ─── NOT FOUND ──────────────────────────────────────────────────────────

$router->notFound(function () {
    http_response_code(404);
    echo '<h1 style="padding:3rem;text-align:center;color:#888;">404 – Page introuvable</h1>';
});
