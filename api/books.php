<?php

require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

// Handle reading progress endpoints
if (isset($_GET['progress'])) {
    handleProgress($method);
    exit;
}

// Handle PDF serving
if (isset($_GET['serve'])) {
    servePdf();
    exit;
}

switch ($method) {
    case 'GET':
        listPdfs();
        break;
    case 'POST':
        uploadPdf();
        break;
    case 'DELETE':
        deletePdf();
        break;
    case 'PUT':
        movePdf();
        break;
    default:
        json_response(['error' => 'Method not allowed'], 405);
}

function listPdfs(): void {
    $category = $_GET['category'] ?? '';

    if (empty($category)) {
        // List all PDFs across all categories
        $data = read_json(CATEGORIES_FILE);
        $categories = $data['categories'] ?? [];
        $progress = read_json(PROGRESS_FILE)['progress'] ?? [];
        $allPdfs = [];

        foreach ($categories as $cat) {
            $dir = PDF_PATH . '/' . $cat['slug'];
            if (!is_dir($dir)) continue;
            $files = glob($dir . '/*.pdf');
            if (!$files) continue;
            foreach ($files as $file) {
                $key = $cat['slug'] . '/' . basename($file);
                $allPdfs[] = [
                    'name' => basename($file),
                    'category' => $cat['slug'],
                    'category_name' => $cat['name'],
                    'size' => filesize($file),
                    'modified' => date('c', filemtime($file)),
                    'last_page' => $progress[$key]['page'] ?? null,
                ];
            }
        }
        json_response(['pdfs' => $allPdfs]);
        return;
    }

    $slug = basename($category);
    $dir = PDF_PATH . '/' . $slug;

    if (!is_dir($dir)) {
        json_response(['error' => 'Kategori bulunamadi'], 404);
    }

    $files = glob($dir . '/*.pdf') ?: [];
    $progress = read_json(PROGRESS_FILE)['progress'] ?? [];
    $pdfs = [];

    foreach ($files as $file) {
        $key = $slug . '/' . basename($file);
        $pdfs[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'modified' => date('c', filemtime($file)),
            'last_page' => $progress[$key]['page'] ?? null,
        ];
    }

    json_response(['pdfs' => $pdfs, 'category' => $slug]);
}

function servePdf(): void {
    $category = basename($_GET['category'] ?? '');
    $file = basename($_GET['file'] ?? '');

    if (empty($category) || empty($file)) {
        http_response_code(400);
        exit('Missing parameters');
    }

    $path = PDF_PATH . '/' . $category . '/' . $file;

    if (!validate_path($path) || !file_exists($path)) {
        http_response_code(404);
        exit('File not found');
    }

    // Verify it's actually a PDF
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($path);
    if ($mime !== 'application/pdf') {
        http_response_code(403);
        exit('Not a PDF file');
    }

    $size = filesize($path);
    header('Content-Type: application/pdf');
    header('Content-Length: ' . $size);
    header('Content-Disposition: inline; filename="' . $file . '"');
    header('Accept-Ranges: bytes');
    header('Cache-Control: private, max-age=3600');

    // Support range requests for large PDFs
    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = $_SERVER['HTTP_RANGE'];
        if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            $start = (int)$matches[1];
            $end = !empty($matches[2]) ? (int)$matches[2] : $size - 1;
            $length = $end - $start + 1;

            http_response_code(206);
            header("Content-Range: bytes $start-$end/$size");
            header("Content-Length: $length");

            $fp = fopen($path, 'rb');
            fseek($fp, $start);
            echo fread($fp, $length);
            fclose($fp);
            exit;
        }
    }

    readfile($path);
    exit;
}

