<?php
/**
 * Authentification visiteur / utilisateur (hors zone admin).
 */

function auth_is_logged_in(): bool
{
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

/**
 * Valide une URL de redirection interne (évite open redirect).
 */
function auth_safe_redirect(string $url): ?string
{
    $url = trim($url);
    if ($url === '' || preg_match('#^https?://#i', $url) || strpos($url, '..') !== false) {
        return null;
    }

    $allowedPrefixes = [
        'index.php',
        'all_modules.php',
        'module.php',
        'quiz.php',
        'access_module.php',
        'pages/',
        'profil.php',
    ];

    foreach ($allowedPrefixes as $prefix) {
        if (strpos($url, $prefix) === 0) {
            return $url;
        }
    }

    return null;
}

/**
 * Redirige vers login si non connecté.
 */
function auth_require_login(?string $redirectTarget = null): void
{
    if (auth_is_logged_in()) {
        return;
    }

    if ($redirectTarget === null) {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if (preg_match('#/Secura/(.+)$#i', $script, $m)) {
            $redirectTarget = $m[1];
        } else {
            $redirectTarget = basename($script);
        }
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        if ($qs !== '') {
            $redirectTarget .= '?' . $qs;
        }
    }

    $loginUrl = 'login.php';
    $safe     = $redirectTarget ? auth_safe_redirect($redirectTarget) : null;
    if ($safe) {
        $loginUrl .= '?redirect=' . rawurlencode($safe);
    }

    header('Location: ' . $loginUrl);
    exit();
}

/**
 * URL cible d'un module (sans contrôle de session).
 */
function module_target_url(array $m): string
{
    if (!empty($m['page'])) {
        $page = trim($m['page']);
        if (preg_match('#^https?://#i', $page)) {
            return $page;
        }
        if (strpos($page, 'pages/') === 0 || strpos($page, './pages/') === 0) {
            $path = ltrim(str_replace('./', '', $page), '/');
            return 'access_module.php?to=' . rawurlencode($path);
        }
        return 'access_module.php?to=' . rawurlencode('pages/' . ltrim($page, './'));
    }

    return 'module.php?id=' . (int)($m['id'] ?? 0);
}

/**
 * Lien « accéder » : direct si connecté, sinon login avec retour.
 */
function module_access_href(array $m, bool $loggedIn): string
{
    $target = module_target_url($m);
    if ($loggedIn) {
        return $target;
    }
    if (preg_match('#^https?://#i', $target)) {
        return 'login.php?redirect=' . rawurlencode('all_modules.php');
    }
    return 'login.php?redirect=' . rawurlencode($target);
}
