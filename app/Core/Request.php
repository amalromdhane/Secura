<?php
/**
 * HTTP Request Wrapper – Secura
 * Provides typed, null-safe accessors over PHP superglobals.
 */

namespace App\Core;

class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function isGet(): bool
    {
        return self::method() === 'GET';
    }

    public static function isPut(): bool
    {
        return self::method() === 'PUT';
    }

    public static function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * @return array<string,mixed>
     */
    public static function all(): array
    {
        return array_merge($_GET ?? [], $_POST ?? []);
    }

    /**
     * @return array<string,mixed>
     */
    public static function only(array $keys): array
    {
        $source = self::all();
        return array_intersect_key($source, array_flip($keys));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $source = self::all();
        return $source[$key] ?? $default;
    }

    /**
     * Decode JSON body on POST/PUT requests that send Content-Type: application/json.
     */
    public static function json(): ?array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    public static function jsonField(string $key, mixed $default = null): mixed
    {
        $data = self::json();
        return $data[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        $source = self::all();
        return array_key_exists($key, $source) && !empty($source[$key]);
    }

    /**
     * File upload helper.
     * @return array{name:string,type:string,tmp_name:string,error:int,size:int}|null
     */
    public static function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    /**
     * Returns the client IP address.
     */
    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Returns the request URI (path only, without query string).
     */
    public static function uri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        return rtrim($uri, '/') ?: '/';
    }

    /**
     * Full URL including query string.
     */
    public static function fullUrl(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Returns a single query / body value cast to int.
     */
    public static function int(string $key, int $default = 0): int
    {
        return (int) (self::get($key, $default));
    }

    /**
     * Returns a single query / body value cast to bool.
     */
    public static function bool(string $key): bool
    {
        $val = self::get($key);
        if (is_bool($val)) {
            return $val;
        }
        $val = strtolower((string) $val);
        return in_array($val, ['1', 'true', 'on', 'yes'], true);
    }
}