function uploadPdf(): void {
    $category = basename($_POST['category'] ?? '');

    if (empty($category)) {
        json_response(['error' => 'Kategori secin'], 400);
    }

    $dir = PDF_PATH . '/' . $category;
    if (!is_dir($dir)) {
        json_response(['error' => 'Kategori bulunamadi'], 404);
    }

    if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
        $errCode = $_FILES['pdf']['error'] ?? -1;
        json_response(['error' => 'Dosya yuklenemedi (hata kodu: ' . $errCode . ')'], 400);
    }

    $file = $_FILES['pdf'];

    // Validate size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        json_response(['error' => 'Dosya boyutu cok buyuk (max 50MB)'], 400);
    }

    // Validate extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        json_response(['error' => 'Sadece PDF dosyalari yuklenebilir'], 400);
    }

    // Validate MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if ($mime !== 'application/pdf') {
        json_response(['error' => 'Dosya gecerli bir PDF degil'], 400);
    }

    // Validate magic bytes
    $fp = fopen($file['tmp_name'], 'rb');
    $header = fread($fp, 4);
    fclose($fp);
    if ($header !== '%PDF') {
        json_response(['error' => 'Dosya gecerli bir PDF degil'], 400);
    }

    // Sanitize filename
    $filename = sanitize_filename($file['name']);
    if (!str_ends_with(strtolower($filename), '.pdf')) {
        $filename .= '.pdf';
    }

    // Avoid overwrite
    $target = $dir . '/' . $filename;
    $counter = 1;
    while (file_exists($target)) {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $target = $dir . '/' . $base . '_' . $counter . '.pdf';
        $counter++;
    }

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        json_response(['error' => 'Dosya kaydedilemedi'], 500);
    }

    json_response([
        'success' => true,
        'file' => basename($target),
        'size' => filesize($target),
    ], 201);
}

function deletePdf(): void {
    $category = basename($_GET['category'] ?? '');
    $file = basename($_GET['file'] ?? '');

    if (empty($category) || empty($file)) {
        json_response(['error' => 'Eksik parametre'], 400);
    }

    $path = PDF_PATH . '/' . $category . '/' . $file;

    if (!validate_path($path) || !file_exists($path)) {
        json_response(['error' => 'Dosya bulunamadi'], 404);
    }

    unlink($path);

    // Remove progress entry
    $progressData = read_json(PROGRESS_FILE);
    $key = $category . '/' . $file;
    unset($progressData['progress'][$key]);
    write_json(PROGRESS_FILE, $progressData);

    json_response(['success' => true]);
}

function movePdf(): void {
    $input = get_input();
    $fromCategory = basename($input['from_category'] ?? '');
    $toCategory = basename($input['to_category'] ?? '');
    $file = basename($input['file'] ?? '');

    if (empty($fromCategory) || empty($toCategory) || empty($file)) {
        json_response(['error' => 'Eksik parametre'], 400);
    }

    $srcPath = PDF_PATH . '/' . $fromCategory . '/' . $file;
    $dstPath = PDF_PATH . '/' . $toCategory . '/' . $file;

    if (!validate_path($srcPath) || !file_exists($srcPath)) {
        json_response(['error' => 'Kaynak dosya bulunamadi'], 404);
    }

    if (!is_dir(PDF_PATH . '/' . $toCategory)) {
        json_response(['error' => 'Hedef kategori bulunamadi'], 404);
    }

    // Avoid overwrite
    $counter = 1;
    while (file_exists($dstPath)) {
        $base = pathinfo($file, PATHINFO_FILENAME);
        $dstPath = PDF_PATH . '/' . $toCategory . '/' . $base . '_' . $counter . '.pdf';
        $counter++;
    }

    rename($srcPath, $dstPath);

    // Update progress key
    $progressData = read_json(PROGRESS_FILE);
    $oldKey = $fromCategory . '/' . $file;
    $newKey = $toCategory . '/' . basename($dstPath);
    if (isset($progressData['progress'][$oldKey])) {
        $progressData['progress'][$newKey] = $progressData['progress'][$oldKey];
        unset($progressData['progress'][$oldKey]);
        write_json(PROGRESS_FILE, $progressData);
    }

    json_response(['success' => true, 'file' => basename($dstPath)]);
}

function handleProgress(string $method): void {
    if ($method === 'GET') {
        $file = $_GET['file'] ?? '';
        if (empty($file)) {
            json_response(['error' => 'Dosya parametresi gerekli'], 400);
        }
        $data = read_json(PROGRESS_FILE);
        $progress = $data['progress'][$file] ?? null;
        json_response(['progress' => $progress]);
    } elseif ($method === 'POST') {
        $input = get_input();
        $file = $input['file'] ?? '';
        $page = (int)($input['page'] ?? 0);

        if (empty($file) || $page < 1) {
            json_response(['error' => 'Dosya ve sayfa numarasi gerekli'], 400);
        }

        $data = read_json(PROGRESS_FILE);
        if (!isset($data['progress'])) {
            $data['progress'] = [];
        }
        $data['progress'][$file] = [
            'page' => $page,
            'updated_at' => date('c'),
        ];
        write_json(PROGRESS_FILE, $data);
        json_response(['success' => true]);
    } else {
        json_response(['error' => 'Method not allowed'], 405);
    }
}
