<?php
/**
 * HTTP Router – Secura
 *
 * Minimal dependency-free router.  Supports:
 *   – named routes with parameters  : /module/:id
 *   – GET / POST / PUT / DELETE verbs
 *   – middleware stacks (auth, admin)
 *   – fallback "not found" handler
 */

namespace App\Core;

class Router
{
    private array $routes   = [];
    private string $notFound;
    private string $basePath = '';

    public function setBasePath(string $path): void
    {
        $this->basePath = rtrim($path, '/');
    }

    /**
     * Return URI relative to basePath.
     */
    private function relativeUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        if ($this->basePath !== '' && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath));
        }
        return rtrim($uri, '/') ?: '/';
    }

    /**
     * Compile a route pattern such as /module/:id into a PCRE regex.
     *
     * @return array{regex:string, paramNames:string[]}
     */
    private function compile(string $pattern): array
    {
        $regex     = preg_replace('/\/:([\w-]+)/', '/(?P<\1>[^/]+)', $pattern);
        $regex     = '#^' . rtrim($regex, '/') . '/?$#';
        preg_match_all('/\(\?P<(\w+)>/', $regex, $matches);
        $paramNames = array_values($matches[1] ?? []);
        return compact('regex', 'paramNames');
    }

    /**
     * Extract route parameters from a URI according to a compiled route.
     */
    private function matchParameters(string $uri, array $paramNames): array
    {
        $params = [];
        foreach ($paramNames as $name) {
            if (preg_match('#/' . $name . '/([^/]+)#', $uri, $m)) {
                $params[$name] = rawurldecode($m[1]);
            }
        }
        return $params;
    }

    /**
     * Register a route.
     *
     * @param callable|string $handler   Closures receive (Request $req, array $params).
     *                                   Strings such as 'ModuleController@index' are resolved
     *                                   against the App\Controllers namespace.
     */
    public function add(string $method, string $pattern, string|callable $handler, array $middleware = []): self
    {
        $this->routes[] = compact('method', 'pattern', 'handler', 'middleware');
        return $this;
    }

    public function get(string $pattern, string|callable $handler, array $middleware = []): self
    {
        return $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, string|callable $handler, array $middleware = []): self
    {
        return $this->add('POST', $pattern, $handler, $middleware);
    }

    public function put(string $pattern, string|callable $handler, array $middleware = []): self
    {
        return $this->add('PUT', $pattern, $handler, $middleware);
    }

    public function delete(string $pattern, string|callable $handler, array $middleware = []): self
    {
        return $this->add('DELETE', $pattern, $handler, $middleware);
    }

    /**
     * Set the 404 handler.
     */
    public function notFound(callable $handler): self
    {
        $this->notFound = $handler;
        return $this;
    }

    /* ─── Dispatch ─────────────────────────────────────────────────────────────────── */

    /**
     * Resolve a string controller reference to a callable.
     *
     * @throws \Throwable
     */
    private function resolveHandler(string|callable $handler): callable
    {
        if (is_callable($handler)) {
            return $handler;
        }

        if (str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $fqcn = 'App\\Controllers\\' . $class;
            if (class_exists($fqcn)) {
                return [$fqcn, $method];
            }
        }

        return fn () => http_response_code(500);
    }

    /**
     * Run middleware stack; stop early if any middleware does not call $next().
     */
    private function applyMiddleware(array $middleware, array $context): bool
    {
        foreach ($middleware as $mw) {
            if (is_string($mw) && strlen($mw) > 0) {
                if ($mw === 'auth' && !Session::isLoggedIn()) {
                    Session::flash('Veuillez vous connecter.', 'warning');
                    header('Location: /login.php');
                    exit;
                }
                if ($mw === 'admin' && !Session::isAdmin()) {
                    http_response_code(403);
                    echo '<p style="padding:2rem;text-align:center;color:#888;">Accès interdit.</p>';
                    exit;
                }
            } elseif (is_callable($mw)) {
                $mw($context);
            }
        }
        return true;
    }

    public function dispatch(): void
    {
        $request = new \App\Core\Request();
        $uri     = $request->uri();
        $method  = $request->method();

        // static file check first (let the web server handle it)
        if ($method === 'GET' && $uri !== '/' && preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|pdf)$/i', $uri)) {
            return;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $compiled = $this->compile($route['pattern']);
            if (!preg_match($compiled['regex'], $uri)) {
                continue;
            }

            $params = $this->matchParameters($uri, $compiled['paramNames']);

            if (!$this->applyMiddleware($route['middleware'], compact('request', 'params'))) {
                return;
            }

            $handler = $this->resolveHandler($route['handler']);
            if ($handler($request, $params) === false) {
                return;
            }
            return;
        }

        if (isset($this->notFound) && is_callable($this->notFound)) {
            ($this->notFound)(new Request());
        } else {
            http_response_code(404);
            echo '<p style="padding:2rem;text-align:center;color:#aaa;">404 – Page introuvable.</p>';
        }
    }
}
