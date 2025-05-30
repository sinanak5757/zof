<?php
// Genel ayarlar sayfası

// Ayarları kaydetme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $siteName = $_POST['site_name'] ?? '';
    $siteEmail = $_POST['site_email'] ?? '';
    $dateFormat = $_POST['date_format'] ?? 'd.m.Y';
    $timeFormat = $_POST['time_format'] ?? 'H:i';
    
    try {
        // Site adı güncelleme
        $db->update('settings', ['setting_value' => $siteName], 'setting_name = ?', ['site_title']);
        
        // Site e-posta güncelleme
        $db->update('settings', ['setting_value' => $siteEmail], 'setting_name = ?', ['site_email']);
        
        // Tarih formatı güncelleme
        $db->update('settings', ['setting_value' => $dateFormat], 'setting_name = ?', ['date_format']);
        
        // Zaman formatı güncelleme
        $db->update('settings', ['setting_value' => $timeFormat], 'setting_name = ?', ['time_format']);
        
        setFlashMessage('success', 'Ayarlar başarıyla güncellendi.');
        redirect(url('index.php?page=settings&action=general'));
    } catch (Exception $e) {
        $error = 'Ayarlar güncellenirken bir hata oluştu: ' . $e->getMessage();
    }
}

// Mevcut ayarları al
try {
    $settings = [];
    $settingRows = $db->getAll("SELECT setting_name, setting_value FROM settings");
    
    foreach ($settingRows as $row) {
        $settings[$row['setting_name']] = $row['setting_value'];
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Ayarlar yüklenirken bir hata oluştu: ' . $e->getMessage() . '</div>';
    $settings = [];
}
?>

<div class="card-header">
    <h5 class="mb-0">Genel Ayarlar</h5>
</div>
<div class="card-body">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="mb-3">
            <label for="site_name" class="form-label">Site Adı</label>
            <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_title'] ?? 'Seozof Agency'); ?>">
        </div>
        
        <div class="mb-3">
            <label for="site_email" class="form-label">Site E-posta Adresi</label>
            <input type="email" class="form-control" id="site_email" name="site_email" value="<?php echo htmlspecialchars($settings['site_email'] ?? 'info@seozof.com'); ?>">
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="date_format" class="form-label">Tarih Formatı</label>
                <select class="form-select" id="date_format" name="date_format">
                    <option value="d.m.Y" <?php echo ($settings['date_format'] ?? 'd.m.Y') === 'd.m.Y' ? 'selected' : ''; ?>>31.12.2023 (d.m.Y)</option>
                    <option value="Y-m-d" <?php echo ($settings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : ''; ?>>2023-12-31 (Y-m-d)</option>
                    <option value="m/d/Y" <?php echo ($settings['date_format'] ?? '') === 'm/d/Y' ? 'selected' : ''; ?>>12/31/2023 (m/d/Y)</option>
                    <option value="d F Y" <?php echo ($settings['date_format'] ?? '') === 'd F Y' ? 'selected' : ''; ?>>31 Aralık 2023 (d F Y)</option>
                </select>
            </div>
            
            <div class="col-md-6">
                <label for="time_format" class="form-label">Zaman Formatı</label>
                <select class="form-select" id="time_format" name="time_format">
                    <option value="H:i" <?php echo ($settings['time_format'] ?? 'H:i') === 'H:i' ? 'selected' : ''; ?>>14:30 (H:i)</option>
                    <option value="h:i A" <?php echo ($settings['time_format'] ?? '') === 'h:i A' ? 'selected' : ''; ?>>02:30 PM (h:i A)</option>
                </select>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Tema</label>
            <div class="d-flex gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="theme" id="theme_light" value="light" checked>
                    <label class="form-check-label" for="theme_light">Açık</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="theme" id="theme_dark" value="dark">
                    <label class="form-check-label" for="theme_dark">Koyu</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="theme" id="theme_auto" value="auto">
                    <label class="form-check-label" for="theme_auto">Otomatik (Sistem)</label>
                </div>
            </div>
        </div>
        
        <div>
            <button type="submit" name="save_settings" class="btn btn-primary">Ayarları Kaydet</button>
        </div>
    </form>
</div>