import * as pdfjsLib from 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.9.155/build/pdf.min.mjs';

pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.9.155/build/pdf.worker.min.mjs';

const config = window.READER_CONFIG;
let pdfDoc = null;
let currentPage = 1;
let totalPages = 0;
let scale = 1.5;
let rendering = false;
let currentTextLayer = null;

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

        // Cancel previous text layer before rendering new one
        if (currentTextLayer) {
            currentTextLayer.cancel();
            currentTextLayer = null;
        }
        tl.innerHTML = '';

        // Render text layer using PDF.js official TextLayer API
        const textContent = await page.getTextContent();
        currentTextLayer = new pdfjsLib.TextLayer({
            textContentSource: textContent,
            container: tl,
            viewport: viewport,
        });
        await currentTextLayer.render();

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

// === Selection Action Bar (works on desktop, tablet, mobile) ===
let selectionBar = null;
let selectedText = '';

function isInTextLayer(node) {
    const tl = document.getElementById('text-layer');
    return tl && node && (tl.contains(node) || tl === node);
}

function handleSelection() {
    const selection = window.getSelection();
    const text = selection.toString().trim();

    if (!text) {
        hideSelectionBar();
        return;
    }

    // Check if either end of selection is in text layer
    if (!isInTextLayer(selection.anchorNode) && !isInTextLayer(selection.focusNode)) {
        return;
    }

    selectedText = text;
    showSelectionBar(selection);
}

function showSelectionBar(selection) {
    hideSelectionBar();
    const isMobile = window.innerWidth <= 768;

    selectionBar = document.createElement('div');
    selectionBar.className = 'selection-bar ' + (isMobile ? 'bottom-bar' : 'floating');

    // Build buttons based on selection length
    let buttons = '';

    // Translate - for short text (words/phrases)
    if (selectedText.length < 100) {
        buttons += `<button class="sel-btn" data-action="translate"><span class="ico">&#128269;</span> Cevir</button>`;
    }

    // Pronounce
    if (selectedText.length < 60) {
        buttons += `<button class="sel-btn" data-action="pronounce"><span class="ico">&#128264;</span> Telaffuz</button>`;
    }

    // Save Quote - for any text
    buttons += `<button class="sel-btn" data-action="quote"><span class="ico">&#128221;</span> Alintiyi Kaydet</button>`;

    // Copy
    buttons += `<button class="sel-btn" data-action="copy"><span class="ico">&#128203;</span> Kopyala</button>`;

    selectionBar.innerHTML = buttons;

    // Position
    if (!isMobile) {
        const range = selection.getRangeAt(0);
        const rects = range.getClientRects();
        // Use the first rect (top of selection) for positioning
        const firstRect = rects.length > 0 ? rects[0] : range.getBoundingClientRect();
        selectionBar.style.left = (firstRect.left + firstRect.width / 2) + 'px';
        selectionBar.style.top = Math.max(8, firstRect.top - 48) + 'px';
    }

    document.body.appendChild(selectionBar);

    // Action handlers
    selectionBar.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const action = btn.dataset.action;

        switch (action) {
            case 'translate':
                window.dispatchEvent(new CustomEvent('word-selected', { detail: { word: selectedText } }));
                if (isMobile) {
                    sidebar.classList.remove('collapsed');
                    sidebar.classList.add('open');
                }
                hideSelectionBar();
                break;

            case 'pronounce':
                if (window.pronounceWord) window.pronounceWord(selectedText);
                break;

            case 'quote':
                saveQuote(btn);
                break;

            case 'copy':
                navigator.clipboard.writeText(selectedText).then(() => {
                    btn.innerHTML = '<span class="ico">&#10003;</span> Kopyalandi';
                    setTimeout(hideSelectionBar, 1000);
                });
                break;
        }
    });
}

async function saveQuote(btn) {
    const fileKey = config.category + '/' + config.file;
    const fileName = config.file;
    btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;"></span>';

    try {
        const res = await fetch('/api/notes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                text: selectedText.substring(0, 2000),
                source_pdf: fileKey,
                source_page: currentPage,
                source_name: fileName,
            }),
        });
        const data = await res.json();
        if (res.ok) {
            btn.innerHTML = '<span class="ico">&#10003;</span> Kaydedildi';
            setTimeout(hideSelectionBar, 1500);
        } else {
            btn.innerHTML = '<span class="ico">&#128221;</span> ' + (data.error || 'Hata');
        }
    } catch {
        btn.innerHTML = '<span class="ico">&#128221;</span> Hata';
    }
}

function hideSelectionBar() {
    if (selectionBar) {
        selectionBar.remove();
        selectionBar = null;
    }
}

// Desktop: show on mouseup
document.addEventListener('mouseup', (e) => {
    // Don't trigger if clicking inside the selection bar itself
    if (selectionBar && selectionBar.contains(e.target)) return;
    // Small delay to let selection finalize
    setTimeout(handleSelection, 10);
});

// Mobile/Tablet: show on selectionchange (long-press triggers this)
let selChangeTimer = null;
document.addEventListener('selectionchange', () => {
    clearTimeout(selChangeTimer);
    selChangeTimer = setTimeout(() => {
        const selection = window.getSelection();
        const text = selection.toString().trim();
        if (text && text.length > 0 && window.innerWidth <= 768) {
            if (isInTextLayer(selection.anchorNode) || isInTextLayer(selection.focusNode)) {
                selectedText = text;
                showSelectionBar(selection);
            }
        }
    }, 400);
});

// Hide bar when clicking elsewhere
document.addEventListener('mousedown', (e) => {
    if (selectionBar && !selectionBar.contains(e.target)) {
        hideSelectionBar();
    }
});
document.addEventListener('touchstart', (e) => {
    if (selectionBar && !selectionBar.contains(e.target)) {
        hideSelectionBar();
    }
});

// Export for dictionary.js
window.readerGetCurrentPage = () => currentPage;
window.readerGetFileKey = () => config.category + '/' + config.file;

init();
