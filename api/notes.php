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
    $data = read_json(NOTES_FILE);
    $notes = $data['notes'] ?? [];

    // Filter by search
    $search = trim($_GET['search'] ?? '');
    if (!empty($search)) {
        $search = mb_strtolower($search);
        $notes = array_values(array_filter($notes, function ($n) use ($search) {
            return str_contains(mb_strtolower($n['text']), $search)
                || str_contains(mb_strtolower($n['source_pdf'] ?? ''), $search);
        }));
    }

    // Filter by source PDF
    $source = trim($_GET['source'] ?? '');
    if (!empty($source)) {
        $notes = array_values(array_filter($notes, fn($n) => ($n['source_pdf'] ?? '') === $source));
    }

    // Sort by newest first
    usort($notes, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

    json_response(['notes' => $notes, 'total' => count($notes)]);
}

function handlePost(): void {
    $input = get_input();
    $text = trim($input['text'] ?? '');

    if (empty($text)) {
        json_response(['error' => 'Alinti metni gerekli'], 400);
    }

    $data = read_json(NOTES_FILE);
    $notes = $data['notes'] ?? [];

    $note = [
        'id' => generate_id('n-'),
        'text' => $text,
        'source_pdf' => $input['source_pdf'] ?? '',
        'source_page' => (int)($input['source_page'] ?? 0),
        'source_name' => $input['source_name'] ?? '',
        'comment' => trim($input['comment'] ?? ''),
        'created_at' => date('c'),
    ];

    $notes[] = $note;
    write_json(NOTES_FILE, ['notes' => $notes]);
    json_response(['note' => $note], 201);
}

function handlePut(): void {
    $input = get_input();
    $id = $input['id'] ?? '';

    if (empty($id)) {
        json_response(['error' => 'Not ID gerekli'], 400);
    }

    $data = read_json(NOTES_FILE);
    $notes = $data['notes'] ?? [];
    $found = false;

    foreach ($notes as &$note) {
        if ($note['id'] === $id) {
            $found = true;
            if (isset($input['comment'])) $note['comment'] = trim($input['comment']);
            if (isset($input['text'])) $note['text'] = trim($input['text']);
            break;
        }
    }

    if (!$found) {
        json_response(['error' => 'Not bulunamadi'], 404);
    }

    write_json(NOTES_FILE, ['notes' => $notes]);
    json_response(['success' => true]);
}

function handleDelete(): void {
    $id = $_GET['id'] ?? '';
    if (empty($id)) {
        json_response(['error' => 'Not ID gerekli'], 400);
    }

    $data = read_json(NOTES_FILE);
    $notes = $data['notes'] ?? [];
    $filtered = array_values(array_filter($notes, fn($n) => $n['id'] !== $id));

    if (count($filtered) === count($notes)) {
        json_response(['error' => 'Not bulunamadi'], 404);
    }

    write_json(NOTES_FILE, ['notes' => $filtered]);
    json_response(['success' => true]);
}
