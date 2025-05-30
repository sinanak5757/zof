<?php
// Genel yardımcı fonksiyonlar

// Sayfa yönlendirme
function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit;
    } else {
        echo "<script>window.location.href='$url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
        exit;
    }
}

// URL oluşturma
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

// XSS koruması için metin temizleme
function sanitize($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Flash mesajları ayarlama
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Flash mesajlarını gösterme
function showFlashMessages() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_message']['type'];
        $message = $_SESSION['flash_message']['message'];
        
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo $message;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        
        unset($_SESSION['flash_message']);
    }
}

// CSRF token oluşturma
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

// CSRF token doğrulama
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('CSRF token doğrulama hatası.');
    }
    
    return true;
}

// Tarih formatını düzenleme
function formatDate($date, $format = 'd.m.Y H:i') {
    return date($format, strtotime($date));
}

// Para birimini formatla
function formatMoney($amount, $currency = '₺') {
    return number_format($amount, 2, ',', '.') . ' ' . $currency;
}

// Dosya uzantısını alma
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

// Güvenli dosya adı oluşturma
function createSafeFilename($filename) {
    $extension = getFileExtension($filename);
    $safeName = preg_replace('/[^a-z0-9]+/', '-', strtolower(pathinfo($filename, PATHINFO_FILENAME)));
    return $safeName . '-' . time() . '.' . $extension;
}

// Dosya yükleme
function uploadFile($file, $directory, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']) {
    $targetDir = UPLOAD_DIR . $directory . '/';
    
    // Dizin yoksa oluştur
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $extension = getFileExtension($file['name']);
    
    // Dosya türü kontrolü
    if (!in_array($extension, $allowedTypes)) {
        return [
            'success' => false,
            'message' => 'Desteklenmeyen dosya türü. İzin verilen türler: ' . implode(', ', $allowedTypes)
        ];
    }
    
    $targetFile = $targetDir . createSafeFilename($file['name']);
    
    // Dosyayı yükle
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return [
            'success' => true,
            'filepath' => str_replace(UPLOAD_DIR, '', $targetFile)
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Dosya yüklenirken bir hata oluştu.'
        ];
    }
}

// Aktif menü sınıfı
function isActiveMenu($page) {
    $currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
    return ($currentPage == $page) ? 'active' : '';
}

// Paginasyon
function pagination($totalItems, $currentPage, $perPage = 10, $urlPattern = '?page=%d') {
    $totalPages = ceil($totalItems / $perPage);
    
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Sayfalama"><ul class="pagination">';
    
    // Önceki sayfa
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $currentPage - 1) . '">&laquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#">&laquo;</a></li>';
    }
    
    // Sayfa numaraları
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, 1) . '">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            $html .= '<li class="page-item active"><a class="page-link" href="#">' . $i . '</a></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $i) . '">' . $i . '</a></li>';
        }
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $totalPages) . '">' . $totalPages . '</a></li>';
    }
    
    // Sonraki sayfa
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $currentPage + 1) . '">&raquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#">&raquo;</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

// Zaman önce/sonra formatı (örn: 3 saat önce)
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return $diff . ' saniye önce';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' dakika önce';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' saat önce';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' gün önce';
    } elseif ($diff < 2592000) {
        return floor($diff / 604800) . ' hafta önce';
    } elseif ($diff < 31536000) {
        return floor($diff / 2592000) . ' ay önce';
    } else {
        return floor($diff / 31536000) . ' yıl önce';
    }
}

// Metni kısaltma
function truncate($text, $length = 100, $append = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    
    return $text . $append;
}

// Slug oluşturma
function createSlug($text) {
    // Türkçe karakterleri dönüştür
    $text = str_replace(
        ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'],
        ['i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c'],
        $text
    );
    
    // Alfanümerik olmayan karakterleri çıkar, boşlukları tire ile değiştir
    $text = preg_replace('/[^a-z0-9\s-]/', '', strtolower($text));
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    
    return $text;
}
?>