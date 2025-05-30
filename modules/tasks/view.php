<?php
// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    // Oturum kontrolü başka yerde yapılıyor olabilir
}

// Görev ID'si kontrol et
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('warning', 'Görev ID belirtilmedi');
    redirect(url('index.php?page=tasks'));
}

$taskId = intval($_GET['id']);

// Görevi veritabanından al
try {
    $task = $db->getRow("SELECT t.*, 
                         p.project_name,
                         CONCAT(u.first_name, ' ', u.last_name) as assigned_name 
                         FROM tasks t
                         LEFT JOIN projects p ON t.project_id = p.id
                         LEFT JOIN users u ON t.assigned_to = u.id
                         WHERE t.id = ?", [$taskId]);
    
    if (!$task) {
        setFlashMessage('danger', 'Görev bulunamadı');
        redirect(url('index.php?page=tasks'));
    }
    
    // Görev durumlarını hazırla
    $statuses = [
        'not_started' => ['label' => 'Başlamadı', 'color' => 'secondary'],
        'in_progress' => ['label' => 'Devam Ediyor', 'color' => 'primary'],
        'completed' => ['label' => 'Tamamlandı', 'color' => 'success']
    ];
    
    // Öncelik seviyeleri
    $priorities = [
        'low' => ['label' => 'Düşük', 'color' => 'secondary'],
        'medium' => ['label' => 'Orta', 'color' => 'primary'],
        'high' => ['label' => 'Yüksek', 'color' => 'danger']
    ];
    
    // Görev durumu güncelleme
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $newStatus = isset($_POST['status']) ? trim($_POST['status']) : '';
        
        if (!empty($newStatus) && array_key_exists($newStatus, $statuses)) {
            $db->update('tasks', ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$taskId]);
            
            setFlashMessage('success', 'Görev durumu güncellendi');
            redirect(url('index.php?page=tasks&action=view&id=' . $taskId));
        }
    }
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Veritabanı hatası: ' . $e->getMessage());
    redirect(url('index.php?page=tasks'));
}

// Sayfa başlığını ayarla
$pageTitle = 'Görev: ' . (isset($task['task_name']) ? $task['task_name'] : '');

