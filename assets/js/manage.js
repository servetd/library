document.addEventListener('DOMContentLoaded', () => {
    loadCategories();
    setupUpload();
});

// === Categories ===

async function loadCategories() {
    const res = await fetch('/api/categories.php');
    const data = await res.json();
    const categories = data.categories || [];

    // Render categories list
    const container = document.getElementById('categories-list');
    if (categories.length === 0) {
        container.innerHTML = '<div class="empty-state"><p>Henuz kategori yok. Yeni bir kategori olusturun.</p></div>';
    } else {
        container.innerHTML = categories.map(cat => `
            <div class="pdf-item">
                <span style="width:16px;height:16px;border-radius:50%;background:${cat.color};flex-shrink:0;display:inline-block;"></span>
                <div class="pdf-info">
                    <span class="pdf-name">${escHtml(cat.name)}</span>
                    <span class="pdf-meta">${escHtml(cat.description || '')} &middot; ${cat.pdf_count} PDF</span>
                </div>
                <button class="btn btn-sm btn-secondary" onclick="editCategory('${cat.id}', '${escAttr(cat.name)}', '${escAttr(cat.description || '')}', '${cat.color}')">Duzenle</button>
                <button class="btn btn-sm btn-danger" onclick="deleteCategory('${cat.id}', '${escAttr(cat.name)}')">Sil</button>
            </div>
        `).join('');
    }

    // Populate select dropdowns
    const selects = ['upload-category', 'pdf-category-filter', 'move-to'];
    selects.forEach(id => {
        const sel = document.getElementById(id);
        if (!sel) return;
        const firstOpt = sel.options[0];
        sel.innerHTML = '';
        sel.appendChild(firstOpt);
        categories.forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat.slug;
            opt.textContent = cat.name;
            sel.appendChild(opt);
        });
    });

    // Load PDFs if filter set
    loadPdfs();
}

function openCategoryModal(id, name, desc, color) {
    document.getElementById('edit-category-id').value = id || '';
    document.getElementById('category-name').value = name || '';
    document.getElementById('category-desc').value = desc || '';
    document.getElementById('category-color').value = color || '#2563eb';
    document.getElementById('modal-title').textContent = id ? 'Kategori Duzenle' : 'Yeni Kategori';
    document.getElementById('category-modal').classList.add('active');
}

function closeCategoryModal() {
    document.getElementById('category-modal').classList.remove('active');
}

function editCategory(id, name, desc, color) {
    openCategoryModal(id, name, desc, color);
}

async function saveCategory() {
    const id = document.getElementById('edit-category-id').value;
    const name = document.getElementById('category-name').value.trim();
    const description = document.getElementById('category-desc').value.trim();
    const color = document.getElementById('category-color').value;

    if (!name) return alert('Kategori adi gerekli');

    const method = id ? 'PUT' : 'POST';
    const body = { name, description, color };
    if (id) body.id = id;

    const res = await fetch('/api/categories.php', {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    });

    const data = await res.json();
    if (!res.ok) return alert(data.error || 'Hata olustu');

    closeCategoryModal();
    loadCategories();
}

