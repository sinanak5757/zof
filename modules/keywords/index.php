<?php
// keywords/index.php - Anahtar Kelime Listesi

// Sayfalama için toplam anahtar kelime sayısı
$totalKeywords = $db->getRow("SELECT COUNT(*) as count FROM keywords")['count'];
$perPage = 10;
$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($currentPage - 1) * $perPage;

// Filtreler
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$projectFilter = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$priorityFilter = isset($_GET['priority']) ? $_GET['priority'] : '';

// SQL sorgusu oluştur
$sql = "
    SELECT k.*, p.project_name
    FROM keywords k
    LEFT JOIN projects p ON k.project_id = p.id
    WHERE 1=1
";
$params = [];

if (!empty($searchTerm)) {
    $sql .= " AND (k.keyword LIKE ? OR p.project_name LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params = array_merge($params, [$searchParam, $searchParam]);
}

if ($projectFilter > 0) {
    $sql .= " AND k.project_id = ?";
    $params[] = $projectFilter;
}

if (!empty($priorityFilter)) {
    switch($priorityFilter) {
        case 'urgent':
            $sql .= " AND k.current_position > 20 AND k.target_position <= 10";
            break;
        case 'good':
            $sql .= " AND k.current_position <= 10";
            break;
        case 'needs_work':
            $sql .= " AND (k.current_position > k.target_position * 2 OR k.current_position IS NULL)";
            break;
    }
}

$sql .= " ORDER BY k.id DESC LIMIT $offset, $perPage";

// Tablodaki mevcut sütunları kontrol et
try {
    $tableColumns = $db->getAll("SHOW COLUMNS FROM keywords");
    $existingColumns = array_column($tableColumns, 'Field');
} catch (Exception $e) {
    $existingColumns = ['keyword', 'project_id']; // Minimum sütunlar
}

