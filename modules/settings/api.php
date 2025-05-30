<?php
// API ayarları sayfası

// API ayarlarını kaydetme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_api_settings'])) {
    $groqApiKey = $_POST['groq_api_key'] ?? '';
    $groqApiModel = $_POST['groq_api_model'] ?? 'llama3-70b-8192';
    
    try {
        // API key güncelleme
        $db->update('settings', ['setting_value' => $groqApiKey], 'setting_name = ?', ['groq_api_key']);
        
        // API model güncelleme
        $db->update('settings', ['setting_value' => $groqApiModel], 'setting_name = ?', ['groq_api_model']);
        
        // Groq API entegrasyonu güncelleme/ekleme
        $existingIntegration = $db->getRow("SELECT id FROM api_integrations WHERE integration_name = ?", ['groq']);
        
        if ($existingIntegration) {
            $db->update('api_integrations', [
                'api_key' => $groqApiKey,
                'settings' => json_encode(['model' => $groqApiModel]),
                'is_active' => !empty($groqApiKey)
            ], 'id = ?', [$existingIntegration['id']]);
        } else {
            $db->insert('api_integrations', [
                'integration_name' => 'groq',
                'api_key' => $groqApiKey,
                'settings' => json_encode(['model' => $groqApiModel]),
                'is_active' => !empty($groqApiKey)
            ]);
        }
        
        setFlashMessage('success', 'API ayarları başarıyla güncellendi.');
        redirect(url('index.php?page=settings&action=api'));
    } catch (Exception $e) {
        $error = 'API ayarları güncellenirken bir hata oluştu: ' . $e->getMessage();
    }
}

// Mevcut API ayarlarını al
try {
    $settings = [];
    $settingRows = $db->getAll("SELECT setting_name, setting_value FROM settings WHERE setting_name LIKE 'groq_%'");
    
    foreach ($settingRows as $row) {
        $settings[$row['setting_name']] = $row['setting_value'];
    }
    
    // API entegrasyonlarını al
    $apiIntegrations = $db->getAll("SELECT * FROM api_integrations");
    $groqIntegration = null;
    
    foreach ($apiIntegrations as $integration) {
        if ($integration['integration_name'] === 'groq') {
            $groqIntegration = $integration;
            break;
        }
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">API ayarları yüklenirken bir hata oluştu: ' . $e->getMessage() . '</div>';
    $settings = [];
    $groqIntegration = null;
}

// API durumunu test et
$apiStatus = 'unknown';
$apiMessage = '';

if (!empty($settings['groq_api_key'] ?? '')) {
    try {
        $groqApi = new GroqApi();
        $testResponse = $groqApi->generateCompletion("Test mesajı", [
            'max_tokens' => 10
        ]);
        
        $apiStatus = isset($testResponse['choices']) && is_array($testResponse['choices']) ? 'connected' : 'error';
        $apiMessage = $apiStatus === 'connected' ? 'API bağlantısı başarılı.' : 'API bağlantısı kuruldu, ancak beklenmeyen yanıt alındı.';
    } catch (Exception $e) {
        $apiStatus = 'error';
        $apiMessage = 'API bağlantı hatası: ' . $e->getMessage();
    }
}
?>

<div class="card-header">
    <h5 class="mb-0">API Ayarları</h5>
</div>
<div class="card-body">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="mb-4">
            <h6 class="border-bottom pb-2">Groq API Ayarları</h6>
            
            <div class="mb-3">
                <label for="groq_api_key" class="form-label">API Anahtarı</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="groq_api_key" name="groq_api_key" value="<?php echo htmlspecialchars($settings['groq_api_key'] ?? ''); ?>">
                    <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="form-text">Groq API anahtarınızı buraya girin. <a href="https://console.groq.com/keys" target="_blank">Groq Console</a>'dan edinebilirsiniz.</div>
            </div>
            
            <div class="mb-3">
                <label for="groq_api_model" class="form-label">Model</label>
                <select class="form-select" id="groq_api_model" name="groq_api_model">
                    <option value="llama3-70b-8192" <?php echo ($settings['groq_api_model'] ?? '') === 'llama3-70b-8192' ? 'selected' : ''; ?>>LLaMA-3 70B (Önerilen)</option>
                    <option value="llama3-8b-8192" <?php echo ($settings['groq_api_model'] ?? '') === 'llama3-8b-8192' ? 'selected' : ''; ?>>LLaMA-3 8B</option>
                    <option value="mixtral-8x7b-32768" <?php echo ($settings['groq_api_model'] ?? '') === 'mixtral-8x7b-32768' ? 'selected' : ''; ?>>Mixtral 8x7B</option>
                    <option value="gemma-7b-it" <?php echo ($settings['groq_api_model'] ?? '') === 'gemma-7b-it' ? 'selected' : ''; ?>>Gemma 7B</option>
                </select>
                <div class="form-text">Kullanılacak yapay zeka modelini seçin.</div>
            </div>
            
            <?php if ($apiStatus !== 'unknown'): ?>
                <div class="alert alert-<?php echo $apiStatus === 'connected' ? 'success' : 'danger'; ?>">
                    <i class="bi bi-<?php echo $apiStatus === 'connected' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo $apiMessage; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div>
            <button type="submit" name="save_api_settings" class="btn btn-primary">API Ayarlarını Kaydet</button>
            <a href="<?php echo url('index.php?page=ai-assistant'); ?>" class="btn btn-outline-secondary ms-2">AI Asistana Git</a>
        </div>
    </form>
</div>

<script>
// API anahtarını göster/gizle
document.getElementById('toggleApiKey').addEventListener('click', function() {
    var apiKeyInput = document.getElementById('groq_api_key');
    var eyeIcon = this.querySelector('i');
    
    if (apiKeyInput.type === 'password') {
        apiKeyInput.type = 'text';
        eyeIcon.classList.remove('bi-eye');
        eyeIcon.classList.add('bi-eye-slash');
    } else {
        apiKeyInput.type = 'password';
        eyeIcon.classList.remove('bi-eye-slash');
        eyeIcon.classList.add('bi-eye');
    }
});
</script>