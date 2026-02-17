<?php
// inc_chat-proxy.php
// Frontend'den gelen mesajı alır, n8n webhook'una iletir ve cevabı geri döndürür.

header('Content-Type: application/json; charset=utf-8');

// ---- AYARLAR ----
$N8N_WEBHOOK_URL = 'https://n8n.e12.com.tr/webhook/lingo-chatbot';
$N8N_API_KEY     = 'lingo_chatbot_api_key_060320018426';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
 http_response_code(405);
 echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
 exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

$message   = isset($body['message']) ? trim($body['message']) : '';
$sessionId = isset($body['sessionId']) ? trim($body['sessionId']) : '';

if ($message === '' || $sessionId === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'message and sessionId are required']);
  exit;
}

$payload = json_encode([
  'message'   => $message,
  'sessionId' => $sessionId,
], JSON_UNESCAPED_UNICODE);

$ch = curl_init($N8N_WEBHOOK_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Content-Type: application/json',
  'Authorization: Bearer ' . $N8N_API_KEY,
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false) {
  http_response_code(502);
  echo json_encode(['ok' => false, 'error' => 'Upstream error: ' . ($curlErr ?: 'unknown')]);
  exit;
}

// n8n'den gelen cevabı aynen döndür
http_response_code($httpCode);
echo $response;
