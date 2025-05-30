<?php
// Gerekli dosyaları dahil et
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/groq_api.php';

// Sayfa ve işlem parametrelerini al
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

// Giriş kontrolü
requireLogin();

// Header
include 'includes/header.php';

// İçeriği yükle
$modulePath = "modules/$page/$action.php";

if (file_exists($modulePath)) {
    include $modulePath;
} else {
    echo '<div class="alert alert-danger">Sayfa bulunamadı!</div>';
}

// Footer
include 'includes/footer.php';
?>