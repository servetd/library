<?php

require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $data = read_json(CATEGORIES_FILE);
        $categories = $data['categories'] ?? [];
        // Add PDF count per category
        foreach ($categories as &$cat) {
            $dir = PDF_PATH . '/' . $cat['slug'];
            $cat['pdf_count'] = 0;
            if (is_dir($dir)) {
                $files = glob($dir . '/*.pdf');
                $cat['pdf_count'] = $files ? count($files) : 0;
            }
        }
        json_response(['categories' => $categories]);
        break;

    case 'POST':
        $input = get_input();
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $color = trim($input['color'] ?? '#2563eb');

        if (empty($name)) {
            json_response(['error' => 'Kategori adi gerekli'], 400);
        }

        $slug = slugify($name);
        if (empty($slug)) {
            json_response(['error' => 'Gecersiz kategori adi'], 400);
        }

        $data = read_json(CATEGORIES_FILE);
        $categories = $data['categories'] ?? [];

        // Check uniqueness
        foreach ($categories as $cat) {
            if ($cat['slug'] === $slug) {
                json_response(['error' => 'Bu kategori zaten mevcut'], 409);
            }
        }

        // Create directory
        $dir = PDF_PATH . '/' . $slug;
        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            json_response(['error' => 'Klasor olusturulamadi'], 500);
        }

        $category = [
            'id' => generate_id('cat-'),
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'color' => $color,
            'created_at' => date('c'),
        ];

        $categories[] = $category;
        write_json(CATEGORIES_FILE, ['categories' => $categories]);
        json_response(['category' => $category], 201);
        break;

    case 'PUT':
        $input = get_input();
        $id = $input['id'] ?? '';

        if (empty($id)) {
            json_response(['error' => 'Kategori ID gerekli'], 400);
        }

        $data = read_json(CATEGORIES_FILE);
        $categories = $data['categories'] ?? [];
        $found = false;

        foreach ($categories as &$cat) {
            if ($cat['id'] === $id) {
                $found = true;
                $newName = trim($input['name'] ?? $cat['name']);
                $newSlug = slugify($newName);
                $oldSlug = $cat['slug'];

                // If slug changed, rename directory
                if ($newSlug !== $oldSlug) {
                    // Check uniqueness
                    foreach ($categories as $other) {
                        if ($other['id'] !== $id && $other['slug'] === $newSlug) {
                            json_response(['error' => 'Bu kategori adi zaten kullaniliyor'], 409);
                        }
                    }
                    $oldDir = PDF_PATH . '/' . $oldSlug;
                    $newDir = PDF_PATH . '/' . $newSlug;
                    if (is_dir($oldDir)) {
                        rename($oldDir, $newDir);
                    }
                    $cat['slug'] = $newSlug;
                }

                $cat['name'] = $newName;
                if (isset($input['description'])) $cat['description'] = trim($input['description']);
                if (isset($input['color'])) $cat['color'] = trim($input['color']);
                break;
            }
        }

        if (!$found) {
            json_response(['error' => 'Kategori bulunamadi'], 404);
        }

        write_json(CATEGORIES_FILE, ['categories' => $categories]);
        json_response(['success' => true]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            json_response(['error' => 'Kategori ID gerekli'], 400);
        }

        $data = read_json(CATEGORIES_FILE);
        $categories = $data['categories'] ?? [];
        $filtered = [];
        $deleted = null;

        foreach ($categories as $cat) {
            if ($cat['id'] === $id) {
                $deleted = $cat;
            } else {
                $filtered[] = $cat;
            }
        }

        if (!$deleted) {
            json_response(['error' => 'Kategori bulunamadi'], 404);
        }

        // Check if directory has PDFs
        $dir = PDF_PATH . '/' . $deleted['slug'];
        if (is_dir($dir)) {
            $files = glob($dir . '/*.pdf');
            if ($files && count($files) > 0) {
                json_response(['error' => 'Kategori icinde PDF dosyalari var. Once dosyalari silin veya tasyin.'], 400);
            }
            rmdir($dir);
        }

        write_json(CATEGORIES_FILE, ['categories' => $filtered]);
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'Method not allowed'], 405);
}
