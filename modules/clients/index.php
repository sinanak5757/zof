<?php
// Müşteriler listesi

// Arama filtresi
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// SQL sorgusu oluştur
$sql = "SELECT * FROM clients WHERE 1=1";
$params = [];

if (!empty($searchTerm)) {
    $sql .= " AND (company_name LIKE ? OR contact_person LIKE ? OR contact_email LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if (!empty($statusFilter)) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY company_name ASC";

// Hata yakalama
try {
    // Müşterileri al
    $clients = $db->getAll($sql, $params);
} catch (Exception $e) {
    $error_message = 'Müşteri verileri yüklenirken bir hata oluştu: ' . $e->getMessage();
    $clients = [];
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Müşteriler</h4>
        <a href="<?php echo url('index.php?page=clients&action=add'); ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Yeni Müşteri
        </a>
    </div>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Filtreler -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <input type="hidden" name="page" value="clients">
                
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Müşteri ara..." name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">Tüm Durumlar</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Pasif</option>
                        <option value="prospect" <?php echo $statusFilter === 'prospect' ? 'selected' : ''; ?>>Potansiyel</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <div class="d-grid">
                        <a href="<?php echo url('index.php?page=clients'); ?>" class="btn btn-outline-secondary">Sıfırla</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Müşteri Listesi -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Şirket Adı</th>
                            <th>İlgili Kişi</th>
                            <th>Durum</th>
                            <th>Müşteri Olma Tarihi</th>
                            <th>Web Sitesi</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($clients) > 0): ?>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($client['contact_person']); ?></td>
                                    <td>
                                        <?php
                                        $statusClasses = [
                                            'active' => 'badge bg-success',
                                            'inactive' => 'badge bg-danger',
                                            'prospect' => 'badge bg-warning'
                                        ];
                                        $statusNames = [
                                            'active' => 'Aktif',
                                            'inactive' => 'Pasif',
                                            'prospect' => 'Potansiyel'
                                        ];
                                        ?>
                                        <span class="<?php echo $statusClasses[$client['status']] ?? 'badge bg-secondary'; ?>"><?php echo $statusNames[$client['status']] ?? 'Bilinmiyor'; ?></span>
                                    </td>
                                    <td><?php echo date('d.m.Y', strtotime($client['client_since'])); ?></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($client['website']); ?>" target="_blank" class="text-decoration-none">
                                            <?php echo htmlspecialchars($client['website']); ?> <i class="bi bi-box-arrow-up-right small"></i>
                                        </a>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?php echo url('index.php?page=clients&action=view&id=' . $client['id']); ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Görüntüle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo url('index.php?page=clients&action=edit&id=' . $client['id']); ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Düzenle">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?php echo url('index.php?page=clients&action=delete&id=' . $client['id'] . '&csrf=' . generateCsrfToken()); ?>" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Sil">
											<i class="bi bi-trash"></i>
										</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <?php if (!empty($searchTerm) || !empty($statusFilter)): ?>
                                        Arama kriterlerinize uygun müşteri bulunamadı.
                                    <?php else: ?>
                                        Henüz müşteri bulunmuyor. Eklemek için "Yeni Müşteri" butonunu kullanabilirsiniz.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>