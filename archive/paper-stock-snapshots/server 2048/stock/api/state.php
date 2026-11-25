<?php
/**
 * Paper Stock — shared state API
 * GET  api/state.php  -> returns the saved JSON state
 * POST api/state.php  -> overwrites the saved JSON state (whole document)
 *
 * Storage: ../data/state.json  (one small JSON file, file-locked on write).
 * Good for a few simultaneous users. Move to SQLite later if you want
 * per-change history / audit.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$dataDir = __DIR__ . '/../data';
$file    = $dataDir . '/state.json';
$method  = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!is_file($file)) {
        echo json_encode(['v' => 2, 'papers' => [], 'offer' => []]);
        exit;
    }
    readfile($file);
    exit;
}

if ($method === 'POST' || $method === 'PUT') {
    $raw  = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (!is_array($json) || !isset($json['papers'])) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid payload']);
        exit;
    }
    if (!is_dir($dataDir)) {
        @mkdir($dataDir, 0775, true);
    }
    $fp = fopen($file, 'c+');
    if (!$fp) {
        http_response_code(500);
        echo json_encode(['error' => 'cannot open data file']);
        exit;
    }
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method not allowed']);