async function deleteCategory(id, name) {
    if (!confirm(`"${name}" kategorisini silmek istediginize emin misiniz?`)) return;

    const res = await fetch(`/api/categories.php?id=${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (!res.ok) return alert(data.error || 'Hata olustu');

    loadCategories();
}

// === PDF Upload ===

function setupUpload() {
    const zone = document.getElementById('upload-zone');
    const input = document.getElementById('file-input');

    zone.addEventListener('click', () => input.click());
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });
    input.addEventListener('change', () => handleFiles(input.files));
}

function handleFiles(files) {
    const category = document.getElementById('upload-category').value;
    if (!category) return alert('Lutfen once bir kategori secin');

    Array.from(files).forEach(file => {
        if (file.type !== 'application/pdf') {
            alert(`${file.name} PDF degil, atlanıyor.`);
            return;
        }
        uploadFile(file, category);
    });
}

async function uploadFile(file, category) {
    const progress = document.getElementById('upload-progress');
    const id = 'upload-' + Date.now();
    progress.innerHTML += `<div id="${id}" class="alert" style="background:var(--primary-light);">
        <span class="spinner"></span> ${escHtml(file.name)} yukleniyor...
    </div>`;

    const formData = new FormData();
    formData.append('pdf', file);
    formData.append('category', category);

    try {
        const res = await fetch('/api/books.php', { method: 'POST', body: formData });
        const data = await res.json();
        const el = document.getElementById(id);

        if (res.ok) {
            el.className = 'alert alert-success';
            el.innerHTML = `${escHtml(data.file)} yuklendi!`;
            loadPdfs();
        } else {
            el.className = 'alert alert-danger';
            el.innerHTML = data.error || 'Yukleme hatasi';
        }
    } catch {
        const el = document.getElementById(id);
        if (el) {
            el.className = 'alert alert-danger';
            el.innerHTML = 'Baglanti hatasi';
        }
    }

    // Clear after 5s
    setTimeout(() => {
        const el = document.getElementById(id);
        if (el) el.remove();
    }, 5000);
}

// === PDF List ===

async function loadPdfs() {
    const category = document.getElementById('pdf-category-filter').value;
    const url = category ? `/api/books.php?category=${category}` : '/api/books.php';

    const res = await fetch(url);
    const data = await res.json();
    const pdfs = data.pdfs || [];
    const container = document.getElementById('pdf-list');

    if (pdfs.length === 0) {
        container.innerHTML = '<div class="empty-state"><p>Bu kategoride PDF yok.</p></div>';
        return;
    }

    container.innerHTML = pdfs.map(pdf => {
        const cat = pdf.category || category;
        const sizeMb = (pdf.size / 1024 / 1024).toFixed(1);
        return `
        <div class="pdf-item">
            <span class="pdf-icon">&#128196;</span>
            <div class="pdf-info">
                <span class="pdf-name">${escHtml(pdf.name)}</span>
                <span class="pdf-meta">${pdf.category_name ? escHtml(pdf.category_name) + ' &middot; ' : ''}${sizeMb} MB</span>
            </div>
            <button class="btn btn-sm btn-secondary" onclick="openMoveModal('${escAttr(pdf.name)}', '${escAttr(cat)}')">Tasi</button>
            <button class="btn btn-sm btn-danger" onclick="deletePdf('${escAttr(cat)}', '${escAttr(pdf.name)}')">Sil</button>
        </div>`;
    }).join('');
}

document.getElementById('pdf-category-filter').addEventListener('change', loadPdfs);

async function deletePdf(category, file) {
    if (!confirm(`"${file}" dosyasini silmek istediginize emin misiniz?`)) return;

    const res = await fetch(`/api/books.php?category=${encodeURIComponent(category)}&file=${encodeURIComponent(file)}`, { method: 'DELETE' });
    const data = await res.json();
    if (!res.ok) return alert(data.error || 'Hata olustu');

    loadPdfs();
}

// === Move Modal ===

function openMoveModal(file, fromCategory) {
    document.getElementById('move-file').value = file;
    document.getElementById('move-from').value = fromCategory;
    document.getElementById('move-modal').classList.add('active');
}

function closeMoveModal() {
    document.getElementById('move-modal').classList.remove('active');
}

async function confirmMove() {
    const file = document.getElementById('move-file').value;
    const from = document.getElementById('move-from').value;
    const to = document.getElementById('move-to').value;

    if (!to) return alert('Hedef kategori secin');
    if (from === to) return alert('Ayni kategoriyi sectiniz');

    const res = await fetch('/api/books.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ file, from_category: from, to_category: to }),
    });

    const data = await res.json();
    if (!res.ok) return alert(data.error || 'Hata olustu');

    closeMoveModal();
    loadPdfs();
}

// === Helpers ===

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function escAttr(s) {
    return s.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}
