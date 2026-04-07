<?php
require_once __DIR__ . '/includes/helpers.php';
$pageTitle = 'Kutuphane';
$currentPage = 'home';
$extraJs = ['/assets/js/app.js'];
require_once __DIR__ . '/includes/layout_header.php';
?>

<div class="page-header">
    <h1>Kutuphanem</h1>
    <div class="search-box">
        <input type="text" id="search-input" class="form-control" placeholder="PDF ara...">
    </div>
</div>

<div id="categories-grid" class="card-grid"></div>

<div id="pdf-section" style="display:none; margin-top:1.5rem;">
    <div class="page-header">
        <h2 id="pdf-section-title"></h2>
        <button class="btn btn-secondary btn-sm" onclick="showAllCategories()">Tum Kategoriler</button>
    </div>
    <div class="card">
        <ul class="pdf-list" id="pdf-list"></ul>
    </div>
</div>

<div id="search-results" style="display:none; margin-top:1.5rem;">
    <div class="page-header">
        <h2>Arama Sonuclari</h2>
        <button class="btn btn-secondary btn-sm" onclick="clearSearch()">Temizle</button>
    </div>
    <div class="card">
        <ul class="pdf-list" id="search-list"></ul>
    </div>
</div>

<?php require_once __DIR__ . '/includes/layout_footer.php'; ?>
