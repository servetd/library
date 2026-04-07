<?php
require_once __DIR__ . '/includes/helpers.php';
$pageTitle = 'Alinti Defteri';
$currentPage = 'notes';
$extraJs = ['/assets/js/notes.js'];
require_once __DIR__ . '/includes/layout_header.php';
?>

<div class="page-header">
    <h1>Alinti Defteri</h1>
    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
        <div class="search-box">
            <input type="text" id="search-input" class="form-control" placeholder="Alinti ara...">
        </div>
        <select id="filter-source" class="form-control" style="width:auto; max-width:200px;">
            <option value="">Tum Kaynaklar</option>
        </select>
    </div>
</div>

<div id="notes-list"></div>

<div id="notes-empty" class="empty-state" style="display:none;">
    <div class="icon">&#128221;</div>
    <p>Alinti defteriniz bos. PDF okurken onemli cumleleri secip kaydedin.</p>
</div>

<p id="notes-count" style="margin-top:0.5rem; font-size:0.85rem; color:var(--text-muted);"></p>

<!-- Edit Modal -->
<div class="modal-overlay" id="edit-modal">
    <div class="modal">
        <h2>Alintiyi Duzenle</h2>
        <input type="hidden" id="edit-note-id">
        <div class="form-group">
            <label>Alinti Metni</label>
            <textarea id="edit-text" class="form-control" rows="4"></textarea>
        </div>
        <div class="form-group">
            <label>Notunuz</label>
            <textarea id="edit-comment" class="form-control" rows="2" placeholder="Kendi notunuzu ekleyin..."></textarea>
        </div>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeEditModal()">Iptal</button>
            <button class="btn btn-primary" onclick="saveEdit()">Kaydet</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/layout_footer.php'; ?>
