<?php

require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

$word = trim($_GET['word'] ?? '');
$from = $_GET['from'] ?? 'en';
$to = $_GET['to'] ?? 'tr';

if (empty($word)) {
    json_response(['error' => 'Kelime gerekli'], 400);
}

// Sanitize: only allow reasonable word/phrase length
if (mb_strlen($word) > 200) {
    json_response(['error' => 'Kelime cok uzun'], 400);
}

// Rate limiting via session
session_start();
$now = microtime(true);
$lastRequest = $_SESSION['last_translate'] ?? 0;
if ($now - $lastRequest < 0.5) {
    json_response(['error' => 'Cok hizli istek. Lutfen bekleyin.'], 429);
}
$_SESSION['last_translate'] = $now;

// Call MyMemory Translation API
$langpair = urlencode($from) . '|' . urlencode($to);
$query = urlencode($word);
$url = "https://api.mymemory.translated.net/get?q={$query}&langpair={$langpair}";

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'header' => "User-Agent: PDFLibrary/1.0\r\n",
    ],
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    json_response(['error' => 'Ceviri servisi yanit vermedi'], 502);
}

$result = json_decode($response, true);

if (!$result || !isset($result['responseData'])) {
    json_response(['error' => 'Ceviri alinamadi'], 500);
}

$translation = $result['responseData']['translatedText'] ?? '';
$matches = [];

if (isset($result['matches']) && is_array($result['matches'])) {
    foreach (array_slice($result['matches'], 0, 5) as $match) {
        $matches[] = [
            'translation' => $match['translation'] ?? '',
            'quality' => $match['quality'] ?? '',
            'source' => $match['created-by'] ?? '',
        ];
    }
}

json_response([
    'word' => $word,
    'translation' => $translation,
    'matches' => $matches,
]);
