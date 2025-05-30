<?php
// İçerik listesi

// Sayfalama için toplam içerik sayısı
$totalContent = $db->getRow("SELECT COUNT(*) as count FROM content")['count'];
$perPage = 10;
$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($currentPage - 1) * $perPage;

// Filtreler
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$categoryFilter = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$projectFilter = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

// SQL sorgusu oluştur
$sql = "
    SELECT c.*, cat.name as category_name, p.project_name
    FROM content c
    LEFT JOIN content_categories cat ON c.category_id = cat.id
    LEFT JOIN projects p ON c.project_id = p.id
    WHERE 1=1
";
$params = [];

if (!empty($searchTerm)) {
    $sql .= " AND (c.title LIKE ? OR c.content LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params = array_merge($params, [$searchParam, $searchParam]);
}

if (!empty($statusFilter)) {
    $sql .= " AND c.status = ?";
    $params[] = $statusFilter;
}

if ($categoryFilter > 0) {
    $sql .= " AND c.category_id = ?";
    $params[] = $categoryFilter;
}

if ($projectFilter > 0) {
    $sql .= " AND c.project_id = ?";
    $params[] = $projectFilter;
}

$sql .= " ORDER BY c.id DESC LIMIT $offset, $perPage";

// Filtreler için kategorileri ve projeleri al
try {
    $categories = $db->getAll("
        SELECT id, name as category_name
        FROM content_categories
        ORDER BY category_name ASC
    ");
    
    $projects = $db->getAll("
        SELECT id, project_name
        FROM projects
        ORDER BY project_name ASC
    ");
    
    // İçerikleri al
    $contents = $db->getAll($sql, $params);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Veri yüklenirken bir hata oluştu: ' . $e->getMessage() . '</div>';
    $categories = [];
    $projects = [];
    $contents = [];
}

// Durum sınıfları ve isimleri
$statusClasses = [
    'draft' => 'bg-secondary',
    'pending' => 'bg-primary',
    'published' => 'bg-success',
    'archived' => 'bg-dark'
];

$statusNames = [
    'draft' => 'Taslak',
    'pending' => 'İncelemede',
    'published' => 'Yayınlandı',
    'archived' => 'Arşivlendi'
];
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>İçerik Yönetimi</h4>
        <div>
            <a href="<?php echo url('index.php?page=content&action=calendar'); ?>" class="btn btn-outline-primary me-2">
                <i class="bi bi-calendar3 me-1"></i> İçerik Takvimi
            </a>
            <a href="<?php echo url('index.php?page=content&action=add'); ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Yeni İçerik
            </a>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <input type="hidden" name="page" value="content">
                
                <div class="col-md-3">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="İçerik ara..." name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <select name="category_id" class="form-select">
                        <option value="0">Tüm Kategoriler</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $categoryFilter === $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <select name="project_id" class="form-select">
                        <option value="0">Tüm Projeler</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" <?php echo $projectFilter === $project['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['project_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Tüm Durumlar</option>
                        <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Taslak</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>İncelemede</option>
                        <option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>Yayınlandı</option>
                        <option value="archived" <?php echo $statusFilter === 'archived' ? 'selected' : ''; ?>>Arşivlendi</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <div class="d-grid">
                        <a href="<?php echo url('index.php?page=content'); ?>" class="btn btn-outline-secondary">Sıfırla</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- İçerik Listesi -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Başlık</th>
                            <th>Kategori</th>
                            <th>Proje</th>
                            <th>Durum</th>
                            <th>Oluşturulma Tarihi</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($contents) > 0): ?>
                            <?php foreach ($contents as $content): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($content['title']); ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($content['category_name'])) {
                                            echo htmlspecialchars($content['category_name']);
                                        } else {
                                            echo '<span class="text-muted">Kategori yok</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($content['project_name'])) {
                                            echo htmlspecialchars($content['project_name']);
                                        } else {
                                            echo '<span class="text-muted">Proje yok</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $statusClasses[$content['status']] ?? 'bg-secondary'; ?>">
                                            <?php echo $statusNames[$content['status']] ?? $content['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('d.m.Y H:i', strtotime($content['created_at'])); ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?php echo url('index.php?page=content&action=view&id=' . $content['id']); ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Görüntüle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo url('index.php?page=content&action=edit&id=' . $content['id']); ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Düzenle">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?php echo url('index.php?page=content&action=delete&id=' . $content['id']); ?>" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Sil" onclick="return confirm('Bu içeriği silmek istediğinize emin misiniz?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <?php if (!empty($searchTerm) || !empty($statusFilter) || $categoryFilter > 0 || $projectFilter > 0): ?>
                                        Arama kriterlerinize uygun içerik bulunamadı.
                                    <?php else: ?>
                                        Henüz içerik bulunmuyor. Eklemek için "Yeni İçerik" butonunu kullanabilirsiniz.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Sayfalama -->
        <?php if ($totalContent > $perPage): ?>
            <div class="card-footer">
                <?php
                $totalPages = ceil($totalContent / $perPage);
                $urlPattern = url('index.php?page=content&p=%d');
                
                if (!empty($searchTerm)) {
                    $urlPattern .= '&search=' . urlencode($searchTerm);
                }
                
                if (!empty($statusFilter)) {
                    $urlPattern .= '&status=' . urlencode($statusFilter);
                }
                
                if ($categoryFilter > 0) {
                    $urlPattern .= '&category_id=' . $categoryFilter;
                }
                
                if ($projectFilter > 0) {
                    $urlPattern .= '&project_id=' . $projectFilter;
                }
                
                echo pagination($totalContent, $currentPage, $perPage, $urlPattern);
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>