    </main>
    <footer class="footer">
        <div class="container">
            <p><?= SITE_TITLE ?> &copy; <?= date('Y') ?></p>
        </div>
    </footer>
    <script>
        // Theme toggle
        (function() {
            const btn = document.getElementById('theme-toggle');
            const saved = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', saved);
            btn.textContent = saved === 'dark' ? '\u2600' : '\u263E';
            btn.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-theme');
                const next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
                btn.textContent = next === 'dark' ? '\u2600' : '\u263E';
            });
            // Mobile nav
            const hamburger = document.getElementById('nav-hamburger');
            const navLinks = document.querySelector('.nav-links');
            hamburger.addEventListener('click', () => {
                navLinks.classList.toggle('open');
            });
        })();
    </script>
    <?php if (!empty($extraJs)): ?>
        <?php foreach ($extraJs as $js): ?>
            <script src="<?= $js ?>" <?= str_ends_with($js, '.mjs') ? 'type="module"' : '' ?>></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
