<?php
// İçerik optimizasyonu sayfası

// Projeler listesi
$projects = $db->getAll("
    SELECT p.id, p.project_name, c.company_name
    FROM projects p
    JOIN clients c ON p.client_id = c.id
    WHERE p.status IN ('planning', 'in_progress')
    ORDER BY p.project_name ASC
");

// İçerik türleri
$contentTypes = [
    'blog_post' => 'Blog Yazısı',
    'landing_page' => 'Landing Page',
    'product_description' => 'Ürün Açıklaması',
    'category_page' => 'Kategori Sayfası',
    'service_page' => 'Hizmet Sayfası',
    'other' => 'Diğer'
];

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    validateCsrfToken($_POST['csrf_token']);
    
    // Form verilerini al
    $projectId = $_POST['project_id'] ?? 0;
    $content = $_POST['content'] ?? '';
    $keywords = $_POST['keywords'] ?? '';
    $contentType = $_POST['content_type'] ?? 'blog_post';
    
    // Basit doğrulama
    $errors = [];
    
    if (empty($projectId) || $projectId == 0) {
        $errors[] = 'Proje seçimi gereklidir.';
    }
    
    if (empty($content)) {
        $errors[] = 'İçerik metni gereklidir.';
    }
    
    if (empty($keywords)) {
        $errors[] = 'Hedef anahtar kelimeler gereklidir.';
    }
    
    // Hata yoksa, içerik optimizasyonu yapılır
    if (empty($errors)) {
        try {
            // Groq API kullanarak içerik optimizasyonu al
            $optimization = $groqApi->getContentOptimization($content, $keywords, $contentType);
            
            // AI önerisini kaydet
            $suggestionId = $db->insert('ai_suggestions', [
                'project_id' => $projectId,
                'request_type' => 'content_optimization',
                'request_data' => json_encode([
                    'content' => $content,
                    'keywords' => $keywords,
                    'content_type' => $contentType
                ]),
                'response_data' => json_encode($optimization),
                'created_by' => $_SESSION['user_id']
            ]);
            
            if ($suggestionId) {
                setFlashMessage('success', 'İçerik optimizasyon önerileri başarıyla oluşturuldu.');
                redirect(url('index.php?page=ai-assistant&action=view&id=' . $suggestionId));
            } else {
                $errors[] = 'Öneri kaydedilirken bir hata oluştu.';
            }
        } catch (Exception $e) {
            $errors[] = 'İçerik optimizasyonu sırasında bir hata oluştu: ' . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-file-earmark-text me-2"></i> İçerik Optimizasyonu</h4>
    <a href="<?php echo url('index.php?page=ai-assistant'); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> AI Asistana Dön
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">İçerik Analizi ve Optimizasyon</h5>
    </div>
    <div class="card-body">
        <form method="post" action="" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="mb-3">
                <label for="project_id" class="form-label">Proje *</label>
                <select class="form-select" id="project_id" name="project_id" required>
                    <option value="">Proje Seçin</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>" <?php echo (isset($_POST['project_id']) && $_POST['project_id'] == $project['id']) ? 'selected' : ''; ?>>
                            <?php echo sanitize($project['project_name'] . ' (' . $project['company_name'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Lütfen bir proje seçin.</div>
            </div>
            
            <div class="mb-3">
                <label for="content_type" class="form-label">İçerik Türü</label>
                <select class="form-select" id="content_type" name="content_type">
                    <?php foreach ($contentTypes as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo (isset($_POST['content_type']) && $_POST['content_type'] === $value) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="keywords" class="form-label">Hedef Anahtar Kelimeler *</label>
                <input type="text" class="form-control" id="keywords" name="keywords" value="<?php echo isset($_POST['keywords']) ? sanitize($_POST['keywords']) : ''; ?>" placeholder="Anahtar kelimeleri virgülle ayırarak girin" required>
                <div class="invalid-feedback">Hedef anahtar kelimeler gereklidir.</div>
            </div>
            
            <div class="mb-3">
                <label for="content" class="form-label">İçerik Metni *</label>
                <textarea class="form-control" id="content" name="content" rows="10" required><?php echo isset($_POST['content']) ? sanitize($_POST['content']) : ''; ?></textarea>
                <div class="invalid-feedback">İçerik metni gereklidir.</div>
            </div>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i> İçerik metniniz, hedef anahtar kelimeleriniz için otomatik olarak analiz edilecek ve SEO optimizasyon önerileri sunulacaktır.
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-magic me-1"></i> İçerik Analizi Yap
                </button>
                <a href="<?php echo url('index.php?page=ai-assistant'); ?>" class="btn btn-outline-secondary ms-2">İptal</a>
            </div>
        </form>
    </div>
</div>