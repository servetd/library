<?php
require_once __DIR__ . '/includes/helpers.php';
$pageTitle = 'Kelime Defteri';
$currentPage = 'notebook';
$extraJs = ['/assets/js/notebook.js'];
require_once __DIR__ . '/includes/layout_header.php';
?>

<div class="page-header">
    <h1>Kelime Defteri</h1>
    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
        <div class="search-box">
            <input type="text" id="search-input" class="form-control" placeholder="Kelime ara...">
        </div>
        <select id="filter-mastered" class="form-control" style="width:auto;">
            <option value="">Tumu</option>
            <option value="0">Ogrenilecek</option>
            <option value="1">Ogrenildi</option>
        </select>
        <button class="btn btn-secondary btn-sm" id="btn-review">Tekrar Modu</button>
        <a href="/api/vocabulary.php?export=csv" class="btn btn-secondary btn-sm">CSV Indir</a>
    </div>
</div>

<!-- Word List View -->
<div id="word-list-view">
    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Ingilizce</th>
                        <th>Turkce</th>
                        <th>Baglamn</th>
                        <th>Kaynak</th>
                        <th>Durum</th>
                        <th>Islemler</th>
                    </tr>
                </thead>
                <tbody id="word-tbody"></tbody>
            </table>
        </div>
        <div id="word-empty" class="empty-state" style="display:none;">
            <div class="icon">&#128214;</div>
            <p>Kelime defteriniz bos. PDF okurken kelimeleri buraya ekleyebilirsiniz.</p>
        </div>
    </div>
    <p id="word-count" style="margin-top:0.5rem; font-size:0.85rem; color:var(--text-muted);"></p>
</div>

<!-- Review / Flashcard Mode -->
<div id="review-view" style="display:none;">
    <div style="text-align:center; margin-bottom:1rem;">
        <button class="btn btn-secondary btn-sm" id="btn-exit-review">Listeye Don</button>
        <span id="review-counter" style="margin-left:1rem; color:var(--text-muted);"></span>
    </div>
    <div class="flashcard" id="flashcard" onclick="flipCard()">
        <div class="flashcard-inner" id="flashcard-inner">
            <div class="flashcard-front" id="card-front"></div>
            <div class="flashcard-back" id="card-back"></div>
        </div>
    </div>
    <div style="text-align:center; margin-top:1rem; display:flex; gap:0.5rem; justify-content:center;">
        <button class="btn btn-secondary" id="btn-prev-card">&#9664; Onceki</button>
        <button class="btn btn-success" id="btn-mastered" style="min-width:120px;">Ogrendim</button>
        <button class="btn btn-primary" id="btn-next-card">Sonraki &#9654;</button>
    </div>
</div>

<?php require_once __DIR__ . '/includes/layout_footer.php'; ?>
