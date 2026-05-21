# Organisation du projet Secura

## Dossiers

| Dossier | Contenu |
|---------|---------|
| `css/` | Fichiers CSS (`style.css`, `cyberaware.css`, `quizStyle.css`, …) |
| `js/` | Scripts JavaScript (point-virgule, `JSON.stringify`, `addEventListener`) |
| `pages/` | Pages HTML statiques (modules, quiz) |
| `includes/` | `config.php`, `connexion.php`, `request.php` |
| `traitement/` | Traitements PHP (`login_traitement.php`, `register_traitement.php`) |
| `assets/` | Images et médias (inchangé) |
| Racine `*.php` | Pages PHP + HTML (vues principales) |

## Conventions

- **Config** : `includes/config.php` — variables `$servername`, `$dbname`, …
- **PDO** : `includes/connexion.php` ou `getDBConnection()` dans config
- **Formulaires** : POST vers `traitement/*_traitement.php`
- **API fetch** : `Content-Type: application/json` + `JSON.stringify(payload)`
- **Événements** : délégation `addEventListener`, pas d’attributs `onclick`

## Layout admin partagé

Toutes les pages admin incluent :

```php
require_once __DIR__ . '/includes/admin/auth.php';
$page_title = '…';
$active_menu = 'dashboard'; // dashboard | modules | settings | users
$extra_css = ['css/admin-users-page.css']; // optionnel
require_once __DIR__ . '/includes/admin/layout_start.php';
// contenu de la page uniquement
require_once __DIR__ . '/includes/admin/layout_end.php';
// modales hors du container
$extra_js = ['js/admin-users.js'];
require_once __DIR__ . '/includes/admin/layout_footer.php';
```

- `includes/admin/auth.php` — vérification session admin
- `includes/admin/layout_start.php` — sidebar + navbar + ouverture container
- `includes/admin/layout_end.php` — fermeture container
- `includes/admin/layout_footer.php` — scripts communs
- `css/admin-layout.css` — styles communs du dashboard
- `js/admin-layout.js` — menu utilisateur (dropdown)

## Fichiers JS

- `js/auth.js` — bascule mot de passe (login / register)
- `js/modules.js` — catalogue modules (`all_modules.php`)
- `js/admin-users.js` — gestion utilisateurs
- `js/admin-quiz.js` — gestion quiz admin
- `js/admin-course.js` — éditeur de chapitres
