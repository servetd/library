<?php

define('BASE_PATH', dirname(__DIR__));
define('DATA_PATH', BASE_PATH . '/data');
define('PDF_PATH', BASE_PATH . '/pdfs');
define('CATEGORIES_FILE', DATA_PATH . '/categories.json');
define('VOCABULARY_FILE', DATA_PATH . '/vocabulary.json');
define('PROGRESS_FILE', DATA_PATH . '/reading_progress.json');
define('NOTES_FILE', DATA_PATH . '/notes.json');
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50 MB
define('ALLOWED_EXTENSIONS', ['pdf']);
define('SITE_TITLE', 'PDF Kutuphanem');
