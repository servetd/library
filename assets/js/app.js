let allCategories = [];
let allPdfs = [];

document.addEventListener('DOMContentLoaded', () => {
    loadHome();
    document.getElementById('search-input').addEventListener('input', debounce(handleSearch, 300));
});

async function loadHome() {
    // Load categories and all PDFs in parallel
    const [catRes, pdfRes] = await Promise.all([
        fetch('/api/categories.php'),
        fetch('/api/books.php'),
    ]);
    const catData = await catRes.json();
    const pdfData = await pdfRes.json();

    allCategories = catData.categories || [];
    allPdfs = pdfData.pdfs || [];

    renderCategories();
}

function renderCategories() {
    const grid = document.getElementById('categories-grid');

    if (allCategories.length === 0) {
        grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1;">
            <div class="icon">&#128218;</div>
            <p>Henuz kategori yok.</p>
            <a href="/manage.php" class="btn btn-primary">Kategori Olustur</a>
        </div>`;
        return;
    }

    grid.innerHTML = allCategories.map(cat => `
        <div class="card category-card" style="border-left-color:${cat.color}" onclick="showCategory('${cat.slug}', '${escHtml(cat.name)}')">
            <h3>${escHtml(cat.name)}</h3>
            <p class="card-meta">${escHtml(cat.description || '')}</p>
            <p class="card-meta" style="margin-top:0.5rem;">${cat.pdf_count} PDF</p>
        </div>
    `).join('');
}

async function showCategory(slug, name) {
    document.getElementById('categories-grid').style.display = 'none';
    document.getElementById('search-results').style.display = 'none';
    document.getElementById('pdf-section').style.display = 'block';
    document.getElementById('pdf-section-title').textContent = name;

    const res = await fetch(`/api/books.php?category=${slug}`);
    const data = await res.json();
    const pdfs = data.pdfs || [];

    renderPdfList(pdfs, slug, 'pdf-list');
}

function showAllCategories() {
    document.getElementById('pdf-section').style.display = 'none';
    document.getElementById('categories-grid').style.display = '';
}

function renderPdfList(pdfs, category, containerId) {
    const container = document.getElementById(containerId);

    if (pdfs.length === 0) {
        container.innerHTML = '<li class="empty-state"><p>Bu kategoride PDF yok.</p></li>';
        return;
    }

    container.innerHTML = pdfs.map(pdf => {
        const cat = pdf.category || category;
        const sizeMb = (pdf.size / 1024 / 1024).toFixed(1);
        const progressHtml = pdf.last_page
            ? `<span class="pdf-progress">Sayfa ${pdf.last_page}'de kaldiniz</span>`
            : '';
        const catLabel = pdf.category_name
            ? `<span class="badge badge-primary">${escHtml(pdf.category_name)}</span> `
            : '';

        return `
        <li class="pdf-item" style="cursor:pointer" onclick="openReader('${escAttr(cat)}', '${escAttr(pdf.name)}')">
            <span class="pdf-icon">&#128196;</span>
            <div class="pdf-info">
                <span class="pdf-name">${catLabel}${escHtml(pdf.name)}</span>
                <span class="pdf-meta">${sizeMb} MB</span>
            </div>
            ${progressHtml}
        </li>`;
    }).join('');
}

function openReader(category, file) {
    window.location.href = `/reader.php?category=${encodeURIComponent(category)}&file=${encodeURIComponent(file)}`;
}

function handleSearch() {
    const query = document.getElementById('search-input').value.trim().toLowerCase();

    if (!query) {
        clearSearch();
        return;
    }

    const results = allPdfs.filter(pdf =>
        pdf.name.toLowerCase().includes(query) ||
        (pdf.category_name || '').toLowerCase().includes(query)
    );

    document.getElementById('categories-grid').style.display = 'none';
    document.getElementById('pdf-section').style.display = 'none';
    document.getElementById('search-results').style.display = 'block';

    renderPdfList(results, '', 'search-list');
}

function clearSearch() {
    document.getElementById('search-input').value = '';
    document.getElementById('search-results').style.display = 'none';
    document.getElementById('pdf-section').style.display = 'none';
    document.getElementById('categories-grid').style.display = '';
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

function debounce(fn, ms) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
    };
}
