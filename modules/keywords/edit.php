<?php
// keywords/edit.php - Anahtar Kelime Düzenleme Sayfası

// Anahtar kelime ID'sini kontrol et
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('warning', 'Düzenlenecek anahtar kelime ID belirtilmedi');
    redirect(url('index.php?page=keywords'));
}

$keywordId = intval($_GET['id']);

// Mevcut anahtar kelimeyi veritabanından al
try {
    $keyword_data = $db->getRow("
        SELECT k.*, p.project_name 
        FROM keywords k
        LEFT JOIN projects p ON k.project_id = p.id
        WHERE k.id = ?", [$keywordId]);
    
    if (!$keyword_data) {
        setFlashMessage('danger', 'Düzenlenecek anahtar kelime bulunamadı');
        redirect(url('index.php?page=keywords'));
    }
} catch (Exception $e) {
    setFlashMessage('danger', 'Veritabanı hatası: ' . $e->getMessage());
    redirect(url('index.php?page=keywords'));
}

// Form verilerini al (POST varsa POST'tan, yoksa veritabanından) - null kontrolü ile
$keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : ($keyword_data['keyword'] ?? '');
$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : ($keyword_data['project_id'] ?? 0);
$target_position = isset($_POST['target_position']) ? intval($_POST['target_position']) : ($keyword_data['target_position'] ?? 1);
$current_position = isset($_POST['current_position']) ? ($_POST['current_position'] !== '' ? intval($_POST['current_position']) : null) : ($keyword_data['current_position'] ?? null);
$search_volume = isset($_POST['search_volume']) ? ($_POST['search_volume'] !== '' ? intval($_POST['search_volume']) : null) : ($keyword_data['search_volume'] ?? null);
$competition = isset($_POST['competition']) ? $_POST['competition'] : ($keyword_data['competition'] ?? 'medium');
$search_engine = isset($_POST['search_engine']) ? $_POST['search_engine'] : ($keyword_data['search_engine'] ?? 'google');
$location = isset($_POST['location']) ? trim($_POST['location']) : ($keyword_data['location'] ?? 'Turkey');
$device_type = isset($_POST['device_type']) ? $_POST['device_type'] : ($keyword_data['device_type'] ?? 'desktop');
$url = isset($_POST['url']) ? trim($_POST['url']) : ($keyword_data['url'] ?? '');
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : ($keyword_data['notes'] ?? '');

// Hızlı pozisyon güncelleme
if (isset($_POST['quick_position'])) {
    $current_position = intval($_POST['quick_position']);
}

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
    
    // Hata yoksa veritabanını güncelle
    if (empty($errors)) {
        try {
            // Güncellenecek veriyi hazırla
            $data = [
                'keyword' => $keyword,
                'project_id' => $project_id,
                'target_position' => $target_position,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Tablodaki mevcut sütunları kontrol et
            try {
                $tableColumns = $db->getAll("SHOW COLUMNS FROM keywords");
                $existingColumns = array_column($tableColumns, 'Field');
            } catch (Exception $e) {
                $existingColumns = ['keyword', 'project_id', 'target_position']; // Minimum sütunlar
            }
            
            // İsteğe bağlı alanları kontrol ederek ekle
            if ($current_position !== null && in_array('current_position', $existingColumns)) {
                $data['current_position'] = $current_position;
                
                // Pozisyon değişti mi kontrol et
                if ($current_position != ($keyword_data['current_position'] ?? null)) {
                    if (in_array('last_check_date', $existingColumns)) {
                        $data['last_check_date'] = date('Y-m-d');
                    }
                    
                    // Pozisyon geçmişine kaydet (tablo varsa)
                    try {
                        $historyData = [
                            'keyword_id' => $keywordId,
                            'position' => $current_position,
                            'check_date' => date('Y-m-d'),
                            'search_engine' => $search_engine,
                            'location' => $location,
                            'device_type' => $device_type
                        ];
                        $db->insert('keyword_position_history', $historyData);
                    } catch (Exception $e) {
                        // Pozisyon geçmişi tablosu yoksa hata verme
                    }
                }
            }
            
            if ($search_volume !== null && in_array('search_volume', $existingColumns)) {
                $data['search_volume'] = $search_volume;
            }
            
            if (in_array('competition', $existingColumns)) {
                $data['competition'] = $competition;
            }
            
            if (in_array('search_engine', $existingColumns)) {
                $data['search_engine'] = $search_engine;
            }
            
            if (in_array('location', $existingColumns)) {
                $data['location'] = $location;
            }
            
            if (in_array('device_type', $existingColumns)) {
                $data['device_type'] = $device_type;
            }
            
            if (in_array('url', $existingColumns) && !empty($url)) {
                $data['url'] = $url;
            }
            
            if (in_array('notes', $existingColumns) && !empty($notes)) {
                $data['notes'] = $notes;
            }
            
            $updated = $db->update('keywords', $data, 'id = ?', [$keywordId]);
            
            if ($updated !== false) {
                setFlashMessage('success', 'Anahtar kelime başarıyla güncellendi.');
                redirect(url('index.php?page=keywords&action=view&id=' . $keywordId));
            } else {
                setFlashMessage('info', 'Herhangi bir değişiklik yapılmadı.');
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

// Tablodaki mevcut sütunları kontrol et
try {
    $tableColumns = $db->getAll("SHOW COLUMNS FROM keywords");
    $existingColumns = array_column($tableColumns, 'Field');
} catch (Exception $e) {
    $existingColumns = ['keyword', 'project_id', 'target_position']; // Minimum sütunlar
}

// Sayfa başlığı
$pageTitle = 'Anahtar Kelime Düzenle';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Anahtar Kelime Düzenle</h4>
        <div>
            <a href="<?= url('index.php?page=keywords&action=view&id=' . $keywordId) ?>" class="btn btn-outline-primary me-2">
                Görüntüle
            </a>
            <a href="<?= url('index.php?page=keywords') ?>" class="btn btn-secondary">
                Anahtar Kelime Listesine Dön
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-9">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form action="<?= url('index.php?page=keywords&action=edit&id=' . $keywordId) ?>" method="post" id="keywordForm">
                        
                        <!-- Temel Bilgiler -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="keyword" class="form-label">Anahtar Kelime *</label>
                                <input type="text" class="form-control" id="keyword" name="keyword" required value="<?= htmlspecialchars($keyword) ?>">
                                <div class="form-text">Takip edilecek anahtar kelimeyi girin.</div>
                            </div>
                            <div class="col-md-6">
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
                            <?php if (in_array('current_position', $existingColumns)): ?>
                            <div class="col-md-4">
                                <label for="current_position" class="form-label">Mevcut Pozisyon</label>
                                <input type="number" class="form-control" id="current_position" name="current_position" min="1" max="200" value="<?= $current_position ?>">
                                <div class="form-text">Şu anki sıralama</div>
                                <?php if (!empty($keyword_data['last_check_date'])): ?>
                                    <small class="text-muted">Son kontrol: <?= date('d.m.Y', strtotime($keyword_data['last_check_date'])) ?></small>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="col-md-4">
                                <label for="target_position" class="form-label">Hedef Pozisyon *</label>
                                <input type="number" class="form-control" id="target_position" name="target_position" min="1" max="100" required value="<?= $target_position ?>">
                                <div class="form-text">Ulaşılmak istenen sıralama</div>
                            </div>
                            
                            <?php if (in_array('search_volume', $existingColumns)): ?>
                            <div class="col-md-4">
                                <label for="search_volume" class="form-label">Aylık Arama Hacmi</label>
                                <input type="number" class="form-control" id="search_volume" name="search_volume" min="0" value="<?= $search_volume ?>">
                                <div class="form-text">Aylık arama sayısı</div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Gelişmiş Özellikler -->
                        <?php if (in_array('search_engine', $existingColumns) || in_array('location', $existingColumns) || in_array('device_type', $existingColumns) || in_array('competition', $existingColumns)): ?>
                        <div class="row mb-4">
                            <?php if (in_array('search_engine', $existingColumns)): ?>
                            <div class="col-md-3">
                                <label for="search_engine" class="form-label">Arama Motoru</label>
                                <select class="form-select" id="search_engine" name="search_engine">
                                    <option value="google" <?= $search_engine == 'google' ? 'selected' : '' ?>>Google</option>
                                    <option value="bing" <?= $search_engine == 'bing' ? 'selected' : '' ?>>Bing</option>
                                    <option value="yandex" <?= $search_engine == 'yandex' ? 'selected' : '' ?>>Yandex</option>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (in_array('location', $existingColumns)): ?>
                            <div class="col-md-3">
                                <label for="location" class="form-label">Lokasyon</label>
                                <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($location) ?>">
                                <div class="form-text">Örn: Turkey, Istanbul</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (in_array('device_type', $existingColumns)): ?>
                            <div class="col-md-3">
                                <label for="device_type" class="form-label">Cihaz Tipi</label>
                                <select class="form-select" id="device_type" name="device_type">
                                    <option value="desktop" <?= $device_type == 'desktop' ? 'selected' : '' ?>>Masaüstü</option>
                                    <option value="mobile" <?= $device_type == 'mobile' ? 'selected' : '' ?>>Mobil</option>
                                    <option value="tablet" <?= $device_type == 'tablet' ? 'selected' : '' ?>>Tablet</option>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (in_array('competition', $existingColumns)): ?>
                            <div class="col-md-3">
                                <label for="competition" class="form-label">Rekabet Seviyesi</label>
                                <select class="form-select" id="competition" name="competition">
                                    <option value="low" <?= $competition == 'low' ? 'selected' : '' ?>>Düşük</option>
                                    <option value="medium" <?= $competition == 'medium' ? 'selected' : '' ?>>Orta</option>
                                    <option value="high" <?= $competition == 'high' ? 'selected' : '' ?>>Yüksek</option>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- URL -->
                        <?php if (in_array('url', $existingColumns)): ?>
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label for="url" class="form-label">Hedef URL</label>
                                <input type="url" class="form-control" id="url" name="url" value="<?= htmlspecialchars($url) ?>">
                                <div class="form-text">Bu anahtar kelime için optimize edilecek sayfa URL'si</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Notlar -->
                        <?php if (in_array('notes', $existingColumns)): ?>
                        <div class="mb-4">
                            <label for="notes" class="form-label">Notlar</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($notes) ?></textarea>
                            <div class="form-text">Anahtar kelime hakkında ek bilgiler</div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Anahtar Kelimeyi Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3">
            <!-- Mevcut Bilgiler -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Mevcut Bilgiler</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="text-primary font-weight-bold h2"><?= $current_position ?: '-' ?></div>
                            <div class="small">Mevcut Pozisyon</div>
                        </div>
                        <div class="col-6">
                            <div class="text-success font-weight-bold h2"><?= $target_position ?></div>
                            <div class="small">Hedef Pozisyon</div>
                        </div>
                    </div>
                    <hr>
                    <div class="small text-muted">
                        <div><i class="fas fa-calendar me-1"></i> Oluşturulma: <?= date('d.m.Y', strtotime($keyword_data['created_at'])) ?></div>
                        <?php if (isset($keyword_data['updated_at'])): ?>
                        <div><i class="fas fa-clock me-1"></i> Son Güncelleme: <?= date('d.m.Y H:i', strtotime($keyword_data['updated_at'])) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Hızlı Pozisyon Güncelleme -->
            <?php if (in_array('current_position', $existingColumns)): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Hızlı Pozisyon Güncelleme</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2 mb-3">
                        <button type="button" class="btn btn-outline-success" onclick="setQuickPosition(1)">1. Sırada</button>
                        <button type="button" class="btn btn-outline-primary" onclick="setQuickPosition(3)">İlk 3'te</button>
                        <button type="button" class="btn btn-outline-warning" onclick="setQuickPosition(10)">İlk 10'da</button>
                        <button type="button" class="btn btn-outline-danger" onclick="setQuickPosition(50)">50+ Sırada</button>
                    </div>
                    
                    <div class="input-group">
                        <input type="number" class="form-control" id="quickPositionInput" placeholder="Pozisyon" min="1" max="200">
                        <button class="btn btn-primary" type="button" onclick="setCustomPosition()">Güncelle</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Durum Bilgisi -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Pozisyon Durumu</h6>
                </div>
                <div class="card-body">
                    <?php 
                    $statusClass = 'secondary';
                    $statusText = 'Veri Yok';
                    
                    if ($current_position) {
                        if ($current_position <= 3) {
                            $statusClass = 'success';
                            $statusText = 'Mükemmel';
                        } elseif ($current_position <= 10) {
                            $statusClass = 'primary';
                            $statusText = 'İyi';
                        } elseif ($current_position <= 20) {
                            $statusClass = 'warning';
                            $statusText = 'Orta';
                        } else {
                            $statusClass = 'danger';
                            $statusText = 'Zayıf';
                        }
                    }
                    ?>
                    <div class="text-center">
                        <span class="badge bg-<?= $statusClass ?> fs-6"><?= $statusText ?></span>
                    </div>
                    
                    <?php if ($current_position && $target_position): ?>
                    <hr>
                    <div class="small">
                        <?php 
                        $diff = $current_position - $target_position;
                        if ($diff > 0): ?>
                            <div class="text-danger">Hedefe <?= $diff ?> sıra var</div>
                        <?php elseif ($diff < 0): ?>
                            <div class="text-success">Hedefin <?= abs($diff) ?> sıra üstünde</div>
                        <?php else: ?>
                            <div class="text-success">Hedefe ulaşıldı!</div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Hızlı pozisyon güncelleme fonksiyonları
function setQuickPosition(position) {
    document.getElementById('current_position').value = position;
    animatePositionChange();
}

function setCustomPosition() {
    const customPos = document.getElementById('quickPositionInput').value;
    if (customPos && customPos >= 1 && customPos <= 200) {
        document.getElementById('current_position').value = customPos;
        document.getElementById('quickPositionInput').value = '';
        animatePositionChange();
    }
}

function animatePositionChange() {
    const posInput = document.getElementById('current_position');
    posInput.style.backgroundColor = '#e3f2fd';
    posInput.style.transition = 'background-color 0.5s';
    setTimeout(() => {
        posInput.style.backgroundColor = '';
    }, 1000);
}

// Form submit validasyonu
document.getElementById('keywordForm').addEventListener('submit', function(e) {
    const keyword = document.getElementById('keyword').value.trim();
    const projectId = document.getElementById('project_id').value;
    const targetPosition = document.getElementById('target_position').value;
    
    if (!keyword) {
        alert('Anahtar kelime boş olamaz.');
        e.preventDefault();
        return false;
    }
    
    if (!projectId) {
        alert('Proje seçimi gereklidir.');
        e.preventDefault();
        return false;
    }
    
    if (!targetPosition || targetPosition < 1 || targetPosition > 100) {
        alert('Hedef pozisyon 1-100 arasında olmalıdır.');
        e.preventDefault();
        return false;
    }
});

// Sayfa yüklendiğinde pozisyon durumunu güncelle
document.addEventListener('DOMContentLoaded', function() {
    // Otomatik anahtar kelime düzenleme
    const keywordInput = document.getElementById('keyword');
    keywordInput.addEventListener('blur', function() {
        this.value = this.value.trim().replace(/\s+/g, ' ').toLowerCase();
    });
});
</script>