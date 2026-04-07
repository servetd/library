const dictInput = document.getElementById('dict-input');
const dictResult = document.getElementById('dict-result');
const searchBtn = document.getElementById('dict-search-btn');
const recentLookups = document.getElementById('recent-lookups');
const recentList = document.getElementById('recent-list');

let recentWords = [];

// Listen for word selection from PDF
window.addEventListener('word-selected', (e) => {
    const word = e.detail.word;
    dictInput.value = word;
    lookupWord(word);
});

// Manual search
searchBtn.addEventListener('click', () => {
    const word = dictInput.value.trim();
    if (word) lookupWord(word);
});

dictInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        const word = dictInput.value.trim();
        if (word) lookupWord(word);
    }
});

async function lookupWord(word) {
    dictResult.innerHTML = '<div style="text-align:center;padding:1rem;"><div class="spinner"></div></div>';

    try {
        const res = await fetch(`/api/translate.php?word=${encodeURIComponent(word)}&from=en&to=tr`);
        const data = await res.json();

        if (!res.ok) {
            dictResult.innerHTML = `<div class="alert alert-danger">${escHtml(data.error || 'Hata olustu')}</div>`;
            return;
        }

        let matchesHtml = '';
        if (data.matches && data.matches.length > 0) {
            const uniqueTranslations = [...new Set(data.matches.map(m => m.translation).filter(Boolean))];
            if (uniqueTranslations.length > 1) {
                matchesHtml = `
                    <div style="margin-top:0.5rem; font-size:0.85rem; color:var(--text-muted);">
                        <strong>Diger anlamlar:</strong><br>
                        ${uniqueTranslations.slice(0, 5).map(t => `&bull; ${escHtml(t)}`).join('<br>')}
                    </div>`;
            }
        }

        dictResult.innerHTML = `
            <div class="dict-result">
                <div class="word">${escHtml(data.word)}</div>
                <div class="translation">${escHtml(data.translation)}</div>
                ${matchesHtml}
                <button class="btn btn-success btn-sm save-btn" onclick="saveToNotebook('${escAttr(data.word)}', '${escAttr(data.translation)}')">
                    + Kelime Defterine Ekle
                </button>
            </div>`;

        // Add to recent
        addToRecent(data.word, data.translation);
    } catch (err) {
        dictResult.innerHTML = `<div class="alert alert-danger">Baglanti hatasi: ${err.message}</div>`;
    }
}

function addToRecent(en, tr) {
    // Remove if exists
    recentWords = recentWords.filter(w => w.en !== en);
    recentWords.unshift({ en, tr });
    if (recentWords.length > 20) recentWords.pop();

    renderRecent();
}

function renderRecent() {
    if (recentWords.length === 0) {
        recentLookups.style.display = 'none';
        return;
    }
    recentLookups.style.display = '';
    recentList.innerHTML = recentWords.map(w => `
        <div class="recent-item" onclick="dictInput.value='${escAttr(w.en)}';lookupWord('${escAttr(w.en)}');">
            <span class="en">${escHtml(w.en)}</span>
            <span class="tr">${escHtml(w.tr)}</span>
        </div>
    `).join('');
}

window.saveToNotebook = async function(english, turkish) {
    const currentPage = window.readerGetCurrentPage ? window.readerGetCurrentPage() : 0;
    const fileKey = window.readerGetFileKey ? window.readerGetFileKey() : '';

    // Get selected context text
    const selection = window.getSelection();
    let context = '';
    if (selection.rangeCount > 0) {
        const range = selection.getRangeAt(0);
        // Try to get surrounding text
        const container = range.startContainer.parentElement;
        if (container) {
            context = container.textContent || '';
        }
    }

    try {
        const res = await fetch('/api/vocabulary.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                english,
                turkish,
                context: context.substring(0, 200),
                source_pdf: fileKey,
                source_page: currentPage,
            }),
        });

        const data = await res.json();

        if (res.ok) {
            // Show success feedback
            const btn = document.querySelector('.save-btn');
            if (btn) {
                btn.textContent = 'Eklendi!';
                btn.disabled = true;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-secondary');
            }
        } else {
            alert(data.error || 'Kelime eklenemedi');
        }
    } catch {
        alert('Baglanti hatasi');
    }
};

// Make lookupWord global for recent items
window.lookupWord = lookupWord;

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
}

function escAttr(s) {
    return (s || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '&quot;');
}