// header.php ve footer.php include işlemlerini kaldırıyoruz
// Bunlar muhtemelen ana index.php dosyasında zaten dahil ediliyor
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Görev Detayları</h6>
                    <div>
                        <a href="<?= url('index.php?page=tasks&action=edit&id=' . $taskId) ?>" class="btn btn-sm btn-primary mr-2">
                            <i class="fas fa-edit"></i> Düzenle
                        </a>
                        <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteTaskModal">
                            <i class="fas fa-trash"></i> Sil
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <h4 class="mb-3"><?= isset($task['task_name']) ? htmlspecialchars($task['task_name']) : '' ?></h4>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Proje:</strong> 
                                <?php if(isset($task['project_id']) && $task['project_id']): ?>
                                    <a href="<?= url('index.php?page=projects&action=view&id=' . $task['project_id']) ?>">
                                        <?= htmlspecialchars($task['project_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Proje atanmamış</span>
                                <?php endif; ?>
                            </p>
                            
                            <p><strong>Durum:</strong>
                                <?php 
                                $taskStatus = isset($task['status']) ? $task['status'] : '';
                                $statusInfo = isset($statuses[$taskStatus]) ? $statuses[$taskStatus] : ['label' => 'Bilinmiyor', 'color' => 'secondary'];
                                ?>
                                <span class="badge badge-<?= $statusInfo['color'] ?>">
                                    <?= htmlspecialchars($statusInfo['label']) ?>
                                </span>
                            </p>
                            
                            <p><strong>Öncelik:</strong>
                                <?php 
                                $taskPriority = isset($task['priority']) ? $task['priority'] : '';
                                $priorityInfo = isset($priorities[$taskPriority]) ? $priorities[$taskPriority] : ['label' => 'Orta', 'color' => 'primary'];
                                ?>
                                <span class="badge badge-<?= $priorityInfo['color'] ?>">
                                    <?= htmlspecialchars($priorityInfo['label']) ?>
                                </span>
                            </p>
                        </div>
                        
                        <div class="col-md-6">
                            <p><strong>Atanan Kişi:</strong>
                                <?php if(isset($task['assigned_to']) && $task['assigned_to']): ?>
                                    <?= htmlspecialchars($task['assigned_name']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Atanmamış</span>
                                <?php endif; ?>
                            </p>
                            
                            <p><strong>Son Tarih:</strong>
                                <?php 
                                if(isset($task['due_date']) && $task['due_date']): 
                                    echo date('d.m.Y', strtotime($task['due_date']));
                                    
                                    // Gecikme kontrolü
                                    $now = new DateTime();
                                    $dueDate = new DateTime($task['due_date']);
                                    
                                    if($task['status'] != 'completed' && $dueDate < $now):
                                ?>
                                    <span class="badge badge-danger ml-2">Gecikmiş</span>
                                <?php 
                                    endif;
                                else: 
                                ?>
                                    <span class="text-muted">Belirtilmemiş</span>
                                <?php endif; ?>
                            </p>
                            
                            <p><strong>Oluşturulma:</strong> 
                                <?= isset($task['created_at']) ? date('d.m.Y H:i', strtotime($task['created_at'])) : '' ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2">Açıklama</h5>
                        <div class="p-3 bg-light rounded">
                            <?php if(isset($task['description']) && !empty($task['description'])): ?>
                                <?= nl2br(htmlspecialchars($task['description'])) ?>
                            <?php else: ?>
                                <span class="text-muted">Açıklama bulunmuyor</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Yorumlar özelliği için task_comments tablosu gerekiyor -->
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle mr-2"></i>
                        Görev yorumları özelliği şu anda kullanılamıyor.
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Hızlı İşlemler Kartı -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Hızlı İşlemler</h6>
                </div>
                <div class="card-body">
                    <form action="<?= url('index.php?page=tasks&action=view&id=' . $taskId) ?>" method="post" class="mb-3">
                        <input type="hidden" name="action" value="update_status">
                        <div class="form-group">
                            <label for="status">Durumu Güncelle</label>
                            <select name="status" id="status" class="form-control">
                                <?php foreach($statuses as $key => $status): ?>
                                    <option value="<?= $key ?>" <?= (isset($task['status']) && $task['status'] == $key) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($status['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Durumu Güncelle</button>
                    </form>
                    
                    <div class="d-grid gap-2">
                        <a href="<?= url('index.php?page=tasks&action=edit&id=' . $taskId) ?>" class="btn btn-info btn-block mb-2">
                            <i class="fas fa-edit mr-1"></i> Görevi Düzenle
                        </a>
                        
                        <button type="button" class="btn btn-danger btn-block" data-toggle="modal" data-target="#deleteTaskModal">
                            <i class="fas fa-trash mr-1"></i> Görevi Sil
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- İlgili Görevler Kartı -->
            <?php if(isset($task['project_id']) && $task['project_id']): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">İlgili Görevler</h6>
                </div>
                <div class="card-body">
                    <?php
                    // Projedeki diğer görevleri al
                    $relatedTasks = $db->getAll("SELECT id, task_name, status FROM tasks 
                                               WHERE project_id = ? AND id != ? 
                                               ORDER BY due_date ASC
                                               LIMIT 5", [$task['project_id'], $taskId]);
                    ?>
                    
                    <?php if(isset($relatedTasks) && count($relatedTasks) > 0): ?>
                        <div class="list-group">
                            <?php foreach($relatedTasks as $relTask): ?>
                                <a href="<?= url('index.php?page=tasks&action=view&id=' . $relTask['id']) ?>" 
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($relTask['task_name']) ?>
                                    
                                    <?php if($relTask['status'] == 'completed'): ?>
                                        <span class="badge badge-success badge-pill"><i class="fas fa-check"></i></span>
                                    <?php elseif($relTask['status'] == 'in_progress'): ?>
                                        <span class="badge badge-primary badge-pill"><i class="fas fa-sync"></i></span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary badge-pill"><i class="fas fa-clock"></i></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-3">
                            <a href="<?= url('index.php?page=tasks&project_id=' . $task['project_id']) ?>" class="btn btn-sm btn-outline-primary btn-block">
                                Tüm Proje Görevlerini Gör
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Bu projede başka görev bulunmuyor.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Görev Silme Onay Modalı -->
<div class="modal fade" id="deleteTaskModal" tabindex="-1" role="dialog" aria-labelledby="deleteTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTaskModalLabel">Görevi Sil</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Bu görevi silmek istediğinize emin misiniz? Bu işlem geri alınamaz.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                <a href="<?= url('index.php?page=tasks&action=delete&id=' . $taskId) ?>" class="btn btn-danger">Görevi Sil</a>
            </div>
        </div>
    </div>
</div>