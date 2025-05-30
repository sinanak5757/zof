<?php
// Ayarlar ana sayfası

// Aktif sekme
$activeTab = isset($_GET['action']) ? $_GET['action'] : 'general';

// Sadece admin ve yöneticiler erişebilir
if (!in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    setFlashMessage('danger', 'Bu sayfayı görüntülemek için yetkiniz bulunmuyor.');
    redirect(url('index.php?page=dashboard'));
}
?>

<div class="container mt-4">
    <h4 class="mb-4">Sistem Ayarları</h4>
    
    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="list-group">
                <a href="<?php echo url('index.php?page=settings&action=general'); ?>" class="list-group-item list-group-item-action <?php echo $activeTab === 'general' ? 'active' : ''; ?>">
                    <i class="bi bi-gear me-2"></i> Genel Ayarlar
                </a>
                <a href="<?php echo url('index.php?page=settings&action=users'); ?>" class="list-group-item list-group-item-action <?php echo $activeTab === 'users' ? 'active' : ''; ?>">
                    <i class="bi bi-people me-2"></i> Kullanıcı Yönetimi
                </a>
                <a href="<?php echo url('index.php?page=settings&action=api'); ?>" class="list-group-item list-group-item-action <?php echo $activeTab === 'api' ? 'active' : ''; ?>">
                    <i class="bi bi-braces me-2"></i> API Ayarları
                </a>
                <a href="<?php echo url('index.php?page=settings&action=backup'); ?>" class="list-group-item list-group-item-action <?php echo $activeTab === 'backup' ? 'active' : ''; ?>">
                    <i class="bi bi-cloud-download me-2"></i> Yedekleme
                </a>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <?php
                // İlgili ayar sayfasını yükle
                $settingsPath = "modules/settings/$activeTab.php";
                
                if (file_exists($settingsPath)) {
                    include $settingsPath;
                } else {
                    // Genel ayarlar sayfasını yükle
                    include "modules/settings/general.php";
                }
                ?>
            </div>
        </div>
    </div>
</div>