<?php
// Projeler listesi

// Sayfalama için toplam proje sayısı
$totalProjects = $db->getRow("SELECT COUNT(*) as count FROM projects")['count'];
$perPage = 10;
$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($currentPage - 1) * $perPage;

// Arama filtresi
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$clientFilter = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// SQL sorgusu oluştur
$sql = "
    SELECT p.*, c.company_name, u.first_name, u.last_name
    FROM projects p
    JOIN clients c ON p.client_id = c.id
    LEFT JOIN users u ON p.project_manager_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($searchTerm)) {
    $sql .= " AND (p.project_name LIKE ? OR c.company_name LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params = array_merge($params, [$searchParam, $searchParam]);
}

if (!empty($statusFilter)) {
    $sql .= " AND p.status = ?";
    $params[] = $statusFilter;
}

if ($clientFilter > 0) {
    $sql .= " AND p.client_id = ?";
    $params[] = $clientFilter;
}

$sql .= " ORDER BY p.start_date DESC LIMIT $offset, $perPage";

// Hata yakalama
try {
    // Projeleri al
    $projects = $db->getAll($sql, $params);
    
    // Müşteri listesini al (filtre için)
    $clients = $db->getAll("SELECT id, company_name FROM clients ORDER BY company_name ASC");
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Proje verileri yüklenirken bir hata oluştu: ' . $e->getMessage() . '</div>';
    $projects = [];
    $clients = [];
}

// Durum sınıfları ve isimleri
$statusClasses = [
    'planning' => 'bg-info',
    'in_progress' => 'bg-primary',
    'on_hold' => 'bg-warning',
    'completed' => 'bg-success'
];

$statusNames = [
    'planning' => 'Planlama',
    'in_progress' => 'Devam Ediyor',
    'on_hold' => 'Beklemede',
    'completed' => 'Tamamlandı'
];
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Projeler</h4>
        <a href="<?php echo url('index.php?page=projects&action=add'); ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Yeni Proje
        </a>
    </div>

    <!-- Filtreler -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <input type="hidden" name="page" value="projects">
                
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Proje veya müşteri ara..." name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <select name="client_id" class="form-select">
                        <option value="0">Tüm Müşteriler</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo $clientFilter === $client['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['company_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Tüm Durumlar</option>
                        <option value="planning" <?php echo $statusFilter === 'planning' ? 'selected' : ''; ?>>Planlama</option>
                        <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>Devam Ediyor</option>
                        <option value="on_hold" <?php echo $statusFilter === 'on_hold' ? 'selected' : ''; ?>>Beklemede</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Tamamlandı</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <div class="d-grid">
                        <a href="<?php echo url('index.php?page=projects'); ?>" class="btn btn-outline-secondary">Sıfırla</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Proje Listesi -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Proje Adı</th>
                            <th>Müşteri</th>
                            <th>Durum</th>
                            <th>Başlangıç Tarihi</th>
                            <th>Bitiş Tarihi</th>
                            <th>Proje Yöneticisi</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($projects) > 0): ?>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                                    <td><?php echo htmlspecialchars($project['company_name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $statusClasses[$project['status']] ?? 'bg-secondary'; ?>">
                                            <?php echo $statusNames[$project['status']] ?? $project['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d.m.Y', strtotime($project['start_date'])); ?></td>
                                    <td><?php echo $project['end_date'] ? date('d.m.Y', strtotime($project['end_date'])) : '-'; ?></td>
                                    <td>
                                        <?php 
                                        if ($project['project_manager_id']) {
                                            echo htmlspecialchars($project['first_name'] . ' ' . $project['last_name']);
                                        } else {
                                            echo '<span class="text-muted">Atanmamış</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?php echo url('index.php?page=projects&action=view&id=' . $project['id']); ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Görüntüle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo url('index.php?page=projects&action=edit&id=' . $project['id']); ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Düzenle">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?php echo url('index.php?page=projects&action=delete&id=' . $project['id']); ?>" class="btn btn-sm btn-outline-danger confirm-action" data-bs-toggle="tooltip" title="Sil">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <?php if (!empty($searchTerm) || !empty($statusFilter) || $clientFilter > 0): ?>
                                        Arama kriterlerinize uygun proje bulunamadı.
                                    <?php else: ?>
                                        Henüz proje bulunmuyor. Eklemek için "Yeni Proje" butonunu kullanabilirsiniz.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>