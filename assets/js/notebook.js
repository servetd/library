let words = [];
let filteredWords = [];
let reviewWords = [];
let reviewIndex = 0;

document.addEventListener('DOMContentLoaded', () => {
    loadWords();
    document.getElementById('search-input').addEventListener('input', debounce(filterWords, 300));
    document.getElementById('filter-mastered').addEventListener('change', filterWords);
    document.getElementById('btn-review').addEventListener('click', startReview);
    document.getElementById('btn-exit-review').addEventListener('click', exitReview);
    document.getElementById('btn-prev-card').addEventListener('click', prevCard);
    document.getElementById('btn-next-card').addEventListener('click', nextCard);
    document.getElementById('btn-mastered').addEventListener('click', markMastered);
});

async function loadWords() {
    const search = document.getElementById('search-input').value.trim();
    const mastered = document.getElementById('filter-mastered').value;

    let url = '/api/vocabulary.php?';
    if (search) url += `search=${encodeURIComponent(search)}&`;
    if (mastered !== '') url += `mastered=${mastered}&`;

    const res = await fetch(url);
    const data = await res.json();
    words = data.words || [];
    filteredWords = words;
    renderWords();
}

function filterWords() {
    loadWords();
}

function renderWords() {
    const tbody = document.getElementById('word-tbody');
    const empty = document.getElementById('word-empty');
    const count = document.getElementById('word-count');

    if (filteredWords.length === 0) {
        tbody.innerHTML = '';
        empty.style.display = '';
        count.textContent = '';
        return;
    }

    empty.style.display = 'none';
    count.textContent = `Toplam ${filteredWords.length} kelime`;

    tbody.innerHTML = filteredWords.map(w => {
        const sourceLink = w.source_pdf
            ? `<a href="/reader.php?category=${encodeURIComponent(w.source_pdf.split('/')[0])}&file=${encodeURIComponent(w.source_pdf.split('/')[1] || '')}" title="PDF'de ac">s.${w.source_page || '?'}</a>`
            : '-';
        const masteredClass = w.mastered ? 'btn-secondary' : 'btn-success';
        const masteredText = w.mastered ? 'Ogrenildi' : 'Ogren';

        return `<tr>
            <td><strong>${escHtml(w.english)}</strong></td>
            <td>${escHtml(w.turkish)}</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escAttr(w.context || '')}">${escHtml(w.context || '-')}</td>
            <td>${sourceLink}</td>
            <td>
                <button class="btn btn-sm ${masteredClass}" onclick="toggleMastered('${w.id}', ${!w.mastered})">
                    ${masteredText}
                </button>
            </td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="deleteWord('${w.id}')">Sil</button>
            </td>
        </tr>`;
    }).join('');
}

window.toggleMastered = async function(id, mastered) {
    await fetch('/api/vocabulary.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, mastered }),
    });
    loadWords();
};

window.deleteWord = async function(id) {
    if (!confirm('Bu kelimeyi silmek istediginize emin misiniz?')) return;
    await fetch(`/api/vocabulary.php?id=${id}`, { method: 'DELETE' });
    loadWords();
};

// === Review / Flashcard Mode ===

function startReview() {
    reviewWords = filteredWords.filter(w => !w.mastered);
    if (reviewWords.length === 0) {
        alert('Tekrar edilecek kelime yok!');
        return;
    }
    reviewIndex = 0;
    document.getElementById('word-list-view').style.display = 'none';
    document.getElementById('review-view').style.display = '';
    showCard();
}

function exitReview() {
    document.getElementById('review-view').style.display = 'none';
    document.getElementById('word-list-view').style.display = '';
    loadWords();
}

function showCard() {
    if (reviewWords.length === 0) {
        document.getElementById('review-counter').textContent = 'Tum kelimeler ogrenildi!';
        document.getElementById('flashcard').style.display = 'none';
        return;
    }
    document.getElementById('flashcard').style.display = '';
    const w = reviewWords[reviewIndex];
    document.getElementById('card-front').textContent = w.english;
    document.getElementById('card-back').textContent = w.turkish;
    document.getElementById('flashcard').classList.remove('flipped');
    document.getElementById('review-counter').textContent = `${reviewIndex + 1} / ${reviewWords.length}`;
    document.getElementById('btn-mastered').textContent = 'Ogrendim';
    document.getElementById('btn-mastered').classList.remove('btn-secondary');
    document.getElementById('btn-mastered').classList.add('btn-success');
}

window.flipCard = function() {
    document.getElementById('flashcard').classList.toggle('flipped');
};

function nextCard() {
    if (reviewIndex < reviewWords.length - 1) {
        reviewIndex++;
        showCard();
    }
}

function prevCard() {
    if (reviewIndex > 0) {
        reviewIndex--;
        showCard();
    }
}

async function markMastered() {
    const w = reviewWords[reviewIndex];
    await fetch('/api/vocabulary.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: w.id, mastered: true }),
    });

    document.getElementById('btn-mastered').textContent = 'Ogrenildi!';
    document.getElementById('btn-mastered').classList.remove('btn-success');
    document.getElementById('btn-mastered').classList.add('btn-secondary');

    reviewWords.splice(reviewIndex, 1);
    if (reviewWords.length === 0) {
        showCard();
        return;
    }
    if (reviewIndex >= reviewWords.length) reviewIndex = 0;
    setTimeout(showCard, 500);
}

// Keyboard shortcuts for review
document.addEventListener('keydown', (e) => {
    if (document.getElementById('review-view').style.display === 'none') return;
    if (e.target.tagName === 'INPUT') return;
    if (e.key === ' ') { e.preventDefault(); flipCard(); }
    if (e.key === 'ArrowRight') nextCard();
    if (e.key === 'ArrowLeft') prevCard();
    if (e.key === 'Enter') markMastered();
});

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
