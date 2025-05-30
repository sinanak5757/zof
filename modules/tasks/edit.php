<?php
// Görev Düzenleme Sayfası

// Görev ID'sini kontrol et
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('warning', 'Düzenlenecek görev ID belirtilmedi');
    redirect(url('index.php?page=tasks'));
}

$taskId = intval($_GET['id']);

// POST verilerini başlangıçta boş olarak tanımla
$title = '';
$description = '';
$projectId = 0;
$status = '';
$priority = '';
$assigned_to = '';
$deadline = '';

// Mevcut görev verilerini al
try {
    $task = $db->getRow("SELECT * FROM tasks WHERE id = ?", [$taskId]);
    
    if (!$task) {
        setFlashMessage('danger', 'Düzenlenecek görev bulunamadı');
        redirect(url('index.php?page=tasks'));
    }
    
    // Mevcut verileri değişkenlere aktar
    $title = $task['task_name'];
    $description = $task['description'];
    $projectId = $task['project_id'];
    $status = $task['status'];
    $priority = $task['priority'];
    $assigned_to = $task['assigned_to'];
    $deadline = $task['due_date'] ? date('Y-m-d', strtotime($task['due_date'])) : '';
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Veritabanı hatası: ' . $e->getMessage());
    redirect(url('index.php?page=tasks'));
}

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST verilerini al
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $projectId = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $priority = isset($_POST['priority']) ? trim($_POST['priority']) : '';
    $assigned_to = isset($_POST['assigned_to']) ? trim($_POST['assigned_to']) : '';
    $deadline = isset($_POST['deadline']) ? trim($_POST['deadline']) : '';
    
    $errors = [];
    
    // Başlık doğrulama
    if (empty($title)) {
        $errors[] = 'Görev başlığı boş olamaz.';
    }
    
    // Proje ID doğrulama
    if (empty($projectId) || $projectId <= 0) {
        $errors[] = 'Bir proje seçmelisiniz.';
    }
    
    // Hata yoksa veritabanını güncelle
    if (empty($errors)) {
        try {
            // Güncelleme verilerini hazırla
            $data = [
                'task_name' => $title,
                'description' => $description,
                'project_id' => $projectId,
                'status' => $status,
                'priority' => $priority,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // İsteğe bağlı alanlar
            if (!empty($assigned_to)) {
                $data['assigned_to'] = $assigned_to;
            } else {
                $data['assigned_to'] = null;
            }
            
            if (!empty($deadline)) {
                $data['due_date'] = $deadline;
            } else {
                $data['due_date'] = null;
            }
            
            // Veritabanını güncelle
            $updated = $db->update('tasks', $data, 'id = ?', [$taskId]);
            
            if ($updated) {
                setFlashMessage('success', 'Görev başarıyla güncellendi.');
                redirect(url('index.php?page=tasks&action=view&id=' . $taskId));
            } else {
                $errors[] = 'Görev güncellenirken bir hata oluştu.';
            }
        } catch (Exception $e) {
            $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }
    
    // Hataları göster
    if (!empty($errors)) {
        foreach ($errors as $error) {
            setFlashMessage('danger', $error);
        }
    }
}

// Projeler listesini al
try {
    $projects = $db->getAll("SELECT id, project_name FROM projects ORDER BY project_name");
    
    // Kullanıcılar listesini al
    $users = [];
    try {
        $users = $db->getAll("SELECT id, CONCAT(first_name, ' ', last_name) as full_name FROM users ORDER BY first_name");
    } catch (Exception $e) {
        // Kullanıcılar tablosu yoksa veya hata oluşursa boş dizi kullan
    }
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Veritabanı hatası: ' . $e->getMessage());
    redirect(url('index.php?page=tasks'));
}

// Durum ve öncelik seçenekleri
$statusOptions = [
    'not_started' => 'Başlamadı',
    'in_progress' => 'Devam Ediyor',
    'completed' => 'Tamamlandı'
];

$priorityOptions = [
    'low' => 'Düşük',
    'medium' => 'Orta',
    'high' => 'Yüksek'
];

// Sayfa başlığı
$pageTitle = 'Görev Düzenle: ' . htmlspecialchars($title);
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Görev Düzenle</h4>
        <a href="<?= url('index.php?page=tasks&action=view&id=' . $taskId) ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Geri
        </a>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="<?= url('index.php?page=tasks&action=edit&id=' . $taskId) ?>" method="post" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="project_id" class="form-label">Proje *</label>
                    <select name="project_id" id="project_id" class="form-select" required>
                        <option value="">Proje seçiniz</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" <?= $projectId == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['project_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Görevin ait olduğu projeyi seçin.</div>
                </div>
                
                <div class="mb-3">
                    <label for="title" class="form-label">Görev Başlığı *</label>
                    <input type="text" class="form-control" id="title" name="title" required value="<?= htmlspecialchars($title) ?>">
                    <div class="form-text">Görev için kısa ve açıklayıcı bir başlık girin.</div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Açıklama</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($description) ?></textarea>
                    <div class="form-text">Görevin detaylı açıklamasını girin.</div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Durum</label>
                        <select name="status" id="status" class="form-select">
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $status == $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="priority" class="form-label">Öncelik</label>
                        <select name="priority" id="priority" class="form-select">
                            <?php foreach ($priorityOptions as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $priority == $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="deadline" class="form-label">Son Tarih</label>
                        <input type="date" class="form-control" id="deadline" name="deadline" value="<?= htmlspecialchars($deadline) ?>">
                    </div>
                </div>
                
                <?php if (!empty($users)): ?>
                <div class="mb-3">
                    <label for="assigned_to" class="form-label">Atanan Kişi</label>
                    <select name="assigned_to" id="assigned_to" class="form-select">
                        <option value="">Atanan kişi seçiniz</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= $assigned_to == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?= url('index.php?page=tasks&action=view&id=' . $taskId) ?>" class="btn btn-light me-md-2">İptal</a>
                    <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>