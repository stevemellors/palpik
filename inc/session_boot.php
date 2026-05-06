<?php
// Session cookie pinned to your public site
$sessionParams = [
  'lifetime' => 0,
  'path'     => '/',
  'domain'   => '.palletpiks.com',   // works for www.palletpiks.com and palletpiks.com
  'secure'   => !empty($_SERVER['HTTPS']) || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
  'httponly' => true,
  'samesite' => 'Lax',
];
if (PHP_VERSION_ID >= 70300) {
  session_set_cookie_params($sessionParams);
} else {
  session_set_cookie_params(
    $sessionParams['lifetime'],
    $sessionParams['path'].'; samesite='.$sessionParams['samesite'],
    $sessionParams['domain'],
    $sessionParams['secure'],
    $sessionParams['httponly']
  );
}
if (session_status() === PHP_SESSION_NONE) { session_start(); }
