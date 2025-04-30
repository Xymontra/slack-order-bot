<?php

function isValidSlackRequest($config) {
    $timestamp = $_SERVER['HTTP_X_SLACK_REQUEST_TIMESTAMP'] ?? '';
    $signature = $_SERVER['HTTP_X_SLACK_SIGNATURE'] ?? '';

    // 5 dakikadan eskiyse reddet (Replay attack koruması)
    if (abs(time() - (int)$timestamp) > 60 * 5) {
        return false;
    }

    $reqBody = file_get_contents('php://input');
    $baseString = "v0:$timestamp:$reqBody";
    $computedSig = 'v0=' . hash_hmac('sha256', $baseString, $config['slack_signing_secret']);

    return hash_equals($computedSig, $signature);
}

$config = require 'config.php';
require 'Api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!isValidSlackRequest($config)) {
    http_response_code(403);
    echo "❌ Geçersiz Slack imzası.";
    exit;
}

$command     = $_POST['command']     ?? '';
$text        = $_POST['text']        ?? '';
$channelId   = $_POST['channel_id']  ?? '';
$userId      = $_POST['user_id']     ?? ''; 
$userName    = $_POST['user_name']   ?? 'unknown';

if ($channelId !== $config['allowed_channel']) {
    respond("❌ Bu komut yalnızca yetkili kanalda kullanılabilir.");
}

$args = preg_split('/\s+/', trim($text));
$command = strtolower(trim($command));

// JSON response fonksiyonu
function respond($message, $type = 'in_channel') {
    header('Content-Type: application/json');
    echo json_encode([
        'response_type' => $type,
        'text' => $message
    ]);
    exit;
}

switch ($command) {

    case '/otohikaye':
        if (count($args) !== 3) {
            respond("❌ Hatalı format: TR|GLOBAL|AZE link miktar");
        }

        [$tip, $link, $quantity] = $args;
        $tip = strtoupper($tip);

        if (!isset($config['hikaye_services'][$tip])) {
            respond("❌ Geçersiz servis tipi: TR, GLOBAL, AZE");
        }

        $api = new Api($config['api_url'], $config['api_keys']['otohikaye']);

        $response = $api->order([
            'service'  => $config['hikaye_services'][$tip],
            'link'     => $link,
            'quantity' => $quantity,
            'runs'     => 480,
            'interval' => 90
        ]);

        if (!isset($response->order)) {
            respond("❌ <@$userId> Sipariş oluşturulamadı.");
        } else {
            respond("✅ <@$userId> Drip-feed siparişi alındı. Sipariş ID: {$response->order}");
        }
        break;

    case '/otolike':
        if (count($args) !== 5) {
            respond("❌ Hatalı format: kullanıcı min max post_sayısı bitiş_tarihi");
        }

        [$username, $min, $max, $posts, $expiry] = $args;

        $api = new Api($config['api_url'], $config['api_keys']['otolike']);

        $response = $api->order([
            'service'  => $config['like_service_id'],
            'username' => $username,
            'min'      => $min,
            'max'      => $max,
            'posts'    => $posts,
            'expiry'   => $expiry,
            'delay'    => 0
        ]);

        if (!isset($response->order)) {
            respond("❌ <@$userId> Abonelik siparişi oluşturulamadı.");
        } else {
            respond("✅ <@$userId> Abonelik siparişi alındı. Sipariş ID: {$response->order}");
        }
        break;

    default:
        respond("❌ Tanınmayan komut.");
}
