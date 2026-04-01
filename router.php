<?php
// router.php — PHP built-in server router for Railway / local dev
// Usage: php -S 0.0.0.0:$PORT router.php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ── Strip query string and decode ────────────────────────────────────────────
$uri = rawurldecode($uri);

// ── Route /api/* → api/index.php ─────────────────────────────────────────────
if (strpos($uri, '/api') === 0) {
    require __DIR__ . '/api/index.php';
    exit;
}

// ── Resolve the file path ─────────────────────────────────────────────────────
// Try exact path first, then inside /public/
$candidates = [
    __DIR__ . $uri,
    __DIR__ . '/public' . $uri,
];

foreach ($candidates as $file) {
    if (is_file($file)) {
        // Set correct MIME type
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = [
            'html' => 'text/html',
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'json' => 'application/json',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'ico'  => 'image/x-icon',
            'svg'  => 'image/svg+xml',
        ][$ext] ?? 'application/octet-stream';

        header('Content-Type: ' . $mime);
        readfile($file);
        exit;
    }
}

// ── Fallback → serve index.html ───────────────────────────────────────────────
header('Content-Type: text/html');
readfile(__DIR__ . '/public/index.html');
exit;