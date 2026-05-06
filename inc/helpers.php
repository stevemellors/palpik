<?php
declare(strict_types=1);
require_once __DIR__.'/session_boot.php';

// Escape HTML output
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Redirect to a URL
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// Format currency
function money(float $amount): string {
    return '$' . number_format($amount, 2);
}

// CSRF: return the current session token, generating one if needed
function csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF: render a hidden input for use inside forms
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

// CSRF: verify the submitted token; terminates with 403 on mismatch
function csrf_verify(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(403);
        exit('Invalid or missing CSRF token.');
    }
}
