<?php
/**
 * Movify – Main Dashboard
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/credits_helper.php';

require_login();

$user = current_user($pdo);
if (!$user) {
    logout_user();
    redirect('login.php');
}

// ── Fetch user's videos (latest first) ──────────────────────────────
$stmt = $pdo->prepare(
    'SELECT * FROM videos WHERE user_id = ? ORDER BY created_at DESC LIMIT 50'
);
$stmt->execute([$user['id']]);
$videos = $stmt->fetchAll();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Top Bar -->
<nav class="bg-dark-800 border-b border-gray-700 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
        <a href="<?= url('dashboard.php') ?>" class="text-xl font-bold bg-gradient-to-r from-primary-400 to-purple-400 bg-clip-text text-transparent">
            Movify
        </a>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2 bg-dark-900 px-4 py-2 rounded-lg border border-gray-700">
                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                </svg>
                <span id="credit-balance" class="font-semibold text-yellow-300"><?= (int)$user['credits'] ?></span>
                <span class="text-gray-400 text-sm">credits</span>
            </div>
            <span class="text-gray-400 text-sm hidden sm:inline"><?= h($user['email']) ?></span>
            <!-- Hamburger menu -->
            <div class="relative" id="menu-wrapper">
                <button id="menu-toggle" class="p-2 rounded-lg hover:bg-dark-700 transition text-gray-400 hover:text-white" title="Menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>
                <div id="menu-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-dark-800 border border-gray-700 rounded-xl shadow-2xl z-50 overflow-hidden">
                    <a href="<?= url('dashboard.php') ?>" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-300 hover:bg-dark-700 hover:text-white transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z"/>
                        </svg>
                        Dashboard
                    </a>
                    <a href="<?= url('settings.php') ?>" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-300 hover:bg-dark-700 hover:text-white transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                        Settings
                    </a>
                    <div class="border-t border-gray-700"></div>
                    <a href="<?= url('logout.php') ?>" class="flex items-center gap-3 px-4 py-3 text-sm text-red-400 hover:bg-dark-700 hover:text-red-300 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                        </svg>
                        Exit
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 py-8">
    <div class="grid lg:grid-cols-3 gap-8">

        <!-- ════════════════ LEFT: Controls ════════════════ -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-dark-800 rounded-2xl p-6 border border-gray-700">
                <h2 class="text-lg font-semibold mb-5">Generate Video</h2>

                <form id="generate-form" class="space-y-4" enctype="multipart/form-data">

                    <!-- Prompt -->
                    <div>
                        <label for="prompt" class="block text-sm font-medium text-gray-300 mb-1">Prompt (text)</label>
                        <textarea id="prompt" name="prompt" rows="3"
                                  class="w-full px-4 py-3 rounded-lg bg-dark-900 border border-gray-600 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none text-white placeholder-gray-500 resize-none"
                                  placeholder="Describe the video you want..."></textarea>
                    </div>

                    <!-- Image upload -->
                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-300 mb-1">Image (optional)</label>
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"
                               class="w-full text-sm text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-600 file:text-white file:cursor-pointer hover:file:bg-primary-700">
                    </div>

                    <!-- Model -->
                    <div>
                        <label for="model" class="block text-sm font-medium text-gray-300 mb-1">Choose AI Model</label>
                        <select id="model" name="model"
                                class="w-full px-4 py-3 rounded-lg bg-dark-900 border border-gray-600 focus:border-primary-500 outline-none text-white">
                            <option value="wan_fast" data-cost="5" data-fps="8,12,16,20,24" data-fps-default="16">Wan 2.1 Fast (Eco - Smooth Motion) – 5 cr/s</option>
                            <option value="ltx_video" data-cost="4" data-fps="" data-fps-default="">LTX Video (Ultra-Rapid) – 4 cr/s</option>
                            <option value="kling_turbo" data-cost="8" data-fps="" data-fps-default="">Kling 1.6 Standard (Pro - Human Realism) – 8 cr/s</option>
                        </select>
                    </div>

                    <!-- Format -->
                    <div>
                        <label for="format" class="block text-sm font-medium text-gray-300 mb-1">Format</label>
                        <select id="format" name="format"
                                class="w-full px-4 py-3 rounded-lg bg-dark-900 border border-gray-600 focus:border-primary-500 outline-none text-white">
                            <option value="movie">Movie (16:9)</option>
                            <option value="portrait">Portrait (9:16)</option>
                            <option value="landscape">Landscape (3:2)</option>
                            <option value="portrait_32">Portrait (2:3)</option>
                            <option value="square">Square (1:1)</option>
                        </select>
                    </div>

                    <!-- Resolution -->
                    <div>
                        <label for="resolution" class="block text-sm font-medium text-gray-300 mb-1">Resolution</label>
                        <select id="resolution" name="resolution"
                                class="w-full px-4 py-3 rounded-lg bg-dark-900 border border-gray-600 focus:border-primary-500 outline-none text-white">
                            <option value="720p">720p (x1)</option>
                            <option value="1080p">1080p (x1.5)</option>
                            <option value="4k">4K (x2)</option>
                        </select>
                    </div>

                    <!-- Duration -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Duration</label>
                        <div class="grid grid-cols-4 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="duration" value="4" checked class="peer hidden">
                                <div class="text-center py-2 rounded-lg border border-gray-600 peer-checked:border-primary-500 peer-checked:bg-primary-600/20 text-sm transition">4s</div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="duration" value="6" class="peer hidden">
                                <div class="text-center py-2 rounded-lg border border-gray-600 peer-checked:border-primary-500 peer-checked:bg-primary-600/20 text-sm transition">6s</div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="duration" value="8" class="peer hidden">
                                <div class="text-center py-2 rounded-lg border border-gray-600 peer-checked:border-primary-500 peer-checked:bg-primary-600/20 text-sm transition">8s</div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="duration" value="10" class="peer hidden">
                                <div class="text-center py-2 rounded-lg border border-gray-600 peer-checked:border-primary-500 peer-checked:bg-primary-600/20 text-sm transition">10s</div>
                            </label>
                        </div>
                    </div>

                    <!-- Frame Rate (shown only for models that support it) -->
                    <div id="fps-section" class="">
                        <label for="fps" class="block text-sm font-medium text-gray-300 mb-1">Frame Rate (FPS)</label>
                        <select id="fps" name="fps"
                                class="w-full px-4 py-3 rounded-lg bg-dark-900 border border-gray-600 focus:border-primary-500 outline-none text-white">
                            <option value="8">8 FPS</option>
                            <option value="12">12 FPS</option>
                            <option value="16" selected>16 FPS (default)</option>
                            <option value="20">20 FPS</option>
                            <option value="24">24 FPS</option>
                        </select>
                        <p id="fps-duration-hint" class="mt-1 text-xs text-gray-500"></p>
                    </div>

                    <!-- Cost preview -->
                    <div class="flex items-center justify-between p-3 rounded-lg bg-dark-900 border border-gray-700">
                        <span class="text-gray-400 text-sm">Estimated cost:</span>
                        <span id="cost-preview" class="text-yellow-300 font-bold text-lg">4</span>
                        <span class="text-gray-400 text-sm">credits</span>
                    </div>

                    <!-- Submit -->
                    <button type="submit" id="btn-generate"
                            class="w-full py-3 rounded-xl bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-semibold text-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Generate Video
                    </button>
                </form>

                <!-- Processing overlay -->
                <div id="processing" class="hidden mt-4 p-4 rounded-lg bg-dark-900 border border-gray-700 text-center">
                    <div class="inline-block w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full animate-spin mb-3"></div>
                    <p class="text-gray-300 text-sm" id="processing-text">Generating video...</p>
                    <div class="w-full bg-gray-700 rounded-full h-2 mt-3">
                        <div id="progress-bar" class="bg-primary-500 h-2 rounded-full transition-all duration-500" style="width: 5%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════ RIGHT: Gallery ════════════════ -->
        <div class="lg:col-span-2">
            <h2 class="text-lg font-semibold mb-4">My Gallery</h2>

            <?php if (empty($videos)): ?>
                <div class="bg-dark-800 rounded-2xl p-12 border border-gray-700 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15.75 10.5l4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9A2.25 2.25 0 0 0 13.5 5.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25z"/>
                    </svg>
                    <p class="text-gray-400">You haven't generated any videos yet.</p>
                    <p class="text-gray-500 text-sm mt-1">Fill in the form on the left to get started.</p>
                </div>
            <?php else: ?>
                <div id="video-grid" class="grid sm:grid-cols-2 gap-4">
                    <?php foreach ($videos as $v): ?>
                        <div class="bg-dark-800 rounded-xl overflow-hidden border border-gray-700 hover:border-gray-600 transition group">
                            <?php if ($v['status'] === 'completed' && $v['video_url']): ?>
                                <video class="w-full aspect-video bg-black" controls preload="metadata">
                                    <source src="<?= h($v['video_url']) ?>" type="video/mp4">
                                </video>
                            <?php elseif ($v['status'] === 'processing'): ?>
                                <div class="w-full aspect-video bg-dark-900 flex items-center justify-center">
                                    <div class="text-center">
                                        <div class="inline-block w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full animate-spin mb-2"></div>
                                        <p class="text-gray-400 text-sm">Processing...</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="w-full aspect-video bg-dark-900 flex items-center justify-center">
                                    <p class="text-red-400 text-sm">Generation failed</p>
                                </div>
                            <?php endif; ?>

                            <div class="p-3 space-y-2">
                                <?php if ($v['prompt']): ?>
                                    <p class="text-sm text-gray-300 truncate" title="<?= h($v['prompt']) ?>">
                                        <?= h($v['prompt']) ?>
                                    </p>
                                <?php endif; ?>
                                <div class="flex flex-wrap gap-1.5">
                                    <span class="text-xs px-2 py-0.5 rounded bg-primary-600/20 text-primary-300"><?= h(ucfirst($v['model_used'])) ?></span>
                                    <span class="text-xs px-2 py-0.5 rounded bg-gray-700 text-gray-300"><?= h($v['resolution']) ?></span>
                                    <span class="text-xs px-2 py-0.5 rounded bg-gray-700 text-gray-300"><?= (int)$v['duration'] ?>s</span>
                                    <span class="text-xs px-2 py-0.5 rounded bg-yellow-600/20 text-yellow-300"><?= (int)$v['credits_deducted'] ?> credits</span>
                                </div>
                                <div class="flex items-center gap-3 mt-1">
                                    <?php if ($v['status'] === 'completed' && $v['video_url']): ?>
                                        <a href="<?= h($v['video_url']) ?>" download
                                           class="inline-flex items-center gap-1 text-xs text-primary-400 hover:text-primary-300 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                            </svg>
                                            Download
                                        </a>
                                    <?php endif; ?>
                                    <button onclick="deleteVideo(<?= (int)$v['id'] ?>, this)"
                                            class="inline-flex items-center gap-1 text-xs text-red-400 hover:text-red-300 transition"
                                            title="Delete video">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                        </svg>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<script>
async function deleteVideo(videoId, btn) {
    if (!confirm('Are you sure you want to delete this video?')) return;
    btn.disabled = true;
    btn.textContent = '...';
    try {
        const fd = new FormData();
        fd.append('video_id', videoId);
        const res  = await fetch(<?= json_encode(url('delete_video.php')) ?>, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) {
            const card = btn.closest('.group');
            if (card) card.remove();
        } else {
            alert(data.error || 'Delete error.');
            btn.disabled = false;
            btn.textContent = 'Delete';
        }
    } catch (err) {
        console.error(err);
        alert('Network error.');
        btn.disabled = false;
        btn.textContent = 'Delete';
    }
}

// Hamburger menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('menu-toggle');
    const dropdown = document.getElementById('menu-dropdown');
    if (toggle && dropdown) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
        });
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && e.target !== toggle) {
                dropdown.classList.add('hidden');
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
