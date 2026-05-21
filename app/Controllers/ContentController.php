<?php
namespace App\Controllers;

class ContentController
{
    /**
     * Serve a static pedagogical page from /pages/.
     */
    public function page(array $params): void
    {
        $page = basename($params['page'] ?? 'index.html');
        $path = __DIR__ . '/../../pages/' . $page;

        if (!is_file($path)) {
            http_response_code(404);
            echo '<h1 style="padding:3rem;text-align:center;color:#888;">404 – Page introuvable</h1>';
            return;
        }

        // Pass through: serve the HTML file directly
        include $path;
    }
}
