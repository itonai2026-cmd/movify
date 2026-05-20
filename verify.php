<?php
/**
 * Movify – Email Verification
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$email = $_SESSION['verify_email'] ?? '';
if (!$email) {
    redirect('register.php');
}

$error   = '';
$success = '';

if (is_post()) {
    if (!verify_csrf(post('csrf_token'))) {
        $error = 'Invalid request.';
    } else {
        $code = post('code');
        if (verify_email($pdo, $email, $code)) {
            unset($_SESSION['verify_email']);
            flash('success', 'Account verified! You can now sign in.');
            redirect('login.php');
        }
        $error = 'Incorrect code. Please check and try again.';
    }
}

$pageTitle = 'Email Verification';
require_once __DIR__ . '/includes/header.php';
?>

<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-md bg-dark-800 rounded-2xl p-8 shadow-xl border border-gray-700">
        <h2 class="text-2xl font-bold text-center mb-2">Email Verification</h2>
        <p class="text-gray-400 text-center text-sm mb-6">
            We sent a 6-digit code to <span class="text-primary-400"><?= h($email) ?></span>
        </p>

        <?php if (!empty($_SESSION['verify_code_hint']) && (SMTP_USER === '' || SMTP_PASS === '')): ?>
            <div class="mb-4 p-3 rounded-lg bg-yellow-900/40 border border-yellow-600 text-yellow-300 text-sm text-center">
                <strong>Dev mode</strong> – SMTP not configured.<br>
                Your verification code: <span class="text-2xl font-mono font-bold tracking-widest text-white"><?= h($_SESSION['verify_code_hint']) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-4 p-3 rounded-lg bg-red-900/40 border border-red-700 text-red-300 text-sm">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label for="code" class="block text-sm font-medium text-gray-300 mb-1">Verification Code</label>
                <input type="text" id="code" name="code" required maxlength="6" pattern="\d{6}"
                       class="w-full px-4 py-3 rounded-lg bg-dark-900 border border-gray-600 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none text-white text-center text-2xl tracking-[0.5em] placeholder-gray-500"
                       placeholder="000000" autocomplete="one-time-code">
            </div>

            <button type="submit"
                    class="w-full py-3 rounded-lg bg-primary-600 hover:bg-primary-700 text-white font-semibold transition">
                Verify
            </button>
        </form>

        <p class="text-center text-gray-400 text-sm mt-6">
            Didn't receive the code?
            <button type="button" id="resend-btn" onclick="resendCode()"
                    class="text-primary-400 hover:text-primary-300 hover:underline transition font-medium">
                Resend code
            </button>
            <br><span class="text-xs text-gray-500 mt-2 block">or <a href="<?= url('register.php') ?>" class="text-primary-400 hover:underline">register again</a></span>
        </p>

        <div id="resend-message" class="mt-4 p-3 rounded-lg hidden text-sm text-center"></div>
    </div>
</div>

<script>
let resendCooldown = 0;

async function resendCode() {
    const btn = document.getElementById('resend-btn');
    const msg = document.getElementById('resend-message');
    
    // Check cooldown
    if (resendCooldown > 0) {
        msg.className = 'mt-4 p-3 rounded-lg text-sm text-center bg-yellow-900/40 border border-yellow-600 text-yellow-300';
        msg.textContent = `Please wait ${resendCooldown} seconds...`;
        msg.classList.remove('hidden');
        return;
    }
    
    btn.disabled = true;
    btn.textContent = 'Sending...';
    msg.className = 'mt-4 p-3 rounded-lg hidden text-sm text-center';
    
    try {
        const fd = new FormData();
        const res = await fetch(<?= json_encode(url('resend_verify.php')) ?>, { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.ok) {
            msg.className = 'mt-4 p-3 rounded-lg text-sm text-center bg-green-900/40 border border-green-600 text-green-300';
            msg.textContent = data.message || 'Code sent! Check your email.';
            
            // Start 60-second cooldown
            resendCooldown = 60;
            const countdown = setInterval(() => {
                resendCooldown--;
                if (resendCooldown <= 0) {
                    clearInterval(countdown);
                    btn.disabled = false;
                    btn.textContent = 'Resend code';
                    msg.classList.add('hidden');
                } else {
                    msg.textContent = `Next resend available in ${resendCooldown}s`;
                }
            }, 1000);
        } else {
            msg.className = 'mt-4 p-3 rounded-lg text-sm text-center bg-red-900/40 border border-red-600 text-red-300';
            msg.textContent = data.error || 'Error sending code.';
            btn.disabled = false;
            btn.textContent = 'Resend code';
        }
        msg.classList.remove('hidden');
    } catch (err) {
        console.error(err);
        msg.className = 'mt-4 p-3 rounded-lg text-sm text-center bg-red-900/40 border border-red-600 text-red-300';
        msg.textContent = 'Network error. Try again.';
        msg.classList.remove('hidden');
        btn.disabled = false;
        btn.textContent = 'Resend code';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
