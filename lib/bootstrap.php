<?php

declare(strict_types=1);

session_start();

spl_autoload_register(static function (string $class): void {
    $path = __DIR__ . '/' . $class . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$config = require __DIR__ . '/../config.php';
date_default_timezone_set($config['app']['timezone'] ?? 'Europe/Warsaw');

final class App
{
    private static ?array $config = null;
    private static ?Database $database = null;

    public static function config(?string $section = null, ?string $key = null, mixed $default = null): mixed
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../config.php';
        }

        if ($section === null) {
            return self::$config;
        }

        if ($key === null) {
            return self::$config[$section] ?? $default;
        }

        return self::$config[$section][$key] ?? $default;
    }

    public static function db(): Database
    {
        if (!self::$database instanceof Database) {
            self::$database = new Database(self::config('db'));
        }

        return self::$database;
    }
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_response(['ok' => false, 'error' => 'Niepoprawny JSON.'], 400);
    }

    return $data;
}

function auth(): AuthService
{
    return new AuthService(App::db());
}

function current_user(): ?array
{
    if ((int) ($_SESSION['user_id'] ?? 0) <= 0) {
        return null;
    }

    return auth()->user();
}

function require_user(): array
{
    $user = current_user();
    if ($user === null) {
        if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/')) {
            json_response(['ok' => false, 'error' => 'Musisz się zalogować.'], 401);
        }
        redirect('login.php');
    }

    return $user;
}
