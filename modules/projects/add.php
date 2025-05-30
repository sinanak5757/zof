<?php
// Yeni proje ekleme

// Müşteri listesini al
try {
    $clients = $db->getAll("SELECT id, company_name FROM clients WHERE status = 'active' ORDER BY company_name ASC");
    
    // Proje yöneticisi olabilecek kullanıcıları al
    $managers = $db->getAll("
        SELECT id, first_name, last_name 
        FROM users 
        WHERE role IN ('admin', 'manager') 
        ORDER BY first_name, last_name
    ");
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Veri yüklenirken bir hata oluştu: ' . $e->getMessage() . '</div>';
    $clients = [];
    $managers = [];
}

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form verilerini al
    $clientId = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
    $projectName = $_POST['project_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $startDate = $_POST['start_date'] ?? date('Y-m-d');
    $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $status = $_POST['status'] ?? 'planning';
    $projectManagerId = !empty($_POST['project_manager_id']) ? intval($_POST['project_manager_id']) : null;
    $budget = !empty($_POST['budget']) ? $_POST['budget'] : null;
    $notes = $_POST['notes'] ?? '';
    
    // Basit doğrulama
    $errors = [];
    
    if ($clientId <= 0) {
        $errors[] = 'Müşteri seçimi gereklidir.';
    }
    
    if (empty($projectName)) {
        $errors[] = 'Proje adı gereklidir.';
    }
    
    if (empty($startDate)) {
        $errors[] = 'Başlangıç tarihi gereklidir.';
    }
    
    // Hata yoksa, projeyi ekle
    if (empty($errors)) {
        $projectData = [
            'client_id' => $clientId,
            'project_name' => $projectName,
            'description' => $description,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
            'project_manager_id' => $projectManagerId,
            'budget' => $budget,
            'notes' => $notes
        ];
        
        try {
            $projectId = $db->insert('projects', $projectData);
            
            if ($projectId) {
                setFlashMessage('success', 'Proje başarıyla eklendi.');
                redirect(url('index.php?page=projects&action=view&id=' . $projectId));
            } else {
                $errors[] = 'Proje eklenirken bir hata oluştu.';
            }
        } catch (Exception $e) {
            $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }
}

// Varsayılan müşteri ID
$defaultClientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Yeni Proje Ekle</h4>
        <a href="<?php echo url('index.php?page=projects'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Projelere Dön
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="client_id" class="form-label">Müşteri *</label>
                    <select class="form-select" id="client_id" name="client_id" required>
                        <option value="">Müşteri Seçin</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo (isset($_POST['client_id']) ? $_POST['client_id'] : $defaultClientId) == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['company_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="project_name" class="form-label">Proje Adı *</label>
                    <input type="text" class="form-control" id="project_name" name="project_name" value="<?php echo isset($_POST['project_name']) ? htmlspecialchars($_POST['project_name']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Proje Açıklaması</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="start_date" class="form-label">Başlangıç Tarihi *</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : date('Y-m-d'); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="end_date" class="form-label">Bitiş Tarihi</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="status" class="form-label">Durum</label>
                    <select class="form-select" id="status" name="status">
                        <option value="planning" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'planning') ? 'selected' : ''; ?>>Planlama</option>
                        <option value="in_progress" <?php echo (isset($_POST['status']) && $_POST['status'] === 'in_progress') ? 'selected' : ''; ?>>Devam Ediyor</option>
                        <option value="on_hold" <?php echo (isset($_POST['status']) && $_POST['status'] === 'on_hold') ? 'selected' : ''; ?>>Beklemede</option>
                        <option value="completed" <?php echo (isset($_POST['status']) && $_POST['status'] === 'completed') ? 'selected' : ''; ?>>Tamamlandı</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="project_manager_id" class="form-label">Proje Yöneticisi</label>
                    <select class="form-select" id="project_manager_id" name="project_manager_id">
                        <option value="">Seçin</option>
                        <?php foreach ($managers as $manager): ?>
                            <option value="<?php echo $manager['id']; ?>" <?php echo (isset($_POST['project_manager_id']) && $_POST['project_manager_id'] == $manager['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="budget" class="form-label">Bütçe</label>
                <div class="input-group">
                    <span class="input-group-text">₺</span>
                    <input type="number" class="form-control" id="budget" name="budget" step="0.01" value="<?php echo isset($_POST['budget']) ? htmlspecialchars($_POST['budget']) : ''; ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Notlar</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
            </div>
        </div>
        
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Proje Ekle</button>
            <a href="<?php echo url('index.php?page=projects'); ?>" class="btn btn-outline-secondary ms-2">İptal</a>
        </div>
    </form>
</div>