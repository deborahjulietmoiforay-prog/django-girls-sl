<?php
require_once __DIR__ . '/config.php';

// Clear all session data, expire the cookie, destroy the session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

session_start();            // fresh session only to carry the confirmation message
flash('You have been logged out.');
header('Location: login.php');
exit;