// Filtreler için projeleri al
try {
    $projects = $db->getAll("
        SELECT id, project_name
        FROM projects
        ORDER BY project_name ASC
    ");
    
    // Anahtar kelimeleri al
    $keywords = $db->getAll($sql, $params);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Veri yüklenirken bir hata oluştu: ' . $e->getMessage() . '</div>';
    $projects = [];
    $keywords = [];
}

// Öncelik sınıfları ve isimleri
$priorityClasses = [
    'urgent' => 'bg-danger',
    'good' => 'bg-success',
    'needs_work' => 'bg-warning'
];

$priorityNames = [
    'urgent' => 'Acil',
    'good' => 'İyi',
    'needs_work' => 'Çalışma Gerekli'
];
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Anahtar Kelimeler</h4>
        <a href="<?php echo url('index.php?page=keywords&action=add'); ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Yeni Anahtar Kelime
        </a>
    </div>

    <!-- Filtreler -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <input type="hidden" name="page" value="keywords">
                
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Anahtar kelime, proje veya müşteri ara..." name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <select name="project_id" class="form-select">
                        <option value="0">Tüm Projeler</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" <?php echo $projectFilter === (int)$project['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['project_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <select name="priority" class="form-select">
                        <option value="">Tüm Öncelikler</option>
                        <option value="urgent" <?php echo $priorityFilter === 'urgent' ? 'selected' : ''; ?>>Acil</option>
                        <option value="good" <?php echo $priorityFilter === 'good' ? 'selected' : ''; ?>>İyi</option>
                        <option value="needs_work" <?php echo $priorityFilter === 'needs_work' ? 'selected' : ''; ?>>Çalışma Gerekli</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <div class="d-grid">
                        <a href="<?php echo url('index.php?page=keywords'); ?>" class="btn btn-outline-secondary">Sıfırla</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Anahtar Kelime Listesi -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Anahtar Kelime</th>
                            <th>Proje</th>
                            <th>Öncelik</th>
                            <?php if (in_array('current_position', $existingColumns)): ?>
                            <th>Mevcut Pozisyon</th>
                            <?php endif; ?>
                            <th>İlk Pozisyon</th>
                            <th>Hedef Pozisyon</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($keywords) > 0): ?>
                            <?php foreach ($keywords as $keyword): ?>
                                <?php
                                // Pozisyon durumunu belirle
                                $currentPos = isset($keyword['current_position']) ? $keyword['current_position'] : null;
                                $targetPos = isset($keyword['target_position']) ? $keyword['target_position'] : 1;
                                
                                $priorityClass = 'bg-secondary';
                                $priorityText = 'Orta';
                                
                                if ($currentPos) {
                                    if ($currentPos <= 3) {
                                        $priorityClass = 'bg-success';
                                        $priorityText = 'Mükemmel';
                                    } elseif ($currentPos <= 10) {
                                        $priorityClass = 'bg-primary';
                                        $priorityText = 'İyi';
                                    } elseif ($currentPos <= 20) {
                                        $priorityClass = 'bg-warning';
                                        $priorityText = 'Orta';
                                    } else {
                                        $priorityClass = 'bg-danger';
                                        $priorityText = 'Acil';
                                    }
                                } else {
                                    $priorityClass = 'bg-secondary';
                                    $priorityText = 'Veri Yok';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($keyword['keyword']); ?></strong>
                                        <?php if (in_array('search_volume', $existingColumns) && !empty($keyword['search_volume'])): ?>
                                        <br><small class="text-muted">Arama: <?php echo number_format($keyword['search_volume']); ?>/ay</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($keyword['project_name'])) {
                                            echo htmlspecialchars($keyword['project_name']);
                                        } else {
                                            echo '<span class="text-muted">Proje yok</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $priorityClass; ?>">
                                            <?php echo $priorityText; ?>
                                        </span>
                                    </td>
                                    <?php if (in_array('current_position', $existingColumns)): ?>
                                    <td>
                                        <?php if ($currentPos): ?>
                                            <span class="fw-bold"><?php echo $currentPos; ?></span>
                                            <?php if (in_array('last_check_date', $existingColumns) && !empty($keyword['last_check_date'])): ?>
                                            <br><small class="text-muted"><?php echo date('d.m.Y', strtotime($keyword['last_check_date'])); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                    <td>
                                        <?php if ($currentPos): ?>
                                            <?php if ($currentPos <= $targetPos): ?>
                                                <span class="text-success fw-bold"><?php echo $currentPos; ?></span>
                                                <small class="text-success"> ✓</small>
                                            <?php else: ?>
                                                <span class="text-danger fw-bold"><?php echo $currentPos; ?></span>
                                                <small class="text-danger"> (<?php echo $currentPos - $targetPos; ?>)</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success"><?php echo $targetPos; ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?php echo url('index.php?page=keywords&action=view&id=' . $keyword['id']); ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Görüntüle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo url('index.php?page=keywords&action=edit&id=' . $keyword['id']); ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Düzenle">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?php echo url('index.php?page=keywords&action=delete&id=' . $keyword['id']); ?>" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Sil" onclick="return confirm('Bu anahtar kelimeyi silmek istediğinize emin misiniz?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo in_array('current_position', $existingColumns) ? '7' : '6'; ?>" class="text-center py-4">
                                    <?php if (!empty($searchTerm) || $projectFilter > 0 || !empty($priorityFilter)): ?>
                                        Arama kriterlerinize uygun anahtar kelime bulunamadı.
                                    <?php else: ?>
                                        Henüz anahtar kelime bulunmuyor. Eklemek için "Yeni Anahtar Kelime" butonunu kullanabilirsiniz.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Sayfalama -->
        <?php if ($totalKeywords > $perPage): ?>
            <div class="card-footer">
                <?php
                $totalPages = ceil($totalKeywords / $perPage);
                $urlPattern = url('index.php?page=keywords&p=%d');
                
                if (!empty($searchTerm)) {
                    $urlPattern .= '&search=' . urlencode($searchTerm);
                }
                
                if ($projectFilter > 0) {
                    $urlPattern .= '&project_id=' . $projectFilter;
                }
                
                if (!empty($priorityFilter)) {
                    $urlPattern .= '&priority=' . urlencode($priorityFilter);
                }
                
                echo pagination($totalKeywords, $currentPage, $perPage, $urlPattern);
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Özet İstatistikleri -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">
                            <?php 
                            try {
                                $topRanking = $db->getRow("SELECT COUNT(*) as count FROM keywords WHERE current_position <= 10 AND current_position IS NOT NULL")['count'];
                                echo $topRanking;
                            } catch (Exception $e) {
                                echo '0';
                            }
                            ?>
                        </h4>
                        <p class="mb-0">İlk 10'da</p>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-trophy-fill fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">
                            <?php 
                            try {
                                $tracking = $db->getRow("SELECT COUNT(*) as count FROM keywords WHERE current_position IS NOT NULL")['count'];
                                echo $tracking;
                            } catch (Exception $e) {
                                echo '0';
                            }
                            ?>
                        </h4>
                        <p class="mb-0">Takip Ediliyor</p>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-graph-up fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">
                            <?php 
                            try {
                                $needsWork = $db->getRow("SELECT COUNT(*) as count FROM keywords WHERE current_position > target_position * 2 OR current_position IS NULL")['count'];
                                echo $needsWork;
                            } catch (Exception $e) {
                                echo '0';
                            }
                            ?>
                        </h4>
                        <p class="mb-0">Çalışma Gerekli</p>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-exclamation-triangle-fill fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo $totalKeywords; ?></h4>
                        <p class="mb-0">Toplam Anahtar Kelime</p>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-search fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Tooltip'leri etkinleştir
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>