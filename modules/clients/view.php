<?php
// Müşteri detay sayfası

// Müşteri ID'sini al
$clientId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($clientId <= 0) {
    setFlashMessage('danger', 'Geçersiz müşteri ID\'si.');
    redirect(url('index.php?page=clients'));
}

// Müşteri bilgilerini al
try {
    $client = $db->getRow("SELECT * FROM clients WHERE id = ?", [$clientId]);
    
    if (!$client) {
        setFlashMessage('danger', 'Müşteri bulunamadı.');
        redirect(url('index.php?page=clients'));
    }
    
    // Müşterinin projelerini al
    $projects = $db->getAll("SELECT * FROM projects WHERE client_id = ? ORDER BY start_date DESC", [$clientId]);
    
    // Müşterinin anahtar kelimelerini al
    $keywords = $db->getAll("
        SELECT k.* FROM keywords k
        JOIN projects p ON k.project_id = p.id
        WHERE p.client_id = ?
        ORDER BY k.priority ASC, k.keyword ASC
    ", [$clientId]);
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Müşteri bilgileri alınırken bir hata oluştu: ' . $e->getMessage());
    redirect(url('index.php?page=clients'));
}

// Durum sınıfları ve isimleri
$statusClasses = [
    'active' => 'bg-success',
    'inactive' => 'bg-danger',
    'prospect' => 'bg-warning'
];

$statusNames = [
    'active' => 'Aktif',
    'inactive' => 'Pasif',
    'prospect' => 'Potansiyel'
];
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><?php echo htmlspecialchars($client['company_name']); ?></h4>
        <div>
            <a href="<?php echo url('index.php?page=clients&action=edit&id=' . $clientId); ?>" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> Düzenle
            </a>
            <a href="<?php echo url('index.php?page=clients'); ?>" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-arrow-left me-1"></i> Müşterilere Dön
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Müşteri Bilgileri</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Durum:</th>
                            <td>
                                <span class="badge <?php echo $statusClasses[$client['status']] ?? 'bg-secondary'; ?>">
                                    <?php echo $statusNames[$client['status']] ?? 'Bilinmiyor'; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Müşteri Olma Tarihi:</th>
                            <td><?php echo date('d.m.Y', strtotime($client['client_since'])); ?></td>
                        </tr>
                        <tr>
                            <th>Web Sitesi:</th>
                            <td>
                                <a href="<?php echo htmlspecialchars($client['website']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($client['website']); ?> <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Sektör:</th>
                            <td><?php echo htmlspecialchars($client['industry'] ?: 'Belirtilmemiş'); ?></td>
                        </tr>
                        <tr>
                            <th>İlgili Kişi:</th>
                            <td><?php echo htmlspecialchars($client['contact_person'] ?: 'Belirtilmemiş'); ?></td>
                        </tr>
                        <tr>
                            <th>E-posta:</th>
                            <td>
                                <?php if ($client['contact_email']): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($client['contact_email']); ?>">
                                        <?php echo htmlspecialchars($client['contact_email']); ?>
                                    </a>
                                <?php else: ?>
                                    Belirtilmemiş
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Telefon:</th>
                            <td><?php echo htmlspecialchars($client['contact_phone'] ?: 'Belirtilmemiş'); ?></td>
                        </tr>
                    </table>
                    
                    <?php if ($client['notes']): ?>
                        <div class="mt-3">
                            <h6>Notlar:</h6>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($client['notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Projeler</h5>
                    <a href="<?php echo url('index.php?page=projects&action=add&client_id=' . $clientId); ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Yeni Proje
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (count($projects) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Proje Adı</th>
                                        <th>Durum</th>
                                        <th>Başlangıç Tarihi</th>
                                        <th>Bitiş Tarihi</th>
                                        <th class="text-end">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($projects as $project): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                                            <td>
                                                <?php
                                                $projectStatusClasses = [
                                                    'planning' => 'bg-info',
                                                    'in_progress' => 'bg-primary',
                                                    'on_hold' => 'bg-warning',
                                                    'completed' => 'bg-success'
                                                ];
                                                
                                                $projectStatusNames = [
                                                    'planning' => 'Planlama',
                                                    'in_progress' => 'Devam Ediyor',
                                                    'on_hold' => 'Beklemede',
                                                    'completed' => 'Tamamlandı'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $projectStatusClasses[$project['status']] ?? 'bg-secondary'; ?>">
                                                    <?php echo $projectStatusNames[$project['status']] ?? $project['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d.m.Y', strtotime($project['start_date'])); ?></td>
                                            <td><?php echo $project['end_date'] ? date('d.m.Y', strtotime($project['end_date'])) : '-'; ?></td>
                                            <td class="text-end">
                                                <a href="<?php echo url('index.php?page=projects&action=view&id=' . $project['id']); ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo url('index.php?page=projects&action=edit&id=' . $project['id']); ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center">
                            <p class="mb-0">Bu müşteriye ait proje bulunamadı.</p>
                            <a href="<?php echo url('index.php?page=projects&action=add&client_id=' . $clientId); ?>" class="btn btn-sm btn-primary mt-2">
                                <i class="bi bi-plus-circle me-1"></i> Yeni Proje Ekle
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Anahtar Kelimeler</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($keywords) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Anahtar Kelime</th>
                                        <th>Öncelik</th>
                                        <th>İlk Pozisyon</th>
                                        <th>Hedef Pozisyon</th>
                                        <th>Son Kontrol</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($keywords as $keyword): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($keyword['keyword']); ?></td>
                                            <td>
                                                <?php
                                                $priorityClasses = [
                                                    'high' => 'bg-danger',
                                                    'medium' => 'bg-warning',
                                                    'low' => 'bg-info'
                                                ];
                                                
                                                $priorityNames = [
                                                    'high' => 'Yüksek',
                                                    'medium' => 'Orta',
                                                    'low' => 'Düşük'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $priorityClasses[$keyword['priority']] ?? 'bg-secondary'; ?>">
                                                    <?php echo $priorityNames[$keyword['priority']] ?? $keyword['priority']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $keyword['initial_position'] ?: '-'; ?></td>
                                            <td><?php echo $keyword['target_position'] ?: '-'; ?></td>
                                            <td>
                                                <?php
                                                try {
                                                    $lastRanking = $db->getRow("
                                                        SELECT position, check_date 
                                                        FROM keyword_rankings 
                                                        WHERE keyword_id = ? 
                                                        ORDER BY check_date DESC 
                                                        LIMIT 1
                                                    ", [$keyword['id']]);
                                                    
                                                    if ($lastRanking) {
                                                        echo '<span class="badge bg-secondary">' . $lastRanking['position'] . '</span> ';
                                                        echo date('d.m.Y', strtotime($lastRanking['check_date']));
                                                    } else {
                                                        echo 'Henüz kontrol edilmedi';
                                                    }
                                                } catch (Exception $e) {
                                                    echo 'Hata: ' . $e->getMessage();
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center">
                            <p class="mb-0">Bu müşteriye ait anahtar kelime bulunamadı.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>