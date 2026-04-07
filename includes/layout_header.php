<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? SITE_TITLE) ?> - <?= SITE_TITLE ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if (!empty($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <link rel="stylesheet" href="<?= $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="/" class="nav-logo"><?= SITE_TITLE ?></a>
            <div class="nav-links">
                <a href="/" class="nav-link <?= ($currentPage ?? '') === 'home' ? 'active' : '' ?>">Kutuphane</a>
                <a href="/notebook.php" class="nav-link <?= ($currentPage ?? '') === 'notebook' ? 'active' : '' ?>">Kelime Defteri</a>
                <a href="/manage.php" class="nav-link <?= ($currentPage ?? '') === 'manage' ? 'active' : '' ?>">Yonetim</a>
                <button id="theme-toggle" class="btn-icon" title="Tema Degistir">&#9790;</button>
            </div>
            <button class="nav-hamburger" id="nav-hamburger" aria-label="Menu">&#9776;</button>
        </div>
    </nav>
    <main class="container">
