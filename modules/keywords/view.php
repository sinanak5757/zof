<?php
// Anahtar Kelime Görüntüleme Sayfası

// Anahtar kelime ID'sini al
$keywordId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ID kontrolü
if ($keywordId <= 0) {
    setFlashMessage('danger', 'Geçersiz anahtar kelime ID\'si.');
    redirect(url('index.php?page=keywords'));
}

try {
    // Anahtar kelime bilgilerini al
    $keyword = $db->getRow("
        SELECT k.*, p.project_name, p.id as project_id
        FROM keywords k
        JOIN projects p ON k.project_id = p.id
        WHERE k.id = ?
    ", [$keywordId]);
    
    if (!$keyword) {
        setFlashMessage('danger', 'Anahtar kelime bulunamadı.');
        redirect(url('index.php?page=keywords'));
    }
    
    // Anahtar kelime sıralama geçmişini al (varsa)
    $rankings = $db->getAll("
        SELECT * FROM keyword_rankings 
        WHERE keyword_id = ? 
        ORDER BY check_date DESC
        LIMIT 10
    ", [$keywordId]);
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Veritabanı hatası: ' . $e->getMessage());
    redirect(url('index.php?page=keywords'));
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Anahtar Kelime: <?php echo htmlspecialchars($keyword['keyword'] ?? ''); ?></h4>
        <div>
            <a href="<?php echo url('index.php?page=keywords'); ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Geri
            </a>
            <a href="<?php echo url('index.php?page=keywords&action=edit&id=' . $keywordId); ?>" class="btn btn-primary ms-2">
                <i class="bi bi-pencil me-1"></i> Düzenle
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Anahtar Kelime Bilgileri</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th width="30%">Proje:</th>
                            <td>
                                <a href="<?php echo url('index.php?page=projects&action=view&id=' . ($keyword['project_id'] ?? 0)); ?>">
                                    <?php echo htmlspecialchars($keyword['project_name'] ?? ''); ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Anahtar Kelime:</th>
                            <td><?php echo htmlspecialchars($keyword['keyword'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Mevcut Sıralama:</th>
                            <td>
                                <?php if (isset($keyword['current_rank']) && $keyword['current_rank'] > 0): ?>
                                    <span class="badge bg-primary"><?php echo $keyword['current_rank']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Henüz izlenmiyor</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Hedef Sıralama:</th>
                            <td>
                                <?php if (isset($keyword['target_rank']) && $keyword['target_rank'] > 0): ?>
                                    <span class="badge bg-success"><?php echo $keyword['target_rank']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Belirtilmemiş</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Arama Hacmi:</th>
                            <td>
                                <?php if (isset($keyword['search_volume']) && $keyword['search_volume'] > 0): ?>
                                    <?php echo number_format($keyword['search_volume'], 0, ',', '.'); ?> / ay
                                <?php else: ?>
                                    <span class="text-muted">Belirtilmemiş</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Son Kontrol:</th>
                            <td>
                                <?php if (isset($keyword['last_checked']) && $keyword['last_checked']): ?>
                                    <?php echo date('d.m.Y H:i', strtotime($keyword['last_checked'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">Henüz kontrol edilmedi</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Oluşturulma Tarihi:</th>
                            <td>
                                <?php if (isset($keyword['created_at'])): ?>
                                    <?php echo date('d.m.Y', strtotime($keyword['created_at'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">Belirtilmemiş</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Notlar</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($keyword['notes'])): ?>
                        <?php echo nl2br(htmlspecialchars($keyword['notes'])); ?>
                    <?php else: ?>
                        <span class="text-muted">Bu anahtar kelime için not bulunmuyor.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sıralama Geçmişi -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Sıralama Geçmişi</h5>
            <a href="<?php echo url('index.php?page=keywords&action=check&id=' . $keywordId); ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-arrow-clockwise me-1"></i> Sıralamayı Kontrol Et
            </a>
        </div>
        <div class="card-body">
            <?php if (count($rankings) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Sıralama</th>
                                <th>Değişim</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rankings as $index => $ranking): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y', strtotime($ranking['check_date'])); ?></td>
                                    <td><?php echo $ranking['rank']; ?></td>
                                    <td>
                                        <?php
                                        if (isset($rankings[$index + 1])) {
                                            $change = $rankings[$index + 1]['rank'] - $ranking['rank'];
                                            if ($change > 0) {
                                                echo '<span class="text-success"><i class="bi bi-arrow-up-circle"></i> +' . $change . '</span>';
                                            } elseif ($change < 0) {
                                                echo '<span class="text-danger"><i class="bi bi-arrow-down-circle"></i> ' . $change . '</span>';
                                            } else {
                                                echo '<span class="text-muted"><i class="bi bi-dash-circle"></i> 0</span>';
                                            }
                                        } else {
                                            echo '<span class="text-muted">İlk kontrol</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">Henüz sıralama geçmişi bulunmuyor.</p>
            <?php endif; ?>
        </div>
    </div>
</div>