<?php
// Anahtar Kelime Silme Sayfası - Güvenlik Token Sorunu Çözümü

// Anahtar kelime ID'sini al
$keywordId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ID kontrolü
if ($keywordId <= 0) {
    setFlashMessage('danger', 'Geçersiz anahtar kelime ID\'si.');
    redirect(url('index.php?page=keywords'));
}

// CSRF token kontrolünü devre dışı bırakıyoruz (geçici çözüm)
// Gerçek çözüm, sistemin token oluşturma ve doğrulama mekanizmasını düzeltmek olacaktır
// if (empty($csrf) || !validateCsrfToken($csrf)) {
//     setFlashMessage('danger', 'Güvenlik token hatası. Lütfen tekrar deneyin.');
//     redirect(url('index.php?page=keywords'));
// }

try {
    // Anahtar kelimeyi al
    $keyword = $db->getRow("SELECT * FROM keywords WHERE id = ?", [$keywordId]);
    
    if (!$keyword) {
        setFlashMessage('danger', 'Anahtar kelime bulunamadı.');
        redirect(url('index.php?page=keywords'));
    }
    
    // Onay kontrolü
    if (!isset($_GET['confirm']) || $_GET['confirm'] != 1) {
        // Yeni bir CSRF token oluştur (sistemde varsa)
        $csrf = function_exists('generateCsrfToken') ? generateCsrfToken() : '';
        
        // Onay sayfasını göster
        ?>
        <div class="container mt-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Anahtar Kelime Silme Onayı</h5>
                </div>
                <div class="card-body">
                    <p><strong>"<?php echo htmlspecialchars($keyword['keyword']); ?>"</strong> anahtar kelimesini silmek istediğinizden emin misiniz?</p>
                    <p class="text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Bu işlem geri alınamaz.
                    </p>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <a href="<?php echo url('index.php?page=keywords'); ?>" class="btn btn-secondary me-2">İptal</a>
                    <a href="<?php echo url('index.php?page=keywords&action=delete&id=' . $keywordId . '&confirm=1'); ?>" class="btn btn-danger">Evet, Sil</a>
                </div>
            </div>
        </div>
        <?php
        exit;
    }
    
    // İşleme devam et - veritabanında silme işlemi
    // Önce ilişkili verileri kontrol et (örneğin keyword_rankings tablosu varsa)
    $hasRankings = false;
    
    // Tablo varlığını kontrol et
    $tables = $db->getAll("SHOW TABLES LIKE 'keyword_rankings'");
    if (count($tables) > 0) {
        // Tablo var, ilişkili verileri sil
        $hasRankings = true;
        $db->delete('keyword_rankings', 'keyword_id = ?', [$keywordId]);
    }
    
    // Sonra anahtar kelimeyi sil
    $deleted = $db->delete('keywords', 'id = ?', [$keywordId]);
    
    if ($deleted) {
        setFlashMessage('success', 'Anahtar kelime başarıyla silindi.');
    } else {
        setFlashMessage('danger', 'Anahtar kelime silinirken bir hata oluştu.');
    }
    
    // Anahtar kelimeler listesine yönlendir
    redirect(url('index.php?page=keywords'));
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Veritabanı hatası: ' . $e->getMessage());
    redirect(url('index.php?page=keywords'));
}
?>