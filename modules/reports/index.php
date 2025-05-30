<?php
// reports/index.php - Raporlar Ana Sayfası

// Sayfalama için toplam rapor sayısı
try {
    $totalReports = $db->getRow("SELECT COUNT(*) as count FROM reports")['count'];
} catch (Exception $e) {
    $totalReports = 0;
}

$perPage = 12;
$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($currentPage - 1) * $perPage;

// Filtreler
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$clientFilter = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$dateFilter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';

// SQL sorgusu oluştur
$sql = "
    SELECT r.*, c.client_name, u.first_name, u.last_name
    FROM reports r
    LEFT JOIN clients c ON r.client_id = c.id
    LEFT JOIN users u ON r.created_by = u.id
    WHERE 1=1
";
$params = [];

if (!empty($searchTerm)) {
    $sql .= " AND (r.title LIKE ? OR r.description LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params = array_merge($params, [$searchParam, $searchParam]);
}

if (!empty($typeFilter)) {
    $sql .= " AND r.report_type = ?";
    $params[] = $typeFilter;
}

if (!empty($statusFilter)) {
    $sql .= " AND r.status = ?";
    $params[] = $statusFilter;
}

if ($clientFilter > 0) {
    $sql .= " AND r.client_id = ?";
    $params[] = $clientFilter;
}

