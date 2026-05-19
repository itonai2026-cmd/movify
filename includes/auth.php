<?php
/**
 * Movify – Authentication Helpers
 */

require_once __DIR__ . '/../config.php';

// ── Guard: redirect unauthenticated users ───────────────────────────
function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . url('login.php'));
        exit;
    }
}

// ── Current user row from DB ────────────────────────────────────────
function current_user(PDO $pdo): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id, email, credits, is_verified, created_at FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

// ── Register ────────────────────────────────────────────────────────
function register_user(PDO $pdo, string $email, string $password): array
{
    $email = trim(strtolower($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid email address.'];
    }
    if (strlen($password) < 8) {
        return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
    }

    $exists = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $exists->execute([$email]);
    if ($exists->fetch()) {
        return ['ok' => false, 'error' => 'This email is already registered.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare(
        'INSERT INTO users (email, password_hash, verification_code) VALUES (?, ?, ?)'
    );
    $stmt->execute([$email, $hash, $code]);

    $userId = (int)$pdo->lastInsertId();

    send_verification_email($email, $code);

    return ['ok' => true, 'user_id' => $userId, 'email' => $email, 'code' => $code];
}

// ── Verify email code ───────────────────────────────────────────────
function verify_email(PDO $pdo, string $email, string $code): bool
{
    $stmt = $pdo->prepare(
        'SELECT id FROM users WHERE email = ? AND verification_code = ? AND is_verified = 0'
    );
    $stmt->execute([trim(strtolower($email)), $code]);
    $user = $stmt->fetch();

    if (!$user) {
        return false;
    }

    $upd = $pdo->prepare('UPDATE users SET is_verified = 1, verification_code = NULL WHERE id = ?');
    $upd->execute([$user['id']]);
    return true;
}

// ── Login ───────────────────────────────────────────────────────────
function login_user(PDO $pdo, string $email, string $password): array
{
    $email = trim(strtolower($email));

    $stmt = $pdo->prepare('SELECT id, password_hash, is_verified FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'error' => 'Incorrect email or password.'];
    }
    if (!$user['is_verified']) {
        return ['ok' => false, 'error' => 'Account not verified. Please check your email.', 'needs_verify' => true];
    }

    $_SESSION['user_id'] = $user['id'];
    session_regenerate_id(true);

    return ['ok' => true];
}

// ── Logout ──────────────────────────────────────────────────────────
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ── Forgot Password – create token ─────────────────────────────────
function create_password_reset(PDO $pdo, string $email): bool
{
    $email = trim(strtolower($email));

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        return true; // silent – don't reveal existence
    }

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $pdo->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
    $pdo->prepare(
        'INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)'
    )->execute([$email, $token, $expires]);

    $link = rtrim(APP_URL, '/') . url('reset_password.php') . '?token=' . $token;
    send_reset_email($email, $link);

    return true;
}

// ── Reset Password – apply new password ─────────────────────────────
function reset_password(PDO $pdo, string $token, string $newPassword): array
{
    if (strlen($newPassword) < 8) {
        return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
    }

    $stmt = $pdo->prepare(
        'SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()'
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if (!$row) {
        return ['ok' => false, 'error' => 'Invalid or expired link.'];
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?')
        ->execute([$hash, $row['email']]);
    $pdo->prepare('DELETE FROM password_resets WHERE email = ?')
        ->execute([$row['email']]);

    return ['ok' => true];
}

// ── Email Helpers ───────────────────────────────────────────────────
function send_verification_email(string $to, string $code): void
{
    $subject = APP_NAME . ' – Verification Code';
    $body    = "Your verification code is: <strong>{$code}</strong><br>Enter this code on the verification page.";
    send_mail($to, $subject, $body);
}

function send_reset_email(string $to, string $link): void
{
    $subject = APP_NAME . ' – Password Reset';
    $body    = "Click the link below to reset your password (valid for 15 minutes):<br><a href=\"{$link}\">{$link}</a>";
    send_mail($to, $subject, $body);
}

function send_mail(string $to, string $subject, string $htmlBody): void
{
    if (SMTP_USER === '' || SMTP_PASS === '') {
        error_log("SMTP not configured – would send to {$to}: {$subject}");
        error_log("Mail body: {$htmlBody}");
        return;
    }

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";

    @mail($to, $subject, $htmlBody, $headers);
}
