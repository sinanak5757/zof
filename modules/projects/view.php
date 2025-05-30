<?php
// Proje detayları görüntüleme sayfası

// Proje ID'sini al
$projectId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ID kontrolü
if ($projectId <= 0) {
    setFlashMessage('danger', 'Geçersiz proje ID\'si.');
    redirect(url('index.php?page=projects'));
}

// Projeyi al
try {
    // Projeyi ve ilişkili bilgileri getir
    $project = $db->getRow("
        SELECT p.*, c.company_name as client_name, u.first_name, u.last_name
        FROM projects p 
        LEFT JOIN clients c ON p.client_id = c.id 
        LEFT JOIN users u ON p.project_manager_id = u.id
        WHERE p.id = ?", [$projectId]);
    
    if (!$project) {
        setFlashMessage('danger', 'Proje bulunamadı.');
        redirect(url('index.php?page=projects'));
    }
    
    // Projeye ait görevleri al
    try {
        $tasks = $db->getAll("
            SELECT t.*, u.first_name as assigned_first_name, u.last_name as assigned_last_name
            FROM tasks t 
            LEFT JOIN users u ON t.assigned_user_id = u.id
            WHERE t.project_id = ? 
            ORDER BY t.created_at DESC", [$projectId]);
    } catch (Exception $e) {
        $tasks = [];
    }
    
    // Projeye ait anahtar kelimeleri al
    try {
        $keywords = $db->getAll("
            SELECT k.*, 
                   k.keyword as keyword,
                   k.current_position as current_rank,
                   k.target_position as target_rank,
                   k.last_check_date as last_checked
            FROM keywords k 
            WHERE k.project_id = ? 
            ORDER BY k.created_at DESC", [$projectId]);
    } catch (Exception $e) {
        $keywords = [];
    }
    
    // Projeye ait içerikleri al
    try {
        $contents = $db->getAll("
            SELECT * FROM content 
            WHERE project_id = ? 
            ORDER BY created_at DESC", [$projectId]);
    } catch (Exception $e) {
        $contents = [];
    }
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Veritabanı hatası: ' . $e->getMessage());
    redirect(url('index.php?page=projects'));
}
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><?php echo htmlspecialchars($project['project_name'] ?? ''); ?></h1>
        <div>
            <a href="<?php echo url('index.php?page=projects&action=edit&id=' . $projectId); ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Düzenle
            </a>
            <a href="<?php echo url('index.php?page=projects'); ?>" class="btn btn-secondary ms-2">
                <i class="bi bi-arrow-left"></i> Geri
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Proje Bilgileri</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Müşteri:</th>
                            <td><?php echo htmlspecialchars($project['client_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <tr>
                            <th>Durum:</th>
                            <td>
                                <?php
                                    $status = $project['status'] ?? '';
                                    $statusClass = '';
                                    $statusText = '';
                                    
                                    switch($status) {
                                        case 'planning':
                                            $statusClass = 'info';
                                            $statusText = 'Planlama';
                                            break;
                                        case 'in_progress':
                                            $statusClass = 'primary';
                                            $statusText = 'Devam Ediyor';
                                            break;
                                        case 'on_hold':
                                            $statusClass = 'warning';
                                            $statusText = 'Beklemede';
                                            break;
                                        case 'completed':
                                            $statusClass = 'success';
                                            $statusText = 'Tamamlandı';
                                            break;
                                        default:
                                            $statusClass = 'secondary';
                                            $statusText = 'Tanımsız';
                                    }
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>Başlangıç Tarihi:</th>
                            <td><?php echo !empty($project['start_date']) ? date('d.m.Y', strtotime($project['start_date'])) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Bitiş Tarihi:</th>
                            <td>
                                <?php 
                                    echo !empty($project['end_date']) 
                                        ? date('d.m.Y', strtotime($project['end_date'])) 
                                        : '-';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Proje Yöneticisi:</th>
                            <td>
                                <?php 
                                if (!empty($project['first_name']) && !empty($project['last_name'])) {
                                    echo htmlspecialchars($project['first_name'] . ' ' . $project['last_name']);
                                } elseif (!empty($project['project_manager_id'])) {
                                    echo 'ID: ' . $project['project_manager_id'];
                                } else {
                                    echo 'Atanmamış';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php if (!empty($project['budget'])): ?>
                        <tr>
                            <th>Bütçe:</th>
                            <td><?php echo number_format($project['budget'], 2) . ' ₺'; ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Proje Açıklaması</h5>
                </div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($project['description'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
                    
                    <?php if (!empty($project['notes'])): ?>
                        <hr>
                        <h6>Notlar:</h6>
                        <?php echo nl2br(htmlspecialchars($project['notes'], ENT_QUOTES, 'UTF-8')); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- İçerikler -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">İçerikler</h5>
            <a href="<?php echo url('index.php?page=content&action=add&project_id=' . $projectId); ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-plus"></i> Yeni İçerik Ekle
            </a>
        </div>
        <div class="card-body">
            <?php if (count($contents) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Başlık</th>
                                <th>Durum</th>
                                <th>Oluşturulma Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contents as $content): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($content['title'] ?? ''); ?></td>
                                    <td>
                                        <?php
                                            $contentStatus = $content['status'] ?? '';
                                            $statusClass = '';
                                            $statusText = '';
                                            
                                            switch($contentStatus) {
                                                case 'published':
                                                    $statusClass = 'success';
                                                    $statusText = 'Yayınlandı';
                                                    break;
                                                case 'draft':
                                                    $statusClass = 'secondary';
                                                    $statusText = 'Taslak';
                                                    break;
                                                case 'pending':
                                                    $statusClass = 'warning';
                                                    $statusText = 'İncelemede';
                                                    break;
                                                case 'archived':
                                                    $statusClass = 'dark';
                                                    $statusText = 'Arşivlendi';
                                                    break;
                                                default:
                                                    $statusClass = 'light';
                                                    $statusText = $contentStatus;
                                            }
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </td>
                                    <td><?php echo !empty($content['created_at']) ? date('d.m.Y H:i', strtotime($content['created_at'])) : '-'; ?></td>
                                    <td>
                                        <a href="<?php echo url('index.php?page=content&action=view&id=' . $content['id']); ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo url('index.php?page=content&action=edit&id=' . $content['id']); ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?php echo url('index.php?page=content&action=delete&id=' . $content['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu içeriği silmek istediğinize emin misiniz?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">Bu projeye henüz içerik eklenmemiş.</p>
                <div class="text-center mt-3">
                    <a href="<?php echo url('index.php?page=content&action=add&project_id=' . $projectId); ?>" class="btn btn-primary">
                        <i class="bi bi-plus"></i> İlk İçeriği Ekle
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Görevler tablosu -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Görevler</h5>
            <a href="<?php echo url('index.php?page=tasks&action=add&project_id=' . $projectId); ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-plus"></i> Yeni Görev
            </a>
        </div>
        <div class="card-body">
            <?php if (count($tasks) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Görev</th>
                                <th>Durum</th>
                                <th>Öncelik</th>
                                <th>Son Tarih</th>
                                <th>Sorumlu</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($task['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php
                                            $taskStatus = $task['status'] ?? '';
                                            $taskStatusClass = '';
                                            $taskStatusText = '';
                                            
                                            switch($taskStatus) {
                                                case 'not_started':
                                                    $taskStatusClass = 'secondary';
                                                    $taskStatusText = 'Başlamadı';
                                                    break;
                                                case 'in_progress':
                                                    $taskStatusClass = 'primary';
                                                    $taskStatusText = 'Devam Ediyor';
                                                    break;
                                                case 'completed':
                                                    $taskStatusClass = 'success';
                                                    $taskStatusText = 'Tamamlandı';
                                                    break;
                                                default:
                                                    $taskStatusClass = 'light';
                                                    $taskStatusText = 'Tanımsız';
                                            }
                                        ?>
                                        <span class="badge bg-<?php echo $taskStatusClass; ?>"><?php echo $taskStatusText; ?></span>
                                    </td>
                                    <td>
                                        <?php
                                            $priority = $task['priority'] ?? '';
                                            $priorityClass = '';
                                            $priorityText = '';
                                            
                                            switch($priority) {
                                                case 'low':
                                                    $priorityClass = 'info';
                                                    $priorityText = 'Düşük';
                                                    break;
                                                case 'medium':
                                                    $priorityClass = 'warning';
                                                    $priorityText = 'Orta';
                                                    break;
                                                case 'high':
                                                    $priorityClass = 'danger';
                                                    $priorityText = 'Yüksek';
                                                    break;
                                                default:
                                                    $priorityClass = 'secondary';
                                                    $priorityText = 'Tanımsız';
                                            }
                                        ?>
                                        <span class="badge bg-<?php echo $priorityClass; ?>"><?php echo $priorityText; ?></span>
                                    </td>
                                    <td><?php echo !empty($task['deadline']) ? date('d.m.Y', strtotime($task['deadline'])) : '-'; ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($task['assigned_first_name']) && !empty($task['assigned_last_name'])) {
                                            echo htmlspecialchars($task['assigned_first_name'] . ' ' . $task['assigned_last_name']);
                                        } else {
                                            echo 'Atanmamış';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo url('index.php?page=tasks&action=view&id=' . $task['id']); ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo url('index.php?page=tasks&action=edit&id=' . $task['id']); ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">Bu projeye henüz görev eklenmemiş.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Anahtar Kelimeler -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Anahtar Kelimeler</h5>
            <a href="<?php echo url('index.php?page=keywords&action=add&project_id=' . $projectId); ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-plus"></i> Yeni Anahtar Kelime
            </a>
        </div>
        <div class="card-body">
            <?php if (count($keywords) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Anahtar Kelime</th>
                                <th>Güncel Sıralama</th>
                                <th>Hedef Sıralama</th>
                                <th>Son Kontrol</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($keywords as $keyword): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($keyword['keyword'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo !empty($keyword['current_rank']) ? $keyword['current_rank'] : '-'; ?></td>
                                    <td><?php echo !empty($keyword['target_rank']) ? $keyword['target_rank'] : '-'; ?></td>
                                    <td><?php echo !empty($keyword['last_checked']) ? date('d.m.Y', strtotime($keyword['last_checked'])) : '-'; ?></td>
                                    <td>
                                        <a href="<?php echo url('index.php?page=keywords&action=view&id=' . $keyword['id']); ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo url('index.php?page=keywords&action=edit&id=' . $keyword['id']); ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">Bu projeye henüz anahtar kelime eklenmemiş.</p>
            <?php endif; ?>
        </div>
    </div>
</div>