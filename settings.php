<?php
/**
 * Movify – Settings Page
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$user = current_user($pdo);
$success = '';
$error   = '';

// Handle credit addition
if (is_post()) {
    if (!verify_csrf(post('csrf_token'))) {
        $error = 'Invalid request.';
    } else {
        $action = post('action');

        if ($action === 'add_credits') {
            $amount = (int)post('credits_amount');
            if ($amount < 1 || $amount > 10000) {
                $error = 'Enter a valid amount (1 – 10,000).';
            } else {
                $stmt = $pdo->prepare('UPDATE users SET credits = credits + ? WHERE id = ?');
                $stmt->execute([$amount, $user['id']]);
                $user['credits'] = (int)$user['credits'] + $amount;
                $success = $amount . ' credits added successfully! New balance: ' . $user['credits'];
            }
        }
    }
}

$pageTitle = 'Settings';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-10">

    <!-- Top Bar -->
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold">Settings</h1>
        <a href="<?= url('dashboard.php') ?>" class="text-sm text-primary-400 hover:text-primary-300 transition flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Back to Dashboard
        </a>
    </div>

    <?php if ($success): ?>
        <div class="mb-6 p-4 rounded-lg bg-green-900/40 border border-green-700 text-green-300 text-sm">
            <?= h($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mb-6 p-4 rounded-lg bg-red-900/40 border border-red-700 text-red-300 text-sm">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <!-- Account Info -->
    <div class="bg-dark-800 rounded-2xl p-6 border border-gray-700 mb-6">
        <h2 class="text-lg font-semibold mb-4">Account Information</h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <span class="text-gray-400 text-sm">Email</span>
                <p class="text-white font-medium"><?= h($user['email']) ?></p>
            </div>
            <div>
                <span class="text-gray-400 text-sm">Current Balance</span>
                <p class="text-yellow-300 font-bold text-xl flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                    </svg>
                    <?= (int)$user['credits'] ?> credits
                </p>
            </div>
        </div>
    </div>

    <!-- Add Credits -->
    <div class="bg-dark-800 rounded-2xl p-6 border border-gray-700 mb-6">
        <h2 class="text-lg font-semibold mb-4">Add Credits</h2>
        <form method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_credits">

            <div>
                <label for="credits_amount" class="block text-sm font-medium text-gray-300 mb-1">Amount</label>
                <input type="number" id="credits_amount" name="credits_amount" min="1" max="10000" value="100" required
                       class="w-full sm:w-64 px-4 py-3 rounded-lg bg-dark-900 border border-gray-600 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none text-white placeholder-gray-500">
            </div>

            <!-- Quick amounts -->
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="document.getElementById('credits_amount').value=50"
                        class="px-3 py-1.5 rounded-lg bg-dark-900 border border-gray-600 text-gray-300 text-sm hover:border-primary-500 hover:text-white transition">50</button>
                <button type="button" onclick="document.getElementById('credits_amount').value=100"
                        class="px-3 py-1.5 rounded-lg bg-dark-900 border border-gray-600 text-gray-300 text-sm hover:border-primary-500 hover:text-white transition">100</button>
                <button type="button" onclick="document.getElementById('credits_amount').value=500"
                        class="px-3 py-1.5 rounded-lg bg-dark-900 border border-gray-600 text-gray-300 text-sm hover:border-primary-500 hover:text-white transition">500</button>
                <button type="button" onclick="document.getElementById('credits_amount').value=1000"
                        class="px-3 py-1.5 rounded-lg bg-dark-900 border border-gray-600 text-gray-300 text-sm hover:border-primary-500 hover:text-white transition">1000</button>
            </div>

            <button type="submit"
                    class="px-6 py-3 rounded-lg bg-primary-600 hover:bg-primary-700 text-white font-semibold transition">
                Add Credits
            </button>
        </form>
    </div>

    <!-- Future settings placeholder -->
    <div class="bg-dark-800 rounded-2xl p-6 border border-gray-700 opacity-50">
        <h2 class="text-lg font-semibold mb-2">More Settings</h2>
        <p class="text-gray-400 text-sm">Additional settings will appear here in future updates.</p>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
