<?php

require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost();
        break;
    case 'PUT':
        handlePut();
        break;
    case 'DELETE':
        handleDelete();
        break;
    default:
        json_response(['error' => 'Method not allowed'], 405);
}

function handleGet(): void {
    // CSV export
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        exportCsv();
        return;
    }

    $data = read_json(VOCABULARY_FILE);
    $words = $data['words'] ?? [];

    // Filter by search
    $search = trim($_GET['search'] ?? '');
    if (!empty($search)) {
        $search = mb_strtolower($search);
        $words = array_values(array_filter($words, function ($w) use ($search) {
            return str_contains(mb_strtolower($w['english']), $search)
                || str_contains(mb_strtolower($w['turkish']), $search);
        }));
    }

    // Filter by mastered
    if (isset($_GET['mastered'])) {
        $mastered = $_GET['mastered'] === '1';
        $words = array_values(array_filter($words, fn($w) => ($w['mastered'] ?? false) === $mastered));
    }

    // Sort by newest first
    usort($words, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

    json_response(['words' => $words, 'total' => count($words)]);
}

function handlePost(): void {
    $input = get_input();
    $english = trim($input['english'] ?? '');
    $turkish = trim($input['turkish'] ?? '');

    if (empty($english) || empty($turkish)) {
        json_response(['error' => 'Ingilizce ve Turkce kelime gerekli'], 400);
    }

    $data = read_json(VOCABULARY_FILE);
    $words = $data['words'] ?? [];

    // Check duplicate
    foreach ($words as $w) {
        if (mb_strtolower($w['english']) === mb_strtolower($english)) {
            json_response(['error' => 'Bu kelime zaten kayitli', 'word' => $w], 409);
        }
    }

    $word = [
        'id' => generate_id('w-'),
        'english' => $english,
        'turkish' => $turkish,
        'context' => trim($input['context'] ?? ''),
        'source_pdf' => $input['source_pdf'] ?? '',
        'source_page' => (int)($input['source_page'] ?? 0),
        'created_at' => date('c'),
        'mastered' => false,
    ];

    $words[] = $word;
    write_json(VOCABULARY_FILE, ['words' => $words]);
    json_response(['word' => $word], 201);
}

function handlePut(): void {
    $input = get_input();
    $id = $input['id'] ?? '';

    if (empty($id)) {
        json_response(['error' => 'Kelime ID gerekli'], 400);
    }

    $data = read_json(VOCABULARY_FILE);
    $words = $data['words'] ?? [];
    $found = false;

    foreach ($words as &$word) {
        if ($word['id'] === $id) {
            $found = true;
            if (isset($input['mastered'])) $word['mastered'] = (bool)$input['mastered'];
            if (isset($input['turkish'])) $word['turkish'] = trim($input['turkish']);
            if (isset($input['context'])) $word['context'] = trim($input['context']);
            break;
        }
    }

    if (!$found) {
        json_response(['error' => 'Kelime bulunamadi'], 404);
    }

    write_json(VOCABULARY_FILE, ['words' => $words]);
    json_response(['success' => true]);
}

function handleDelete(): void {
    $id = $_GET['id'] ?? '';
    if (empty($id)) {
        json_response(['error' => 'Kelime ID gerekli'], 400);
    }

    $data = read_json(VOCABULARY_FILE);
    $words = $data['words'] ?? [];
    $filtered = array_values(array_filter($words, fn($w) => $w['id'] !== $id));

    if (count($filtered) === count($words)) {
        json_response(['error' => 'Kelime bulunamadi'], 404);
    }

    write_json(VOCABULARY_FILE, ['words' => $filtered]);
    json_response(['success' => true]);
}

function exportCsv(): void {
    $data = read_json(VOCABULARY_FILE);
    $words = $data['words'] ?? [];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="kelime_defteri_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    // BOM for Excel UTF-8 compatibility
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['English', 'Turkish', 'Context', 'Source', 'Page', 'Mastered', 'Date']);

    foreach ($words as $w) {
        fputcsv($output, [
            $w['english'],
            $w['turkish'],
            $w['context'] ?? '',
            $w['source_pdf'] ?? '',
            $w['source_page'] ?? '',
            ($w['mastered'] ?? false) ? 'Yes' : 'No',
            $w['created_at'] ?? '',
        ]);
    }

    fclose($output);
    exit;
}
