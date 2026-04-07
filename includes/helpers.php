<?php

require_once __DIR__ . '/config.php';

function read_json(string $filepath): array {
    if (!file_exists($filepath)) {
        return [];
    }
    $content = file_get_contents($filepath);
    if ($content === false) {
        return [];
    }
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function write_json(string $filepath, array $data): bool {
    $tmp = $filepath . '.tmp.' . getmypid();
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    return rename($tmp, $filepath);
}

function generate_id(string $prefix = ''): string {
    return $prefix . time() . '-' . bin2hex(random_bytes(4));
}

function slugify(string $text): string {
    $tr = ['ş' => 's', 'Ş' => 'S', 'ı' => 'i', 'İ' => 'I', 'ğ' => 'g', 'Ğ' => 'G',
           'ü' => 'u', 'Ü' => 'U', 'ö' => 'o', 'Ö' => 'O', 'ç' => 'c', 'Ç' => 'C'];
    $text = strtr($text, $tr);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function validate_path(string $path): bool {
    $real = realpath($path);
    if ($real === false) {
        return false;
    }
    $pdfRoot = realpath(PDF_PATH);
    return $pdfRoot !== false && str_starts_with($real, $pdfRoot);
}

function sanitize_filename(string $name): string {
    $name = basename($name);
    $name = preg_replace('/[^\w\s\-\.\(\)]/', '', $name);
    $name = preg_replace('/\s+/', '_', $name);
    $name = mb_substr($name, 0, 200);
    if (empty($name) || $name === '.pdf') {
        $name = 'document_' . time() . '.pdf';
    }
    return $name;
}

function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_input(): array {
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return $_POST;
    }
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
