<?php
// Görev Ekleme Sayfası

// URL'den proje ID parametresini al (varsa)
$projectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

// POST verilerini al
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'not_started';
$priority = isset($_POST['priority']) ? trim($_POST['priority']) : 'medium';
$assigned_to = isset($_POST['assigned_to']) ? trim($_POST['assigned_to']) : '';
$deadline = isset($_POST['deadline']) ? trim($_POST['deadline']) : '';

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Proje ID doğrulama
    if (empty($_POST['project_id']) || intval($_POST['project_id']) <= 0) {
        $errors[] = 'Bir proje seçmelisiniz.';
    } else {
        $projectId = intval($_POST['project_id']);
    }
    
    // Başlık doğrulama
    if (empty($title)) {
        $errors[] = 'Görev başlığı boş olamaz.';
    }
    
    // Hata yoksa veritabanına ekle
    if (empty($errors)) {
        try {
            // Temel veri dizisini oluştur - doğru sütun adlarıyla
            $data = [
                'project_id' => $projectId,
                'task_name' => $title, // Doğru sütun adını kullanıyoruz
                'description' => $description,
                'status' => $status,
                'priority' => $priority
            ];
            
            // İsteğe bağlı alanlar
            if (!empty($assigned_to)) {
                $data['assigned_to'] = $assigned_to;
            }
            
            if (!empty($deadline)) {
                $data['due_date'] = $deadline; // Doğru sütun adı: due_date
            }
            
            // Oluşturma ve güncelleme zamanları
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Kullanıcı ID'si varsa, created_by'a ekle
            if (isset($_SESSION['user_id'])) {
                $data['created_by'] = $_SESSION['user_id'];
            }
            
            // Veritabanına ekle
            $taskId = $db->insert('tasks', $data);
            
            if ($taskId) {
                setFlashMessage('success', 'Görev başarıyla eklendi.');
                
                // Proje detay sayfasına veya görevler sayfasına yönlendir
                if ($projectId > 0) {
                    redirect(url('index.php?page=projects&action=view&id=' . $projectId));
                } else {
                    redirect(url('index.php?page=tasks'));
                }
            } else {
                $errors[] = 'Görev eklenirken bir hata oluştu.';
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
    
    // Eğer bir proje ID'si belirtilmişse, o projeyi kontrol et
    if ($projectId > 0) {
        $project = $db->getRow("SELECT id, project_name FROM projects WHERE id = ?", [$projectId]);
        if (!$project) {
            setFlashMessage('danger', 'Belirtilen proje bulunamadı.');
            redirect(url('index.php?page=tasks'));
        }
    }
    
    // Kullanıcılar listesini kontrol et
    $users = [];
    
    try {
        $users = $db->getAll("SELECT id, CONCAT(first_name, ' ', last_name) as full_name FROM users ORDER BY first_name");
    } catch (Exception $e) {
        // Tablo yok veya hata oluştu - kullanıcılar boş kalacak
    }
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Veritabanı hatası: ' . $e->getMessage());
    redirect(url('index.php?page=tasks'));
}

// Durum ve öncelik seçenekleri - veritabanıdaki değerlere göre ayarlanmış
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
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Yeni Görev</h4>
        <a href="<?php echo url('index.php?page=tasks'); ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Geri
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form action="<?php echo url('index.php?page=tasks&action=add'); ?>" method="post" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="project_id" class="form-label">Proje *</label>
                    <select name="project_id" id="project_id" class="form-select" required>
                        <option value="">Proje seçiniz</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" <?php echo $projectId == $project['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['project_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Görevin ait olduğu projeyi seçin.</div>
                </div>
                
                <div class="mb-3">
                    <label for="title" class="form-label">Görev Başlığı *</label>
                    <input type="text" class="form-control" id="title" name="title" required value="<?php echo htmlspecialchars($title); ?>">
                    <div class="form-text">Görev için kısa ve açıklayıcı bir başlık girin.</div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Açıklama</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                    <div class="form-text">Görevin detaylı açıklamasını girin.</div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Durum</label>
                        <select name="status" id="status" class="form-select">
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo $status == $value ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="priority" class="form-label">Öncelik</label>
                        <select name="priority" id="priority" class="form-select">
                            <?php foreach ($priorityOptions as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo $priority == $value ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="deadline" class="form-label">Son Tarih</label>
                        <input type="date" class="form-control" id="deadline" name="deadline" value="<?php echo htmlspecialchars($deadline); ?>">
                    </div>
                </div>
                
                <?php if (!empty($users)): ?>
                <div class="mb-3">
                    <label for="assigned_to" class="form-label">Atanan Kişi</label>
                    <select name="assigned_to" id="assigned_to" class="form-select">
                        <option value="">Atanan kişi seçiniz</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $assigned_to == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?php echo url('index.php?page=tasks'); ?>" class="btn btn-light me-md-2">İptal</a>
                    <button type="submit" class="btn btn-primary">Görevi Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>