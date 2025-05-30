<?php
// Kurulum betiği
// NOT: Bu dosya sadece ilk kurulum sırasında çalıştırılmalı ve sonra kaldırılmalıdır!

// Gerekli dosyaları dahil et
require_once 'includes/config.php';
require_once 'includes/db.php';

// Kurulum yapıldı mı kontrolü
$installationLockFile = __DIR__ . '/install.lock';

if (file_exists($installationLockFile)) {
    die('Kurulum zaten yapılmış. Bu dosyayı tekrar çalıştırmak için önce install.lock dosyasını silmeniz gerekiyor.');
}

// Kurulum formunu göster veya kurulumu başlat
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        // Veritabanı tablolarını oluştur
        createTables();
        
        // Varsayılan yönetici kullanıcısını oluştur
        $adminEmail = $_POST['admin_email'] ?? 'admin@example.com';
        $adminPassword = $_POST['admin_password'] ?? 'password';
        $adminFirstName = $_POST['admin_first_name'] ?? 'Admin';
        $adminLastName = $_POST['admin_last_name'] ?? 'User';
        
        createAdminUser($adminEmail, $adminPassword, $adminFirstName, $adminLastName);
        
        // Kurulum tamamlandı
        $success = 'Kurulum başarıyla tamamlandı! Şimdi <a href="login.php">giriş yapabilirsiniz</a>.';
        
        // Kurulum kilidi oluştur
        file_put_contents($installationLockFile, date('Y-m-d H:i:s'));
    } catch (Exception $e) {
        $error = 'Kurulum sırasında bir hata oluştu: ' . $e->getMessage();
    }
}

