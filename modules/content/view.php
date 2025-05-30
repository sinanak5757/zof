<?php
// İçerik Görüntüleme Sayfası

// İçerik ID'sini kontrol et
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-warning">Görüntülenecek içerik ID belirtilmedi</div>';
    exit;
}

$contentId = intval($_GET['id']);

// İçeriği veritabanından al
try {
    // Önce içerik verisini al
    $content = $db->getRow("
        SELECT c.*, cat.name as category_name, p.project_name, p.id as project_id
        FROM content c
        LEFT JOIN content_categories cat ON c.category_id = cat.id
        LEFT JOIN projects p ON c.project_id = p.id
        WHERE c.id = ?", 
        [$contentId]
    );
    
    if (!$content) {
        echo '<div class="alert alert-danger">İçerik bulunamadı</div>';
        exit;
    }
    
    // İçerik etiketlerini getir
    try {
        $tags = $db->getAll("
            SELECT t.id, t.tag_name
            FROM content_tags ct
            JOIN tags t ON ct.tag_id = t.id
            WHERE ct.content_id = ?", 
            [$contentId]
        );
    } catch (Exception $e) {
        $tags = [];
    }
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Veritabanı hatası: ' . $e->getMessage() . '</div>';
    exit;
}

// Sayfa başlığı
$pageTitle = htmlspecialchars($content['title']);

// Meta açıklamaları
$metaDescription = !empty($content['meta_description']) ? $content['meta_description'] : '';
?>

<div class="container-fluid mt-4">
    <!-- Başlık ve İşlem Butonları -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($content['title']) ?></h1>
        <div>
            <a href="<?= url('index.php?page=content') ?>" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Geri
            </a>
            <a href="<?= url('index.php?page=content&action=edit&id=' . $contentId) ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-edit"></i> Düzenle
            </a>
            <a href="<?= url('index.php?page=content&action=delete&id=' . $contentId) ?>" class="btn btn-sm btn-danger">
                <i class="fas fa-trash"></i> Sil
            </a>
        </div>
    </div>

    <div class="row">
        <!-- İçerik Ana Bölümü -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <!-- İçerik Başlık ve Durum -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">İçerik</h6>
                    <div>
                        <?php if ($content['status'] == 'published'): ?>
                            <span class="badge bg-success">Yayında</span>
                        <?php elseif ($content['status'] == 'draft'): ?>
                            <span class="badge bg-secondary">Taslak</span>
                        <?php elseif ($content['status'] == 'pending'): ?>
                            <span class="badge bg-warning">İncelemede</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- İçerik Gövdesi -->
                <div class="card-body">
                    <?php if (!empty($content['featured_image'])): ?>
                    <div class="text-center mb-4">
                        <img src="<?= $content['featured_image'] ?>" alt="<?= htmlspecialchars($content['title']) ?>" class="img-fluid rounded">
                    </div>
                    <?php endif; ?>
                    
                    <div class="content-body">
                        <?= $content['content'] ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- İçerik Yan Bilgi Paneli -->
        <div class="col-lg-4">
            <!-- İçerik Bilgileri -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">İçerik Bilgileri</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th scope="row" width="40%">Kategori:</th>
                                    <td>
                                        <?php if (isset($content['category_name'])): ?>
                                            <?= htmlspecialchars($content['category_name']) ?>
                                        <?php else: ?>
                                            <em>Kategori yok</em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row" width="40%">Proje:</th>
                                    <td>
                                        <?php if (!empty($content['project_name'])): ?>
                                            <a href="<?= url('index.php?page=projects&action=view&id=' . $content['project_id']) ?>">
                                                <?= htmlspecialchars($content['project_name']) ?>
                                            </a>
                                        <?php else: ?>
                                            <em>Proje atanmamış</em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Oluşturulma:</th>
                                    <td><?= date('d.m.Y H:i', strtotime($content['created_at'])) ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Son Güncelleme:</th>
                                    <td><?= date('d.m.Y H:i', strtotime($content['updated_at'])) ?></td>
                                </tr>
                                <?php if (isset($content['views'])): ?>
                                <tr>
                                    <th scope="row">Görüntülenme:</th>
                                    <td><?= number_format($content['views']) ?></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Etiketler -->
            <?php if (!empty($tags)): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Etiketler</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <?php foreach ($tags as $tag): ?>
                            <a href="<?= url('index.php?page=content&tag=' . $tag['id']) ?>" class="btn btn-sm btn-outline-primary mb-1">
                                <?= htmlspecialchars($tag['tag_name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- SEO Bilgileri -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">SEO Bilgileri</h6>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Meta Başlık:</label>
                        <p class="mb-0"><?= !empty($content['meta_title']) ? htmlspecialchars($content['meta_title']) : '<em class="text-muted">Belirtilmemiş</em>' ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-weight-bold">Meta Açıklama:</label>
                        <p class="mb-0"><?= !empty($content['meta_description']) ? htmlspecialchars($content['meta_description']) : '<em class="text-muted">Belirtilmemiş</em>' ?></p>
                    </div>
                    
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Meta Anahtar Kelimeler:</label>
                        <p class="mb-0"><?= !empty($content['meta_keywords']) ? htmlspecialchars($content['meta_keywords']) : '<em class="text-muted">Belirtilmemiş</em>' ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>