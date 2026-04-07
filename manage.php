<?php
require_once __DIR__ . '/includes/helpers.php';
$pageTitle = 'Yonetim';
$currentPage = 'manage';
$extraJs = ['/assets/js/manage.js'];
require_once __DIR__ . '/includes/layout_header.php';
?>

<div class="page-header">
    <h1>Kutuphane Yonetimi</h1>
</div>

<!-- Categories Section -->
<section class="card" style="margin-bottom: 1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h2>Kategoriler</h2>
        <button class="btn btn-primary" onclick="openCategoryModal()">+ Yeni Kategori</button>
    </div>
    <div id="categories-list"></div>
</section>

<!-- PDF Upload Section -->
<section class="card">
    <h2 style="margin-bottom:1rem;">PDF Yukle</h2>
    <div class="form-group">
        <label for="upload-category">Kategori Secin</label>
        <select id="upload-category" class="form-control">
            <option value="">-- Kategori Secin --</option>
        </select>
    </div>
    <div class="upload-zone" id="upload-zone">
        <p>PDF dosyasini surukleyip birakin veya tiklayarak secin</p>
        <p style="font-size:0.8rem; margin-top:0.5rem;">Maksimum 50MB</p>
        <input type="file" id="file-input" accept=".pdf" style="display:none" multiple>
    </div>
    <div id="upload-progress" style="margin-top:1rem;"></div>
</section>

<!-- PDF Management Section -->
<section class="card" style="margin-top:1.5rem;">
    <h2 style="margin-bottom:1rem;">PDF Dosyalari</h2>
    <div class="form-group">
        <select id="pdf-category-filter" class="form-control">
            <option value="">-- Tum Kategoriler --</option>
        </select>
    </div>
    <div id="pdf-list"></div>
</section>

<!-- Category Modal -->
<div class="modal-overlay" id="category-modal">
    <div class="modal">
        <h2 id="modal-title">Yeni Kategori</h2>
        <input type="hidden" id="edit-category-id">
        <div class="form-group">
            <label>Kategori Adi</label>
            <input type="text" id="category-name" class="form-control" placeholder="ornek: Programlama">
        </div>
        <div class="form-group">
            <label>Aciklama</label>
            <input type="text" id="category-desc" class="form-control" placeholder="Kisa aciklama">
        </div>
        <div class="form-group">
            <label>Renk</label>
            <input type="color" id="category-color" class="form-control" value="#2563eb" style="height:40px; padding:4px;">
        </div>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeCategoryModal()">Iptal</button>
            <button class="btn btn-primary" onclick="saveCategory()">Kaydet</button>
        </div>
    </div>
</div>

<!-- Move PDF Modal -->
<div class="modal-overlay" id="move-modal">
    <div class="modal">
        <h2>PDF Tasi</h2>
        <input type="hidden" id="move-file">
        <input type="hidden" id="move-from">
        <div class="form-group">
            <label>Hedef Kategori</label>
            <select id="move-to" class="form-control"></select>
        </div>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeMoveModal()">Iptal</button>
            <button class="btn btn-primary" onclick="confirmMove()">Tasi</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/layout_footer.php'; ?>
