<?php
// Görev Silme Sayfası

// Görev ID'sini kontrol et
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('warning', 'Silinecek görev ID belirtilmedi');
    redirect(url('index.php?page=tasks'));
}

$taskId = intval($_GET['id']);

// Onay parametresini kontrol et (güvenlik için)
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

// Görevi veritabanından al (silme işleminden önce bilgileri almak için)
try {
    $task = $db->getRow("SELECT t.*, p.project_name 
                        FROM tasks t
                        LEFT JOIN projects p ON t.project_id = p.id
                        WHERE t.id = ?", [$taskId]);
    
    if (!$task) {
        setFlashMessage('danger', 'Silinecek görev bulunamadı');
        redirect(url('index.php?page=tasks'));
    }

    $projectId = $task['project_id']; // Silme sonrası yönlendirme için
    
    // Eğer onay varsa, silme işlemini gerçekleştir
    if ($confirmed) {
        // Görevi sil
        $deleted = $db->delete('tasks', 'id = ?', [$taskId]);
        
        if ($deleted) {
            setFlashMessage('success', 'Görev başarıyla silindi.');
            
            // Projeler sayfasına veya görevler sayfasına yönlendir
            if ($projectId > 0) {
                redirect(url('index.php?page=projects&action=view&id=' . $projectId));
            } else {
                redirect(url('index.php?page=tasks'));
            }
        } else {
            setFlashMessage('danger', 'Görev silinirken bir hata oluştu.');
            redirect(url('index.php?page=tasks&action=view&id=' . $taskId));
        }
    }
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Veritabanı hatası: ' . $e->getMessage());
    redirect(url('index.php?page=tasks'));
}

// Sayfa başlığı
$pageTitle = 'Görev Sil: ' . htmlspecialchars($task['task_name']);
?>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Görev Silme Onayı</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5>Bu görevi silmek istediğinize emin misiniz?</h5>
                        <p class="text-muted">Bu işlem geri alınamaz.</p>
                    </div>
                    
                    <div class="alert alert-secondary">
                        <div class="row">
                            <div class="col-md-3 font-weight-bold">Görev:</div>
                            <div class="col-md-9"><?= htmlspecialchars($task['task_name']) ?></div>
                        </div>
                        
                        <?php if ($task['project_id'] && isset($task['project_name'])): ?>
                        <div class="row mt-2">
                            <div class="col-md-3 font-weight-bold">Proje:</div>
                            <div class="col-md-9"><?= htmlspecialchars($task['project_name']) ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($task['description']) && !empty($task['description'])): ?>
                        <div class="row mt-2">
                            <div class="col-md-3 font-weight-bold">Açıklama:</div>
                            <div class="col-md-9"><?= nl2br(htmlspecialchars(substr($task['description'], 0, 100))) ?>
                                <?= (strlen($task['description']) > 100) ? '...' : '' ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?= url('index.php?page=tasks&action=view&id=' . $taskId) ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Vazgeç
                        </a>
                        <a href="<?= url('index.php?page=tasks&action=delete&id=' . $taskId . '&confirm=yes') ?>" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i> Evet, Görevi Sil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>