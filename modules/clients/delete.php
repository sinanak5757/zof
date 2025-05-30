<?php
// Müşteri silme işlemi

// Müşteri ID ve CSRF tokeni kontrol et
$clientId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$csrf = isset($_GET['csrf']) ? $_GET['csrf'] : '';

// ID kontrolü
if ($clientId <= 0) {
    setFlashMessage('danger', 'Geçersiz müşteri ID\'si.');
    redirect(url('index.php?page=clients'));
}

// CSRF kontrolü (güvenlik için)
if (empty($csrf) || !validateCsrfToken($csrf)) {
    setFlashMessage('danger', 'Güvenlik tokeni geçersiz. Lütfen tekrar deneyin.');
    redirect(url('index.php?page=clients'));
}

// İlgili müşteriyi al
try {
    $client = $db->getRow("SELECT * FROM clients WHERE id = ?", [$clientId]);
    
    if (!$client) {
        setFlashMessage('danger', 'Müşteri bulunamadı.');
        redirect(url('index.php?page=clients'));
    }
    
    // Eğer onay istenemediyse, onay için yönlendir
    if (!isset($_GET['confirm']) || $_GET['confirm'] != 1) {
        // Onay sayfasını göster
?>
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Müşteri Silme Onayı</h5>
        </div>
        <div class="card-body">
            <p class="mb-0">
                <strong><?php echo htmlspecialchars($client['company_name']); ?></strong> müşterisini silmek istediğinizden emin misiniz?
            </p>
            <p class="text-danger mt-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Bu işlem geri alınamaz ve müşteriyle ilişkili tüm veriler (projeler, görevler, anahtar kelimeler vb.) silinecektir.
            </p>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <a href="<?php echo url('index.php?page=clients'); ?>" class="btn btn-secondary me-2">İptal</a>
            <a href="<?php echo url('index.php?page=clients&action=delete&id=' . $clientId . '&csrf=' . $csrf . '&confirm=1'); ?>" class="btn btn-danger">Evet, Sil</a>
        </div>
    </div>
</div>
<?php
        // Onay sayfasını gösterdikten sonra çık
        exit;
    }
    
    // Onay alındıysa, müşteriyi sil
    // Önce bağlı verileri kontrol et ve varsa sil
    $hasRelatedData = false;
    
    // Projeleri kontrol et
    $projects = $db->getAll("SELECT id FROM projects WHERE client_id = ?", [$clientId]);
    if (count($projects) > 0) {
        $hasRelatedData = true;
        
        // Her bir proje için bağlı verileri sil
        foreach ($projects as $project) {
            $projectId = $project['id'];
            
            // Görevleri sil
            $db->delete('tasks', 'project_id = ?', [$projectId]);
            
            // Anahtar kelimeleri sil
            $keywords = $db->getAll("SELECT id FROM keywords WHERE project_id = ?", [$projectId]);
            foreach ($keywords as $keyword) {
                // Anahtar kelime sıralamalarını sil
                $db->delete('keyword_rankings', 'keyword_id = ?', [$keyword['id']]);
            }
            $db->delete('keywords', 'project_id = ?', [$projectId]);
            
            // İçerik planlarını sil
            $contentPlans = $db->getAll("SELECT id FROM content_plans WHERE project_id = ?", [$projectId]);
            foreach ($contentPlans as $contentPlan) {
                // İçerik versiyonlarını sil
                $db->delete('content_versions', 'content_plan_id = ?', [$contentPlan['id']]);
            }
            $db->delete('content_plans', 'project_id = ?', [$projectId]);
            
            // Backlink'leri sil
            $db->delete('backlinks', 'project_id = ?', [$projectId]);
            
            // Raporları sil
            $db->delete('reports', 'project_id = ?', [$projectId]);
            
            // AI önerilerini sil
            $db->delete('ai_suggestions', 'project_id = ?', [$projectId]);
        }
        
        // Projeleri sil
        $db->delete('projects', 'client_id = ?', [$clientId]);
    }
    
    // Müşteriyi sil
    $deleted = $db->delete('clients', 'id = ?', [$clientId]);
    
    if ($deleted) {
        if ($hasRelatedData) {
            setFlashMessage('success', 'Müşteri ve ilişkili tüm veriler başarıyla silindi.');
        } else {
            setFlashMessage('success', 'Müşteri başarıyla silindi.');
        }
    } else {
        setFlashMessage('danger', 'Müşteri silinirken bir hata oluştu.');
    }
    
    // Müşteri listesine yönlendir
    redirect(url('index.php?page=clients'));
} catch (Exception $e) {
    setFlashMessage('danger', 'Veritabanı hatası: ' . $e->getMessage());
    redirect(url('index.php?page=clients'));
}
?>