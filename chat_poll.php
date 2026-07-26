<?php
// chat_poll.php — dipanggil berkala (polling) oleh widget live chat
// untuk mengambil pesan baru (termasuk balasan admin dari Telegram).

require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

$session = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['session'] ?? '');
$since = (int)($_GET['since'] ?? 0);

if ($session === '') {
    echo json_encode(['ok' => false, 'messages' => []]);
    exit;
}

$data = rk_chat_load($session);
$messages = [];
if ($data && !empty($data['messages'])) {
    foreach ($data['messages'] as $m) {
        if (($m['time'] ?? 0) > $since) $messages[] = $m;
    }
}

echo json_encode(['ok' => true, 'messages' => $messages, 'server_time' => time()]);
