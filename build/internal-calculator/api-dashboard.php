<?php
// ── Dashboard API — returns orders and browse logs as JSON ───────────────
// Used by the internal calculator's Dashboard tab.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

define('ORDERS_DIR', __DIR__ . '/orders/');

$action = $_GET['action'] ?? '';

if ($action === 'orders') {
    // Return all order JSON files
    $orders = [];
    if (is_dir(ORDERS_DIR)) {
        foreach (glob(ORDERS_DIR . '*.json') as $file) {
            $name = basename($file, '.json');
            // Skip non-order files (rate limiter, counters)
            if (strpos($name, 'calc_rate') !== false) continue;
            if (strpos($name, 'invoice_counter') !== false) continue;
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['invoice_num'])) {
                $orders[] = $data;
            }
        }
    }
    // Sort newest first
    usort($orders, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
    echo json_encode($orders);
    exit;
}

if ($action === 'logs') {
    // Return calculator browse logs
    $log_file = ORDERS_DIR . 'calc_log.jsonl';
    $entries = [];
    if (file_exists($log_file)) {
        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if ($entry) $entries[] = $entry;
        }
    }
    echo json_encode($entries);
    exit;
}

if ($action === 'update') {
    // Update production status fields on an order
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

    $input = json_decode(file_get_contents('php://input'), true);
    $invoice = $input['invoice_num'] ?? '';
    if (!$invoice || !preg_match('/^\d{4}-\d{3}$/', $invoice)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid invoice number']);
        exit;
    }

    $file = ORDERS_DIR . $invoice . '.json';
    if (!file_exists($file)) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    $order = json_decode(file_get_contents($file), true);
    $allowed = ['files_checked', 'printed', 'shipped', 'ready_pickup', 'done'];
    foreach ($allowed as $field) {
        if (isset($input[$field])) {
            $order[$field] = (bool)$input[$field];
        }
    }
    $order['production_updated_at'] = date('c');

    file_put_contents($file, json_encode($order, JSON_PRETTY_PRINT));
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action. Use ?action=orders, ?action=logs, or ?action=update']);
