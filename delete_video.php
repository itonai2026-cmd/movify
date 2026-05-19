<?php
/**
 * Movify – Delete Video Endpoint (AJAX)
 *
 * POST with: video_id
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Unauthorized.'], 401);
}

if (!is_post()) {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$userId  = (int)$_SESSION['user_id'];
$videoId = (int)post('video_id');

if (!$videoId) {
    json_response(['ok' => false, 'error' => 'video_id missing.'], 400);
}

// Verify ownership
$stmt = $pdo->prepare('SELECT id, image_path FROM videos WHERE id = ? AND user_id = ?');
$stmt->execute([$videoId, $userId]);
$video = $stmt->fetch();

if (!$video) {
    json_response(['ok' => false, 'error' => 'Video not found.'], 404);
}

// Delete local image if exists
if ($video['image_path'] && file_exists($video['image_path'])) {
    @unlink($video['image_path']);
}

// Delete from DB
$stmt = $pdo->prepare('DELETE FROM videos WHERE id = ? AND user_id = ?');
$stmt->execute([$videoId, $userId]);

json_response(['ok' => true, 'message' => 'Video deleted successfully.']);
