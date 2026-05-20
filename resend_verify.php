<?php
/**
 * Movify – Resend Verification Code
 *
 * AJAX endpoint for resending verification code.
 * Rate limited to 1 resend per minute per session.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

$email = $_SESSION['verify_email'] ?? '';

if (!$email) {
    json_response(['ok' => false, 'error' => 'No email in session.'], 400);
}

if (!is_post()) {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

// ── Rate limiting (max 1 resend per 60 seconds) ─────────────────────
$lastResend = $_SESSION['last_resend_time'] ?? 0;
$now = time();

if (($now - $lastResend) < 60) {
    $wait = 60 - ($now - $lastResend);
    json_response(['ok' => false, 'error' => "Please wait {$wait} seconds before retrying."], 429);
}

// ── Generate new verification code ──────────────────────────────────
$code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// ── Update DB with new code ────────────────────────────────────────
$stmt = $pdo->prepare('UPDATE users SET verification_code = ? WHERE email = ? AND is_verified = 0');
$stmt->execute([$code, trim(strtolower($email))]);

if ($stmt->rowCount() === 0) {
    json_response(['ok' => false, 'error' => 'Email not found or already verified.'], 404);
}

// ── Send new code via email ────────────────────────────────────────
send_verification_email($email, $code);

// ── Update rate limit in session ───────────────────────────────────
$_SESSION['last_resend_time'] = $now;
$_SESSION['verify_code_hint'] = $code; // For dev mode display

json_response(['ok' => true, 'message' => 'Verification code sent!']);
