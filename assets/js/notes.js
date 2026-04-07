let allNotes = [];
let sources = [];

document.addEventListener('DOMContentLoaded', () => {
    loadNotes();
    document.getElementById('search-input').addEventListener('input', debounce(loadNotes, 300));
    document.getElementById('filter-source').addEventListener('change', loadNotes);
});

async function loadNotes() {
    const search = document.getElementById('search-input').value.trim();
    const source = document.getElementById('filter-source').value;

    let url = '/api/notes.php?';
    if (search) url += `search=${encodeURIComponent(search)}&`;
    if (source) url += `source=${encodeURIComponent(source)}&`;

    const res = await fetch(url);
    const data = await res.json();
    allNotes = data.notes || [];

    // Build source filter options
    updateSourceFilter();
    renderNotes();
}

function updateSourceFilter() {
    const sel = document.getElementById('filter-source');
    const current = sel.value;

    // Collect unique sources
    const uniqueSources = [...new Set(allNotes.map(n => n.source_pdf).filter(Boolean))];

    // Keep first option, rebuild rest
    const firstOpt = sel.options[0];
    sel.innerHTML = '';
    sel.appendChild(firstOpt);

    uniqueSources.forEach(src => {
        const opt = document.createElement('option');
        opt.value = src;
        // Show friendly name: "category/file.pdf" → "file.pdf"
        opt.textContent = src.split('/').pop() || src;
        sel.appendChild(opt);
    });

    sel.value = current;
}

function renderNotes() {
    const container = document.getElementById('notes-list');
    const empty = document.getElementById('notes-empty');
    const count = document.getElementById('notes-count');

    if (allNotes.length === 0) {
        container.innerHTML = '';
        empty.style.display = '';
        count.textContent = '';
        return;
    }

    empty.style.display = 'none';
    count.textContent = `Toplam ${allNotes.length} alinti`;

    container.innerHTML = allNotes.map(n => {
        const date = new Date(n.created_at).toLocaleDateString('tr-TR');
        const fileName = n.source_name || (n.source_pdf ? n.source_pdf.split('/').pop() : '');
        const citation = fileName
            ? `— <em>${escHtml(fileName)}</em>, Sayfa ${n.source_page || '?'}`
            : '';
        const commentHtml = n.comment
            ? `<div class="note-comment"><strong>Not:</strong> ${escHtml(n.comment)}</div>`
            : '';
        const category = n.source_pdf ? n.source_pdf.split('/')[0] : '';
        const file = n.source_pdf ? n.source_pdf.split('/')[1] || '' : '';
        const readerLink = n.source_pdf
            ? `/reader.php?category=${encodeURIComponent(category)}&file=${encodeURIComponent(file)}`
            : '';

        return `
        <div class="card note-card" style="margin-bottom:1rem;">
            <blockquote class="note-text">"${escHtml(n.text)}"</blockquote>
            <div class="note-citation">
                ${readerLink ? `<a href="${readerLink}" title="PDF'de ac">${citation}</a>` : citation}
                <span class="note-date">${date}</span>
            </div>
            ${commentHtml}
            <div class="note-actions">
                <button class="btn btn-sm btn-secondary" onclick="copyNote('${n.id}')">Kopyala</button>
                <button class="btn btn-sm btn-secondary" onclick="openEditModal('${n.id}')">Duzenle</button>
                <button class="btn btn-sm btn-danger" onclick="deleteNote('${n.id}')">Sil</button>
            </div>
        </div>`;
    }).join('');
}

// Copy note with citation to clipboard
window.copyNote = function(id) {
    const note = allNotes.find(n => n.id === id);
    if (!note) return;

    const fileName = note.source_name || (note.source_pdf ? note.source_pdf.split('/').pop() : '');
    const citation = fileName ? `\n— ${fileName}, Sayfa ${note.source_page || '?'}` : '';
    const text = `"${note.text}"${citation}`;

    navigator.clipboard.writeText(text).then(() => {
        const btn = document.querySelector(`[onclick="copyNote('${id}')"]`);
        if (btn) {
            btn.textContent = 'Kopyalandi!';
            setTimeout(() => { btn.textContent = 'Kopyala'; }, 2000);
        }
    });
};

// Edit modal
window.openEditModal = function(id) {
    const note = allNotes.find(n => n.id === id);
    if (!note) return;
    document.getElementById('edit-note-id').value = id;
    document.getElementById('edit-text').value = note.text;
    document.getElementById('edit-comment').value = note.comment || '';
    document.getElementById('edit-modal').classList.add('active');
};

window.closeEditModal = function() {
    document.getElementById('edit-modal').classList.remove('active');
};

window.saveEdit = async function() {
    const id = document.getElementById('edit-note-id').value;
    const text = document.getElementById('edit-text').value.trim();
    const comment = document.getElementById('edit-comment').value.trim();

    if (!text) return alert('Alinti metni bos olamaz');

    await fetch('/api/notes.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, text, comment }),
    });

    closeEditModal();
    loadNotes();
};

window.deleteNote = async function(id) {
    if (!confirm('Bu alintiyi silmek istediginize emin misiniz?')) return;
    await fetch(`/api/notes.php?id=${id}`, { method: 'DELETE' });
    loadNotes();
};

// === Helpers ===

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
}

function escAttr(s) {
    return (s || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

function debounce(fn, ms) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}
