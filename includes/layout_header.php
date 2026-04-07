<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= SITE_TITLE ?>">
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icon.php?size=180">
    <link rel="apple-touch-icon" sizes="152x152" href="/icon.php?size=152">
    <link rel="apple-touch-icon" sizes="120x120" href="/icon.php?size=120">
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
                <a href="/notes.php" class="nav-link <?= ($currentPage ?? '') === 'notes' ? 'active' : '' ?>">Alinti Defteri</a>
                <a href="/manage.php" class="nav-link <?= ($currentPage ?? '') === 'manage' ? 'active' : '' ?>">Yonetim</a>
                <button id="theme-toggle" class="btn-icon" title="Tema Degistir">&#9790;</button>
            </div>
            <button class="nav-hamburger" id="nav-hamburger" aria-label="Menu">&#9776;</button>
        </div>
    </nav>
    <main class="container">
