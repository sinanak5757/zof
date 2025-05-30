<?php
// Yeni müşteri ekleme

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form verilerini al
    $companyName = $_POST['company_name'] ?? '';
    $website = $_POST['website'] ?? '';
    $industry = $_POST['industry'] ?? '';
    $contactPerson = $_POST['contact_person'] ?? '';
    $contactEmail = $_POST['contact_email'] ?? '';
    $contactPhone = $_POST['contact_phone'] ?? '';
    $clientSince = $_POST['client_since'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'active';
    $notes = $_POST['notes'] ?? '';
    
    // Basit doğrulama
    $errors = [];
    
    if (empty($companyName)) {
        $errors[] = 'Şirket adı gereklidir.';
    }
    
    if (empty($website)) {
        $errors[] = 'Web sitesi gereklidir.';
    }
    
    // Hata yoksa, müşteriyi ekle
    if (empty($errors)) {
        $clientData = [
            'company_name' => $companyName,
            'website' => $website,
            'industry' => $industry,
            'contact_person' => $contactPerson,
            'contact_email' => $contactEmail,
            'contact_phone' => $contactPhone,
            'client_since' => $clientSince,
            'status' => $status,
            'notes' => $notes
        ];
        
        try {
            $clientId = $db->insert('clients', $clientData);
            
            if ($clientId) {
                setFlashMessage('success', 'Müşteri başarıyla eklendi.');
                redirect(url('index.php?page=clients'));
            } else {
                $errors[] = 'Müşteri eklenirken bir hata oluştu.';
            }
        } catch (Exception $e) {
            $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Yeni Müşteri Ekle</h4>
        <a href="<?php echo url('index.php?page=clients'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Müşterilere Dön
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
                    <label for="company_name" class="form-label">Şirket Adı *</label>
                    <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="website" class="form-label">Web Sitesi *</label>
                    <input type="url" class="form-control" id="website" name="website" placeholder="https://" value="<?php echo isset($_POST['website']) ? htmlspecialchars($_POST['website']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="industry" class="form-label">Sektör</label>
                    <input type="text" class="form-control" id="industry" name="industry" value="<?php echo isset($_POST['industry']) ? htmlspecialchars($_POST['industry']) : ''; ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="status" class="form-label">Durum</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'active') ? 'selected' : ''; ?>>Aktif</option>
                        <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Pasif</option>
                        <option value="prospect" <?php echo (isset($_POST['status']) && $_POST['status'] === 'prospect') ? 'selected' : ''; ?>>Potansiyel</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="contact_person" class="form-label">İlgili Kişi</label>
                    <input type="text" class="form-control" id="contact_person" name="contact_person" value="<?php echo isset($_POST['contact_person']) ? htmlspecialchars($_POST['contact_person']) : ''; ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="client_since" class="form-label">Müşteri Olma Tarihi</label>
                    <input type="date" class="form-control" id="client_since" name="client_since" value="<?php echo isset($_POST['client_since']) ? htmlspecialchars($_POST['client_since']) : date('Y-m-d'); ?>">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="contact_email" class="form-label">E-posta</label>
                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo isset($_POST['contact_email']) ? htmlspecialchars($_POST['contact_email']) : ''; ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="contact_phone" class="form-label">Telefon</label>
                    <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo isset($_POST['contact_phone']) ? htmlspecialchars($_POST['contact_phone']) : ''; ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Notlar</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
            </div>
        </div>
        
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Müşteri Ekle</button>
            <a href="<?php echo url('index.php?page=clients'); ?>" class="btn btn-outline-secondary ms-2">İptal</a>
        </div>
    </form>
</div>