// Veritabanı tablolarını oluştur
function createTables() {
    global $db;
    
    // Kullanıcılar tablosu
    $db->query("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        role ENUM('admin', 'manager', 'seo_specialist', 'content_writer', 'client') NOT NULL,
        avatar VARCHAR(255),
        reset_token VARCHAR(64),
        reset_token_expiry DATETIME,
        last_login DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Müşteriler tablosu
    $db->query("
    CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_name VARCHAR(100) NOT NULL,
        website VARCHAR(255) NOT NULL,
        industry VARCHAR(100),
        contact_person VARCHAR(100),
        contact_email VARCHAR(100),
        contact_phone VARCHAR(20),
        client_since DATE NOT NULL,
        status ENUM('active', 'inactive', 'prospect') NOT NULL DEFAULT 'active',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Projeler tablosu
    $db->query("
    CREATE TABLE IF NOT EXISTS projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        project_name VARCHAR(100) NOT NULL,
        description TEXT,
        start_date DATE NOT NULL,
        end_date DATE,
        status ENUM('planning', 'in_progress', 'on_hold', 'completed') NOT NULL DEFAULT 'planning',
        project_manager_id INT,
        budget DECIMAL(10, 2),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        FOREIGN KEY (project_manager_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    // Anahtar Kelimeler tablosu
    $db->query("
    CREATE TABLE IF NOT EXISTS keywords (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        keyword VARCHAR(255) NOT NULL,
        search_volume INT,
        difficulty DECIMAL(5,2),
        priority ENUM('high', 'medium', 'low') NOT NULL DEFAULT 'medium',
        initial_position INT,
        target_position INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    )");
    
    // Anahtar Kelime Sıralama Geçmişi tablosu
    $db->query("
    CREATE TABLE IF NOT EXISTS keyword_rankings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        keyword_id INT NOT NULL,
        position INT,
        previous_position INT,
        check_date DATE NOT NULL,
        search_engine ENUM('google', 'bing', 'yandex') NOT NULL DEFAULT 'google',
        search_locale VARCHAR(10) NOT NULL DEFAULT 'tr_TR',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE
    )");
    
// İçerik Planlaması tablosu
    $db->query("
    CREATE TABLE IF NOT EXISTS content_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content_type ENUM('blog_post', 'landing_page', 'product_description', 'category_page', 'other') NOT NULL,
        status ENUM('planned', 'in_progress', 'review', 'published') NOT NULL DEFAULT 'planned',
        target_keywords TEXT,
        word_count INT,
        assigned_to INT,
        due_date DATE,
        publish_date DATE,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    // İçerik Versiyonları tablosu
    $db->query("
    CREATE TABLE IF NOT EXISTS content_versions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content_plan_id INT NOT NULL,
        version INT NOT NULL DEFAULT 1,
        content_text LONGTEXT,
        meta_title VARCHAR(255),
        meta_description VARCHAR(255),
        ai_suggestions TEXT,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (content_plan_id) REFERENCES content_plans(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // SEO Görevleri tablosu
    $db->query("
    CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        task_name VARCHAR(255) NOT NULL,
        description TEXT,
        task_type ENUM('technical_seo', 'on_page_seo', 'content_creation', 'link_building', 'reporting', 'other') NOT NULL,
        priority ENUM('urgent', 'high', 'medium', 'low') NOT NULL DEFAULT 'medium',
        status ENUM('not_started', 'in_progress', 'review', 'completed') NOT NULL DEFAULT 'not_started',
        assigned_to INT,
        created_by INT NOT NULL,
        start_date DATE,
        due_date DATE,
        completion_date DATE,
        estimated_hours DECIMAL(5,2),
        actual_hours DECIMAL(5,2),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Backlink tablosu
    $db->query("
    CREATE TABLE IF NOT EXISTS backlinks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        source_url VARCHAR(255) NOT NULL,
        target_url VARCHAR(255) NOT NULL,
        anchor_text VARCHAR(255),
        link_type ENUM('dofollow', 'nofollow', 'ugc', 'sponsored') NOT NULL,
        domain_authority INT,
        page_authority INT,
        status ENUM('active', 'lost', 'target') NOT NULL,
        discovery_date DATE,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    )");
    
    // Teknik SEO Denetimi tablosu
    $db->query("
    CREATE TABLE IF NOT EXISTS technical_audits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        audit_date DATE NOT NULL,
        performed_by INT,
        total_pages INT,
        indexable_pages INT,
        pages_with_issues INT,
        critical_issues INT,
        warnings INT,
        notices INT,
        audit_summary TEXT,
        full_report_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    // AI Öneri Kaydı tablosu
    $db->query("
    CREATE TABLE IF NOT EXISTS ai_suggestions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        request_type ENUM('content_optimization', 'keyword_recommendation', 'technical_fix', 'strategy', 'other') NOT NULL,
        request_data TEXT NOT NULL,
        response_data LONGTEXT NOT NULL,
        created_by INT NOT NULL,
        applied BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Raporlar tablosu
    $db->query("
    CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        report_name VARCHAR(255) NOT NULL,
        report_type ENUM('monthly', 'quarterly', 'annual', 'custom') NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        created_by INT NOT NULL,
        report_data LONGTEXT,
        pdf_url VARCHAR(255),
        is_sent_to_client BOOLEAN DEFAULT FALSE,
        sent_date DATETIME,
        client_viewed BOOLEAN DEFAULT FALSE,
        client_view_date DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Sistem ayarları tablosu
    $db->query("
    CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_name VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // API Logları tablosu
    $db->query("
    CREATE TABLE IF NOT EXISTS api_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        api_type VARCHAR(50) NOT NULL,
        request_type VARCHAR(50) NOT NULL,
        request_data TEXT,
        response_data TEXT,
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    // Bildirimler tablosu
    $db->query("
    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        notification_type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        related_id INT,
        related_type VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
}

// Varsayılan yönetici kullanıcısını oluştur
function createAdminUser($email, $password, $firstName, $lastName) {
    global $db;
    
    // Kullanıcı adını e-postadan oluştur
    $username = strtolower(substr($firstName, 0, 1) . $lastName);
    
    // Şifreyi hashle
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Kullanıcıyı ekle
    $db->insert('users', [
        'username' => $username,
        'password' => $hashedPassword,
        'email' => $email,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'role' => 'admin'
    ]);
    
    // Temel ayarları ekle
    $db->insert('settings', [
        'setting_name' => 'site_title',
        'setting_value' => 'Seozof Agency'
    ]);
    
    $db->insert('settings', [
        'setting_name' => 'site_email',
        'setting_value' => $email
    ]);
    
    $db->insert('settings', [
        'setting_name' => 'groq_api_key',
        'setting_value' => GROQ_API_KEY
    ]);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurulum - <?php echo SITE_TITLE; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body {
            padding-top: 40px;
            padding-bottom: 40px;
            background-color: #f5f5f5;
        }
        .install-container {
            max-width: 600px;
            padding: 15px;
            margin: 0 auto;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo img {
            height: 60px;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="logo">
            <img src="assets/images/logo.png" alt="<?php echo SITE_TITLE; ?>">
        </div>
        
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="card-title text-center mb-4">SEO Ajans Yönetim Sistemi Kurulumu</h4>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php else: ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">Yönetici E-posta Adresi</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_password" class="form-label">Yönetici Şifresi</label>
                        <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="admin_first_name" class="form-label">Ad</label>
                            <input type="text" class="form-control" id="admin_first_name" name="admin_first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="admin_last_name" class="form-label">Soyad</label>
                            <input type="text" class="form-control" id="admin_last_name" name="admin_last_name" required>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Not:</strong> Kurulum tamamlandıktan sonra güvenlik nedeniyle bu dosyayı sunucunuzdan kaldırmanız önerilir.
                    </div>
                    
                    <div class="d-grid mt-4">
                        <button type="submit" name="install" class="btn btn-primary">Kurulumu Başlat</button>
                    </div>
                </form>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>