<?php
declare(strict_types=1);

/**
 * StumpVision — api/renderCard.php
 * Controller: validates input, loads JSON, calls CardRenderer + VideoBuilder,
 * and returns a small JSON payload for the front end.
 */

header('Content-Type: application/json');

use StumpVision\Util;
use StumpVision\CardRenderer;
use StumpVision\VideoBuilder;

require_once __DIR__ . '/lib/Util.php';
require_once __DIR__ . '/lib/CardRenderer.php';
require_once __DIR__ . '/lib/VideoBuilder.php';

try {
    if (!extension_loaded('imagick')) {
        echo json_encode(['ok'=>false,'error'=>'Imagick extension not available']); exit;
    }

    [$root, $dataDir, $cardsDir] = Util::dirs();

    $id = isset($_GET['id']) ? basename((string)$_GET['id']) : '';
    if ($id === '') {
        echo json_encode(['ok'=>false,'error'=>'Missing ?id parameter']); exit;
    }

    $jsonPath = $dataDir . DIRECTORY_SEPARATOR . $id . '.json';
    if (!is_file($jsonPath)) {
        echo json_encode(['ok'=>false,'error'=>"Match not found: $id"]); exit;
    }

    $raw = file_get_contents($jsonPath);
    if ($raw === false) {
        echo json_encode(['ok'=>false,'error'=>'Could not read match file']); exit;
    }

    $match = json_decode($raw, true);
    if (!is_array($match)) {
        echo json_encode(['ok'=>false,'error'=>'Malformed match JSON']); exit;
    }

    $baseName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $id);

    // 1) Render slides (+ cover)
    [$slides, $coverPng] = CardRenderer::render($match, $cardsDir, $baseName);

    // 2) Try to build MP4
    $videoPath = $cardsDir . DIRECTORY_SEPARATOR . $baseName . '.mp4';
    $video = VideoBuilder::build($slides, $videoPath);

    if ($video['ok']) {
        echo json_encode([
            'ok' => true,
            'mp4' => '/data/cards/' . basename($video['mp4Path']),
            'fallback_png' => '/data/cards/' . basename($coverPng)
        ]);
        exit;
    }

    // PNG fallback
    echo json_encode([
        'ok' => true,
        'mp4' => null,
        'fallback_png' => '/data/cards/' . basename($coverPng),
        'note' => $video['error'] ?? 'FFmpeg not found'
    ]);
} catch (Throwable $e) {
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
