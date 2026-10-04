<?php
require_once __DIR__ . '/session_setup.php';
session_start();
require_once __DIR__ . '/config.php';

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect_to(string $path): never
{
    header("Location: {$path}");
    exit();
}

function require_login(): void
{
    if (!isset($_SESSION['admin_id'])) {
        redirect_to('Login.php');
    }
}

function philippine_time(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
}
?>
