import * as pdfjsLib from 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.9.155/build/pdf.min.mjs';

pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.9.155/build/pdf.worker.min.mjs';

const config = window.READER_CONFIG;
let pdfDoc = null;
let currentPage = 1;
let totalPages = 0;
let scale = 1.5;
let rendering = false;

const canvas = document.getElementById('pdf-canvas');
const ctx = canvas.getContext('2d');
const textLayer = document.getElementById('text-layer');
const pageInput = document.getElementById('page-input');
const totalPagesEl = document.getElementById('total-pages');
const zoomLevel = document.getElementById('zoom-level');
const container = document.getElementById('pdf-container');

// === Initialize ===
async function init() {
    if (!config.category || !config.file) {
        container.innerHTML = '<div class="empty-state"><p>PDF dosyasi belirtilmedi.</p></div>';
        return;
    }

    container.innerHTML = '<div class="empty-state"><div class="spinner" style="width:40px;height:40px;"></div><p style="margin-top:1rem;">PDF yukleniyor...</p></div>';

    try {
        pdfDoc = await pdfjsLib.getDocument(config.pdfUrl).promise;
        totalPages = pdfDoc.numPages;
        totalPagesEl.textContent = totalPages;
        pageInput.max = totalPages;

        // Restore wrapper
        container.innerHTML = '';
        const wrapper = document.createElement('div');
        wrapper.id = 'page-wrapper';
        wrapper.className = 'pdf-page-wrapper';
        const c = document.createElement('canvas');
        c.id = 'pdf-canvas';
        const tl = document.createElement('div');
        tl.className = 'textLayer';
        tl.id = 'text-layer';
        wrapper.appendChild(c);
        wrapper.appendChild(tl);
        container.appendChild(wrapper);

        // Re-assign references
        Object.defineProperty(window, '_pdfCanvas', { value: c, writable: true });
        Object.defineProperty(window, '_textLayer', { value: tl, writable: true });

        // Load saved progress
        const savedPage = await loadProgress();
        if (savedPage && savedPage > 0 && savedPage <= totalPages) {
            currentPage = savedPage;
        }
        pageInput.value = currentPage;

        await renderPage(currentPage);
    } catch (err) {
        console.error('PDF load error:', err);
        container.innerHTML = `<div class="empty-state"><p>PDF yuklenemedi: ${err.message}</p></div>`;
    }
}

// === Render Page ===
async function renderPage(num) {
    if (rendering) return;
    rendering = true;

    const c = document.getElementById('pdf-canvas');
    const tl = document.getElementById('text-layer');
    if (!c || !tl) { rendering = false; return; }

    const ctx2 = c.getContext('2d');

    try {
        const page = await pdfDoc.getPage(num);
        const viewport = page.getViewport({ scale });

        c.width = viewport.width;
        c.height = viewport.height;

        const wrapper = c.parentElement;
        wrapper.style.width = viewport.width + 'px';
        wrapper.style.height = viewport.height + 'px';

        await page.render({ canvasContext: ctx2, viewport }).promise;

        // Text layer using PDF.js official TextLayer API
        tl.innerHTML = '';
        tl.style.width = viewport.width + 'px';
        tl.style.height = viewport.height + 'px';

        const textContent = await page.getTextContent();
        const textLayerObj = new pdfjsLib.TextLayer({
            textContentSource: textContent,
            container: tl,
            viewport: viewport,
        });
        await textLayerObj.render();

        currentPage = num;
        pageInput.value = num;

        // Save progress
        saveProgress(num);
    } catch (err) {
        console.error('Render error:', err);
    }

    rendering = false;
}

// === Progress ===
async function loadProgress() {
    try {
        const key = config.category + '/' + config.file;
        const res = await fetch(`/api/books.php?progress&file=${encodeURIComponent(key)}`);
        const data = await res.json();
        return data.progress?.page || null;
    } catch {
        return null;
    }
}

let saveTimeout = null;
function saveProgress(page) {
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(async () => {
        try {
            const key = config.category + '/' + config.file;
            await fetch('/api/books.php?progress', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ file: key, page }),
            });
        } catch { /* silent */ }
    }, 1000);
}

// === Navigation ===
document.getElementById('prev-page').addEventListener('click', () => {
    if (currentPage > 1) renderPage(currentPage - 1);
});

document.getElementById('next-page').addEventListener('click', () => {
    if (currentPage < totalPages) renderPage(currentPage + 1);
});

pageInput.addEventListener('change', () => {
    const num = parseInt(pageInput.value);
    if (num >= 1 && num <= totalPages) renderPage(num);
    else pageInput.value = currentPage;
});

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
    if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
        e.preventDefault();
        if (currentPage > 1) renderPage(currentPage - 1);
    } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
        e.preventDefault();
        if (currentPage < totalPages) renderPage(currentPage + 1);
    }
});

// === Zoom ===
document.getElementById('zoom-in').addEventListener('click', () => {
    scale = Math.min(scale + 0.25, 4);
    zoomLevel.textContent = Math.round(scale / 1.5 * 100) + '%';
    renderPage(currentPage);
});

document.getElementById('zoom-out').addEventListener('click', () => {
    scale = Math.max(scale - 0.25, 0.5);
    zoomLevel.textContent = Math.round(scale / 1.5 * 100) + '%';
    renderPage(currentPage);
});

document.getElementById('zoom-fit').addEventListener('click', () => {
    const containerWidth = container.clientWidth - 40;
    if (pdfDoc) {
        pdfDoc.getPage(currentPage).then(page => {
            const viewport = page.getViewport({ scale: 1 });
            scale = containerWidth / viewport.width;
            zoomLevel.textContent = Math.round(scale / 1.5 * 100) + '%';
            renderPage(currentPage);
        });
    }
});

// === Sidebar Toggle ===
const sidebar = document.getElementById('dict-sidebar');
document.getElementById('toggle-sidebar').addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('open');
    }
});
document.getElementById('close-sidebar').addEventListener('click', () => {
    sidebar.classList.add('collapsed');
    if (window.innerWidth <= 768) {
        sidebar.classList.remove('open');
    }
});

// === Text Selection → Dictionary ===
document.addEventListener('mouseup', () => {
    const selection = window.getSelection();
    const text = selection.toString().trim();
    if (text && text.length > 0 && text.length < 100) {
        // Check if selection is within the text layer
        const anchor = selection.anchorNode;
        if (anchor && document.getElementById('text-layer')?.contains(anchor)) {
            window.dispatchEvent(new CustomEvent('word-selected', { detail: { word: text } }));
            // Open sidebar on mobile
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('collapsed');
                sidebar.classList.add('open');
            }
        }
    }
});

// Export for dictionary.js
window.readerGetCurrentPage = () => currentPage;
window.readerGetFileKey = () => config.category + '/' + config.file;

init();
