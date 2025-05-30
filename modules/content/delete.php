<?php
// İçerik Silme Sayfası

// İçerik ID'sini kontrol et
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('warning', 'Silinecek içerik ID belirtilmedi');
    redirect(url('index.php?page=content'));
}

$contentId = intval($_GET['id']);

// Onay parametresini kontrol et (güvenlik için)
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

// İçeriği veritabanından al (silme işleminden önce bilgileri almak için)
try {
    $content = $db->getRow("SELECT c.*, cat.category_name 
                        FROM content c
                        LEFT JOIN categories cat ON c.category_id = cat.id
                        WHERE c.id = ?", [$contentId]);
    
    if (!$content) {
        setFlashMessage('danger', 'Silinecek içerik bulunamadı');
        redirect(url('index.php?page=content'));
    }

    $categoryId = $content['category_id']; // Silme sonrası yönlendirme için
    
    // Eğer onay varsa, silme işlemini gerçekleştir
    if ($confirmed) {
        // İçeriği sil
        $deleted = $db->delete('content', 'id = ?', [$contentId]);
        
        if ($deleted) {
            // İçerik ile ilişkili etiketleri de sil
            $db->delete('content_tags', 'content_id = ?', [$contentId]);
            
            setFlashMessage('success', 'İçerik başarıyla silindi.');
            
            // Kategoriler sayfasına veya içerikler sayfasına yönlendir
            if ($categoryId > 0) {
                redirect(url('index.php?page=content&category=' . $categoryId));
            } else {
                redirect(url('index.php?page=content'));
            }
        } else {
            setFlashMessage('danger', 'İçerik silinirken bir hata oluştu.');
            redirect(url('index.php?page=content&action=view&id=' . $contentId));
        }
    }
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Veritabanı hatası: ' . $e->getMessage());
    redirect(url('index.php?page=content'));
}

// Sayfa başlığı
$pageTitle = 'İçerik Sil: ' . htmlspecialchars($content['title']);
?>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">İçerik Silme Onayı</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5>Bu içeriği silmek istediğinize emin misiniz?</h5>
                        <p class="text-muted">Bu işlem geri alınamaz.</p>
                    </div>
                    
                    <div class="alert alert-secondary">
                        <div class="row">
                            <div class="col-md-3 font-weight-bold">Başlık:</div>
                            <div class="col-md-9"><?= htmlspecialchars($content['title']) ?></div>
                        </div>
                        
                        <?php if ($content['category_id'] && isset($content['category_name'])): ?>
                        <div class="row mt-2">
                            <div class="col-md-3 font-weight-bold">Kategori:</div>
                            <div class="col-md-9"><?= htmlspecialchars($content['category_name']) ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($content['excerpt']) && !empty($content['excerpt'])): ?>
                        <div class="row mt-2">
                            <div class="col-md-3 font-weight-bold">Özet:</div>
                            <div class="col-md-9"><?= nl2br(htmlspecialchars(substr($content['excerpt'], 0, 100))) ?>
                                <?= (strlen($content['excerpt']) > 100) ? '...' : '' ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row mt-2">
                            <div class="col-md-3 font-weight-bold">Oluşturulma:</div>
                            <div class="col-md-9"><?= formatDate($content['created_at']) ?></div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?= url('index.php?page=content&action=view&id=' . $contentId) ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Vazgeç
                        </a>
                        <a href="<?= url('index.php?page=content&action=delete&id=' . $contentId . '&confirm=yes') ?>" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i> Evet, İçeriği Sil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>