if (!empty($dateFilter)) {
    switch ($dateFilter) {
        case 'today':
            $sql .= " AND DATE(r.created_at) = CURDATE()";
            break;
        case 'week':
            $sql .= " AND r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $sql .= " AND r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

$sql .= " ORDER BY r.created_at DESC LIMIT $offset, $perPage";

// Filtreler için müşterileri al
try {
    $clients = $db->getAll("SELECT id, client_name FROM clients ORDER BY client_name ASC");
    
    // Raporları al
    $reports = $db->getAll($sql, $params);
} catch (Exception $e) {
    $clients = [];
    $reports = [];
    setFlashMessage('warning', 'Veriler yüklenirken hata oluştu: ' . $e->getMessage());
}

// Rapor türleri
$reportTypes = [
    'client_report' => 'Müşteri Raporu',
    'proposal_report' => 'Teklif Raporu',
    'monthly_report' => 'Aylık Rapor',
    'audit_report' => 'SEO Denetim Raporu',
    'competitor_analysis' => 'Rakip Analizi',
    'keyword_report' => 'Anahtar Kelime Raporu'
];

// Durum sınıfları ve isimleri
$statusClasses = [
    'draft' => 'bg-secondary',
    'generating' => 'bg-warning',
    'completed' => 'bg-success',
    'sent' => 'bg-info',
    'archived' => 'bg-dark'
];

$statusNames = [
    'draft' => 'Taslak',
    'generating' => 'Oluşturuluyor',
    'completed' => 'Tamamlandı',
    'sent' => 'Gönderildi',
    'archived' => 'Arşivlendi'
];

// Sayfa başlığı
$pageTitle = 'SEO Raporları';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4><i class="fas fa-chart-line me-2"></i>SEO Raporları</h4>
            <p class="text-muted mb-0">Müşteri raporları ve teklifler için AI destekli rapor yönetimi</p>
        </div>
        <div>
            <div class="btn-group me-2">
                <a href="<?= url('index.php?page=reports&action=templates') ?>" class="btn btn-outline-info">
                    <i class="fas fa-file-alt me-1"></i> Şablonlar
                </a>
                <a href="<?= url('index.php?page=reports&action=analytics') ?>" class="btn btn-outline-primary">
                    <i class="fas fa-analytics me-1"></i> Analitik
                </a>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-plus me-1"></i> Yeni Rapor
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="<?= url('index.php?page=reports&action=create&type=client_report') ?>">
                        <i class="fas fa-chart-bar me-2"></i> Müşteri Raporu
                    </a></li>
                    <li><a class="dropdown-item" href="<?= url('index.php?page=reports&action=create&type=proposal_report') ?>">
                        <i class="fas fa-file-contract me-2"></i> Teklif Raporu
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= url('index.php?page=reports&action=bulk_generate') ?>">
                        <i class="fas fa-layer-group me-2"></i> Toplu Rapor Oluştur
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Toplam Rapor</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totalReports) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Bu Ay Oluşturulan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                try {
                                    $monthlyCount = $db->getRow("SELECT COUNT(*) as count FROM reports WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")['count'];
                                    echo number_format($monthlyCount);
                                } catch (Exception $e) {
                                    echo '0';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Aktif Müşteriler</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                try {
                                    $activeClients = $db->getRow("SELECT COUNT(DISTINCT client_id) as count FROM reports WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'];
                                    echo number_format($activeClients);
                                } catch (Exception $e) {
                                    echo '0';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                AI Kullanım Oranı</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                try {
                                    $aiReports = $db->getRow("SELECT COUNT(*) as count FROM reports WHERE ai_enhanced = 1")['count'];
                                    $aiPercentage = $totalReports > 0 ? round(($aiReports / $totalReports) * 100) : 0;
                                    echo $aiPercentage . '%';
                                } catch (Exception $e) {
                                    echo '0%';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-robot fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <input type="hidden" name="page" value="reports">
                
                <div class="col-md-3">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Rapor ara..." name="search" value="<?= htmlspecialchars($searchTerm) ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">Tüm Türler</option>
                        <?php foreach ($reportTypes as $type => $label): ?>
                            <option value="<?= $type ?>" <?= $typeFilter === $type ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <select name="client_id" class="form-select">
                        <option value="0">Tüm Müşteriler</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>" <?= $clientFilter === $client['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($client['client_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Tüm Durumlar</option>
                        <?php foreach ($statusNames as $status => $label): ?>
                            <option value="<?= $status ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <select name="date_filter" class="form-select">
                        <option value="">Tüm Zamanlar</option>
                        <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Bugün</option>
                        <option value="week" <?= $dateFilter === 'week' ? 'selected' : '' ?>>Son Hafta</option>
                        <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>Son Ay</option>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <a href="<?= url('index.php?page=reports') ?>" class="btn btn-outline-secondary w-100" title="Filtreleri Sıfırla">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Rapor Listesi -->
    <?php if (count($reports) > 0): ?>
        <div class="row">
            <?php foreach ($reports as $report): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm report-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="badge <?= $statusClasses[$report['status']] ?? 'bg-secondary' ?>">
                                <?= $statusNames[$report['status']] ?? $report['status'] ?>
                            </span>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?= url('index.php?page=reports&action=view&id=' . $report['id']) ?>">
                                        <i class="fas fa-eye me-2"></i> Görüntüle
                                    </a></li>
                                    <?php if ($report['status'] === 'draft'): ?>
                                    <li><a class="dropdown-item" href="<?= url('index.php?page=reports&action=edit&id=' . $report['id']) ?>">
                                        <i class="fas fa-edit me-2"></i> Düzenle
                                    </a></li>
                                    <?php endif; ?>
                                    <li><a class="dropdown-item" href="<?= url('index.php?page=reports&action=duplicate&id=' . $report['id']) ?>">
                                        <i class="fas fa-copy me-2"></i> Kopyala
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?= url('index.php?page=reports&action=export&id=' . $report['id'] . '&format=pdf') ?>">
                                        <i class="fas fa-file-pdf me-2"></i> PDF İndir
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?= url('index.php?page=reports&action=send&id=' . $report['id']) ?>">
                                        <i class="fas fa-envelope me-2"></i> E-posta Gönder
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title mb-2">
                                <a href="<?= url('index.php?page=reports&action=view&id=' . $report['id']) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($report['title']) ?>
                                </a>
                            </h6>
                            
                            <div class="mb-2">
                                <span class="badge bg-light text-dark">
                                    <?= $reportTypes[$report['report_type']] ?? $report['report_type'] ?>
                                </span>
                                <?php if (!empty($report['ai_enhanced']) && $report['ai_enhanced'] == 1): ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-robot me-1"></i> AI
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($report['description'])): ?>
                                <p class="card-text text-muted small">
                                    <?= htmlspecialchars(substr($report['description'], 0, 100)) ?>
                                    <?= strlen($report['description']) > 100 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="report-info">
                                <?php if (!empty($report['client_name'])): ?>
                                    <div class="mb-1">
                                        <i class="fas fa-user text-muted me-1"></i>
                                        <span class="small"><?= htmlspecialchars($report['client_name']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mb-1">
                                    <i class="fas fa-calendar text-muted me-1"></i>
                                    <span class="small"><?= date('d.m.Y H:i', strtotime($report['created_at'])) ?></span>
                                </div>
                                
                                <?php if (!empty($report['first_name']) || !empty($report['last_name'])): ?>
                                    <div class="mb-1">
                                        <i class="fas fa-user-edit text-muted me-1"></i>
                                        <span class="small"><?= htmlspecialchars(trim($report['first_name'] . ' ' . $report['last_name'])) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('index.php?page=reports&action=view&id=' . $report['id']) ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($report['status'] === 'completed'): ?>
                                        <a href="<?= url('index.php?page=reports&action=export&id=' . $report['id'] . '&format=pdf') ?>" class="btn btn-outline-success">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted">
                                    ID: #<?= str_pad($report['id'], 4, '0', STR_PAD_LEFT) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                <h5 class="text-muted mb-3">
                    <?php if (!empty($searchTerm) || !empty($typeFilter) || !empty($statusFilter) || $clientFilter > 0): ?>
                        Arama kriterlerinize uygun rapor bulunamadı
                    <?php else: ?>
                        Henüz rapor oluşturulmamış
                    <?php endif; ?>
                </h5>
                <p class="text-muted mb-4">
                    <?php if (!empty($searchTerm) || !empty($typeFilter) || !empty($statusFilter) || $clientFilter > 0): ?>
                        Farklı filtreler deneyebilir veya filtreleri sıfırlayabilirsiniz.
                    <?php else: ?>
                        Müşterileriniz için profesyonel SEO raporları oluşturmaya başlayın.
                    <?php endif; ?>
                </p>
                <div class="btn-group">
                    <a href="<?= url('index.php?page=reports&action=create&type=client_report') ?>" class="btn btn-primary">
                        <i class="fas fa-chart-bar me-1"></i> Müşteri Raporu Oluştur
                    </a>
                    <a href="<?= url('index.php?page=reports&action=create&type=proposal_report') ?>" class="btn btn-outline-primary">
                        <i class="fas fa-file-contract me-1"></i> Teklif Raporu Oluştur
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Sayfalama -->
    <?php if ($totalReports > $perPage): ?>
        <div class="d-flex justify-content-center mt-4">
            <?php
            $totalPages = ceil($totalReports / $perPage);
            $urlPattern = url('index.php?page=reports&p=%d');
            
            if (!empty($searchTerm)) {
                $urlPattern .= '&search=' . urlencode($searchTerm);
            }
            
            if (!empty($typeFilter)) {
                $urlPattern .= '&type=' . urlencode($typeFilter);
            }
            
            if (!empty($statusFilter)) {
                $urlPattern .= '&status=' . urlencode($statusFilter);
            }
            
            if ($clientFilter > 0) {
                $urlPattern .= '&client_id=' . $clientFilter;
            }
            
            if (!empty($dateFilter)) {
                $urlPattern .= '&date_filter=' . urlencode($dateFilter);
            }
            
            echo pagination($totalReports, $currentPage, $perPage, $urlPattern);
            ?>
        </div>
    <?php endif; ?>
</div>

<style>
.report-card {
    transition: transform 0.2s ease-in-out;
}

.report-card:hover {
    transform: translateY(-5px);
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.report-info {
    font-size: 0.875rem;
}

.card-header {
    background-color: rgba(0,0,0,0.03);
    border-bottom: 1px solid rgba(0,0,0,0.125);
}
</style>

<script>
// Sayfa yüklendiğinde tooltipleri etkinleştir
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Rapor kartlarına hover efekti
document.querySelectorAll('.report-card').forEach(function(card) {
    card.addEventListener('mouseenter', function() {
        this.style.boxShadow = '0 0.5rem 1rem rgba(0, 0, 0, 0.15)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.boxShadow = '';
    });
});
</script>