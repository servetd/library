<?php
require_once __DIR__ . '/includes/helpers.php';
$category = $_GET['category'] ?? '';
$file = $_GET['file'] ?? '';
$pageTitle = $file ?: 'PDF Okuyucu';
$currentPage = 'reader';
$extraCss = ['/assets/css/reader.css'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= SITE_TITLE ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pdfjs-dist@4.9.155/web/pdf_viewer.css">
    <link rel="stylesheet" href="/assets/css/reader.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="/" class="nav-logo"><?= SITE_TITLE ?></a>
            <div class="nav-links">
                <a href="/" class="nav-link">Kutuphane</a>
                <a href="/notebook.php" class="nav-link">Kelime Defteri</a>
                <button id="theme-toggle" class="btn-icon" title="Tema Degistir">&#9790;</button>
            </div>
            <button class="nav-hamburger" id="nav-hamburger" aria-label="Menu">&#9776;</button>
        </div>
    </nav>

    <div class="reader-wrapper">
        <!-- PDF Viewer -->
        <div class="pdf-viewer">
            <div class="pdf-toolbar">
                <a href="/" class="btn btn-sm btn-secondary">&#8592; Geri</a>
                <span class="pdf-title" id="pdf-title"><?= e($file) ?></span>
                <div class="zoom-controls">
                    <button class="btn-icon btn-sm" id="zoom-out" title="Kucult">-</button>
                    <span id="zoom-level">100%</span>
                    <button class="btn-icon btn-sm" id="zoom-in" title="Buyut">+</button>
                    <button class="btn-icon btn-sm" id="zoom-fit" title="Siğdır">&#8596;</button>
                </div>
                <div class="page-nav">
                    <button class="btn-icon btn-sm" id="prev-page" title="Onceki Sayfa">&#9664;</button>
                    <input type="number" id="page-input" min="1" value="1">
                    <span>/ <span id="total-pages">0</span></span>
                    <button class="btn-icon btn-sm" id="next-page" title="Sonraki Sayfa">&#9654;</button>
                </div>
                <button class="btn-icon btn-sm" id="toggle-sidebar" title="Sozluk Paneli">&#128214;</button>
            </div>
            <div class="pdf-container" id="pdf-container">
                <div class="pdf-page-wrapper" id="page-wrapper">
                    <canvas id="pdf-canvas"></canvas>
                    <div class="textLayer" id="text-layer"></div>
                </div>
            </div>
        </div>

        <!-- Dictionary Sidebar -->
        <div class="dict-sidebar" id="dict-sidebar">
            <div class="dict-header">
                <span>Sozluk</span>
                <button class="btn-icon btn-sm" id="close-sidebar">&#10005;</button>
            </div>
            <div class="dict-body">
                <div class="dict-search">
                    <input type="text" id="dict-input" placeholder="Kelime yazin..." autocomplete="off">
                    <button class="btn btn-primary btn-sm" id="dict-search-btn">Ara</button>
                </div>
                <div id="dict-result"></div>
                <div class="recent-lookups" id="recent-lookups" style="display:none;">
                    <h4>Son Aramalar</h4>
                    <div id="recent-list"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Theme & nav toggle (inline since we don't use layout_footer here)
        (function() {
            const btn = document.getElementById('theme-toggle');
            const saved = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', saved);
            btn.textContent = saved === 'dark' ? '\u2600' : '\u263E';
            btn.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-theme');
                const next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
                btn.textContent = next === 'dark' ? '\u2600' : '\u263E';
            });
            const hamburger = document.getElementById('nav-hamburger');
            const navLinks = document.querySelector('.nav-links');
            hamburger.addEventListener('click', () => navLinks.classList.toggle('open'));
        })();
    </script>
    <script>
        // Pass PHP vars to JS
        window.READER_CONFIG = {
            category: <?= json_encode($category) ?>,
            file: <?= json_encode($file) ?>,
            pdfUrl: `/api/books.php?serve&category=${encodeURIComponent(<?= json_encode($category) ?>)}&file=${encodeURIComponent(<?= json_encode($file) ?>)}`,
        };
    </script>
    <script type="module" src="/assets/js/reader.js"></script>
    <script type="module" src="/assets/js/dictionary.js"></script>
</body>
</html>
