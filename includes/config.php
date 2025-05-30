<?php
// Hata raporlamasını yapılandırma
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Veritabanı bağlantı bilgileri
define('DB_HOST', 'localhost');
define('DB_USER', 'seozof_agency');
define('DB_PASS', 'RLFZcaXCrmXp8Ec5ZLAR');
define('DB_NAME', 'seozof_agency');

// Groq API bilgileri
define('GROQ_API_KEY', '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');
define('GROQ_API_MODEL', 'llama3-70b-8192');
define('GROQ_API_URL', 'https://api.groq.com/v1/');

// Sistem yolları
define('BASE_URL', 'https://agency.seozof.com');
define('SITE_TITLE', 'Seozof Agency');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Karakter seti ayarları
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// Oturum yapılandırması
session_start();

// Güvenlik
define('CSRF_TOKEN_SECRET', 'your-secret-key-change-this');

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
?>