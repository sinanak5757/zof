<?php
// keywords/add.php - Anahtar Kelime Ekleme Sayfası

// POST verilerini al
$keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : (isset($_GET['project_id']) ? intval($_GET['project_id']) : 0);
$target_position = isset($_POST['target_position']) ? intval($_POST['target_position']) : 1;
$current_position = isset($_POST['current_position']) ? intval($_POST['current_position']) : null;
$search_volume = isset($_POST['search_volume']) ? intval($_POST['search_volume']) : null;
$competition = isset($_POST['competition']) ? $_POST['competition'] : 'medium';
$search_engine = isset($_POST['search_engine']) ? $_POST['search_engine'] : 'google';
$location = isset($_POST['location']) ? trim($_POST['location']) : 'Turkey';
$device_type = isset($_POST['device_type']) ? $_POST['device_type'] : 'desktop';
$url = isset($_POST['url']) ? trim($_POST['url']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Anahtar kelime doğrulama
    if (empty($keyword)) {
        $errors[] = 'Anahtar kelime boş olamaz.';
    }
    
    // Proje seçimi doğrulama
    if ($project_id <= 0) {
        $errors[] = 'Proje seçimi gereklidir.';
    }
    
    // Hedef pozisyon doğrulama
    if ($target_position < 1 || $target_position > 100) {
        $errors[] = 'Hedef pozisyon 1-100 arasında olmalıdır.';
    }
    
    // Aynı proje içinde anahtar kelime tekrarı kontrolü
    try {
        $existingKeyword = $db->getRow("
            SELECT id FROM keywords 
            WHERE keyword = ? AND project_id = ? AND search_engine = ? AND location = ?", 
            [$keyword, $project_id, $search_engine, $location]);
        if ($existingKeyword) {
            $errors[] = 'Bu anahtar kelime aynı proje, arama motoru ve lokasyon için zaten eklenmiş.';
        }
    } catch (Exception $e) {
        $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
    }
    
    // Hata yoksa veritabanına ekle
    if (empty($errors)) {
        try {
            $data = [
                'keyword' => $keyword,
                'project_id' => $project_id,
                'target_position' => $target_position,
                'current_position' => $current_position,
                'search_volume' => $search_volume,
                'competition' => $competition,
                'search_engine' => $search_engine,
                'location' => $location,
                'device_type' => $device_type,
                'url' => $url,
                'notes' => $notes,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'last_check_date' => date('Y-m-d')
            ];
            
            // Oluşturan kullanıcı bilgisi varsa ekle
            if (isset($_SESSION['user_id'])) {
                $data['created_by'] = $_SESSION['user_id'];
            }
            
            $keywordId = $db->insert('keywords', $data);
            
            if ($keywordId) {
                // İlk pozisyon geçmişi kaydı oluştur
                if ($current_position) {
                    $historyData = [
                        'keyword_id' => $keywordId,
                        'position' => $current_position,
                        'check_date' => date('Y-m-d'),
                        'search_engine' => $search_engine,
                        'location' => $location,
                        'device_type' => $device_type
                    ];
                    $db->insert('keyword_position_history', $historyData);
                }
                
                setFlashMessage('success', 'Anahtar kelime başarıyla eklendi.');
                redirect(url('index.php?page=keywords&action=view&id=' . $keywordId));
            } else {
                $errors[] = 'Anahtar kelime eklenirken bir hata oluştu.';
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

// Projeleri al
try {
    $projects = $db->getAll("SELECT id, project_name FROM projects ORDER BY project_name ASC");
} catch (Exception $e) {
    $projects = [];
    setFlashMessage('warning', 'Projeler yüklenemedi: ' . $e->getMessage());
}

// Sayfa başlığı
$pageTitle = 'Yeni Anahtar Kelime Ekle';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Yeni Anahtar Kelime Ekle</h4>
        <a href="<?= url('index.php?page=keywords') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Anahtar Kelime Listesine Dön
        </a>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form action="<?= url('index.php?page=keywords&action=add') ?>" method="post" id="keywordForm" class="needs-validation" novalidate>
                        
                        <!-- Temel Bilgiler -->
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <label for="keyword" class="form-label">Anahtar Kelime *</label>
                                <input type="text" class="form-control" id="keyword" name="keyword" required value="<?= htmlspecialchars($keyword) ?>">
                                <div class="form-text">Takip edilecek anahtar kelimeyi girin.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="project_id" class="form-label">Proje *</label>
                                <select class="form-select" id="project_id" name="project_id" required>
                                    <option value="">Proje Seçin</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>" <?= $project_id == $project['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($project['project_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Pozisyon Bilgileri -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="current_position" class="form-label">Mevcut Pozisyon</label>
                                <input type="number" class="form-control" id="current_position" name="current_position" min="1" max="200" value="<?= $current_position ?>">
                                <div class="form-text">Şu anki sıralama (opsiyonel)</div>
                            </div>
                            <div class="col-md-4">
                                <label for="target_position" class="form-label">Hedef Pozisyon *</label>
                                <input type="number" class="form-control" id="target_position" name="target_position" min="1" max="100" required value="<?= $target_position ?>">
                                <div class="form-text">Ulaşılmak istenen sıralama</div>
                            </div>
                            <div class="col-md-4">
                                <label for="search_volume" class="form-label">Aylık Arama Hacmi</label>
                                <input type="number" class="form-control" id="search_volume" name="search_volume" min="0" value="<?= $search_volume ?>">
                                <div class="form-text">Aylık arama sayısı</div>
                            </div>
                        </div>
                        
                        <!-- Arama Parametreleri -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label for="search_engine" class="form-label">Arama Motoru</label>
                                <select class="form-select" id="search_engine" name="search_engine">
                                    <option value="google" <?= $search_engine == 'google' ? 'selected' : '' ?>>Google</option>
                                    <option value="bing" <?= $search_engine == 'bing' ? 'selected' : '' ?>>Bing</option>
                                    <option value="yandex" <?= $search_engine == 'yandex' ? 'selected' : '' ?>>Yandex</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="location" class="form-label">Lokasyon</label>
                                <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($location) ?>">
                                <div class="form-text">Örn: Turkey, Istanbul</div>
                            </div>
                            <div class="col-md-3">
                                <label for="device_type" class="form-label">Cihaz Tipi</label>
                                <select class="form-select" id="device_type" name="device_type">
                                    <option value="desktop" <?= $device_type == 'desktop' ? 'selected' : '' ?>>Masaüstü</option>
                                    <option value="mobile" <?= $device_type == 'mobile' ? 'selected' : '' ?>>Mobil</option>
                                    <option value="tablet" <?= $device_type == 'tablet' ? 'selected' : '' ?>>Tablet</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="competition" class="form-label">Rekabet Seviyesi</label>
                                <select class="form-select" id="competition" name="competition">
                                    <option value="low" <?= $competition == 'low' ? 'selected' : '' ?>>Düşük</option>
                                    <option value="medium" <?= $competition == 'medium' ? 'selected' : '' ?>>Orta</option>
                                    <option value="high" <?= $competition == 'high' ? 'selected' : '' ?>>Yüksek</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- URL ve Notlar -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label for="url" class="form-label">Hedef URL</label>
                                <input type="url" class="form-control" id="url" name="url" value="<?= htmlspecialchars($url) ?>">
                                <div class="form-text">Bu anahtar kelime için optimize edilecek sayfa URL'si</div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="notes" class="form-label">Notlar</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($notes) ?></textarea>
                            <div class="form-text">Anahtar kelime hakkında ek bilgiler</div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Anahtar Kelimeyi Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Yardım Kartı -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Kullanım Kılavuzu</h6>
                </div>
                <div class="card-body">
                    <h6><i class="fas fa-search text-primary me-2"></i>Anahtar Kelime</h6>
                    <p class="small mb-3">Takip etmek istediğiniz anahtar kelimeyi tam olarak yazın. Büyük/küçük harf duyarlı değildir.</p>
                    
                    <h6><i class="fas fa-target text-success me-2"></i>Pozisyon Takibi</h6>
                    <p class="small mb-3">Mevcut pozisyonu girerek başlangıç noktanızı belirleyin. Hedef pozisyon genellikle 1-10 arası olmalıdır.</p>
                    
                    <h6><i class="fas fa-chart-line text-info me-2"></i>Arama Hacmi</h6>
                    <p class="small mb-3">Google Keyword Planner veya benzeri araçlardan elde ettiğiniz aylık arama hacmini girin.</p>
                    
                    <h6><i class="fas fa-globe text-warning me-2"></i>Lokasyon</h6>
                    <p class="small mb-0">Hedef kitlenizin bulunduğu coğrafi konumu belirtin. Bu, lokal SEO için kritiktir.</p>
                </div>
            </div>
            
            <!-- İstatistik Kartı -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Pozisyon Referansı</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <div class="text-success font-weight-bold h4">1-3</div>
                                <div class="small">En İyi</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-primary font-weight-bold h4">4-10</div>
                            <div class="small">İyi</div>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <div class="text-warning font-weight-bold h4">11-20</div>
                                <div class="small">Orta</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-danger font-weight-bold h4">21+</div>
                            <div class="small">Zayıf</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validasyonu
    const form = document.getElementById('keywordForm');
    const keywordInput = document.getElementById('keyword');
    const projectSelect = document.getElementById('project_id');
    
    // Anahtar kelime otomatik düzenleme
    keywordInput.addEventListener('blur', function() {
        // Baştaki ve sondaki boşlukları temizle
        this.value = this.value.trim();
        // Çoklu boşlukları tek boşluğa çevir
        this.value = this.value.replace(/\s+/g, ' ');
        // Küçük harfe çevir
        this.value = this.value.toLowerCase();
    });
    
    // Proje seçilince URL alanını otomatik doldur
    projectSelect.addEventListener('change', function() {
        if (this.value) {
            // AJAX ile proje bilgilerini al ve URL'yi otomatik doldur
            // Bu kısım isteğe bağlı, projenin ana URL'sini otomatik doldurabilir
        }
    });
    
    // Bootstrap form validasyonu
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>