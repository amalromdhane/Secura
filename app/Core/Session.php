<?php
/**
 * Session Wrapper – Secura
 *
 * Centralises session_start(), flash messages and key-value storage
 * so the rest of the application never touches $_SESSION directly.
 */

namespace App\Core;

class Session
{
    private const FLASH_KEY = '_flash';
    private const AUTH_KEY  = '_auth';

    private static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function put(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flush(): void
    {
        self::start();
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        session_unset();
        session_destroy();
    }

    /* ── Auth helpers ─────────────────────────────── */

    public static function login(array $user): void
    {
        self::start();
        self::put(self::AUTH_KEY, $user);
    }

    public static function logout(): void
    {
        self::forget(self::AUTH_KEY);
    }

    public static function user(): ?array
    {
        return self::get(self::AUTH_KEY);
    }

    public static function isLoggedIn(): bool
    {
        return self::get(self::AUTH_KEY) !== null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user !== null && ($user['role'] ?? '') === 'admin';
    }

    /* ── Flash messages ───────────────────────────── */

    /**
     * @param string $message
     * @param 'success'|'error'|'warning'|'info' $type
     */
    public static function flash(string $message, string $type = 'info'): void
    {
        $flashes = self::get(self::FLASH_KEY, []);
        $flashes[] = compact('message', 'type');
        self::put(self::FLASH_KEY, $flashes);
    }

    /**
     * Return pending flash messages and clear them.
     * @return list<array{message:string,type:string}>
     */
    public static function getFlashes(): array
    {
        $flashes = self::get(self::FLASH_KEY);
        self::forget(self::FLASH_KEY);
        return is_array($flashes) ? $flashes : [];
    }

    public static function hasFlashes(): bool
    {
        self::start();
        return !empty($_SESSION[self::FLASH_KEY]);
    }
}
