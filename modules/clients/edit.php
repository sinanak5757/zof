<?php
// Müşteri düzenleme sayfası

// Müşteri ID'sini al
$clientId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($clientId <= 0) {
    setFlashMessage('danger', 'Geçersiz müşteri ID\'si.');
    redirect(url('index.php?page=clients'));
}

// Müşteri bilgilerini al
try {
    $client = $db->getRow("SELECT * FROM clients WHERE id = ?", [$clientId]);
    
    if (!$client) {
        setFlashMessage('danger', 'Müşteri bulunamadı.');
        redirect(url('index.php?page=clients'));
    }
} catch (Exception $e) {
    setFlashMessage('danger', 'Müşteri bilgileri alınırken bir hata oluştu: ' . $e->getMessage());
    redirect(url('index.php?page=clients'));
}

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
    
    // Hata yoksa, müşteriyi güncelle
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
            $updated = $db->update('clients', $clientData, 'id = ?', [$clientId]);
            
            if ($updated) {
                setFlashMessage('success', 'Müşteri başarıyla güncellendi.');
                redirect(url('index.php?page=clients&action=view&id=' . $clientId));
            } else {
                $errors[] = 'Müşteri güncellenirken bir hata oluştu.';
            }
        } catch (Exception $e) {
            $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Müşteri Düzenle</h4>
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
                    <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($client['company_name']); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="website" class="form-label">Web Sitesi *</label>
                    <input type="url" class="form-control" id="website" name="website" value="<?php echo htmlspecialchars($client['website']); ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="industry" class="form-label">Sektör</label>
                    <input type="text" class="form-control" id="industry" name="industry" value="<?php echo htmlspecialchars($client['industry']); ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="status" class="form-label">Durum</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo $client['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="inactive" <?php echo $client['status'] === 'inactive' ? 'selected' : ''; ?>>Pasif</option>
                        <option value="prospect" <?php echo $client['status'] === 'prospect' ? 'selected' : ''; ?>>Potansiyel</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="contact_person" class="form-label">İlgili Kişi</label>
                    <input type="text" class="form-control" id="contact_person" name="contact_person" value="<?php echo htmlspecialchars($client['contact_person']); ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="client_since" class="form-label">Müşteri Olma Tarihi</label>
                    <input type="date" class="form-control" id="client_since" name="client_since" value="<?php echo date('Y-m-d', strtotime($client['client_since'])); ?>">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="contact_email" class="form-label">E-posta</label>
                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($client['contact_email']); ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="contact_phone" class="form-label">Telefon</label>
                    <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($client['contact_phone']); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Notlar</label>
                <textarea class="form-control" id="notes" name="notes" rows="4"><?php echo htmlspecialchars($client['notes']); ?></textarea>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Müşteriyi Güncelle</button>
            <a href="<?php echo url('index.php?page=clients'); ?>" class="btn btn-outline-secondary ms-2">İptal</a>
        </div>
    </form>
</div>