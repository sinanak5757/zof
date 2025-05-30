<?php
// İçerik Düzenleme Sayfası

// Fonksiyonu çakışmaları önlemek için kontrol ve yeniden adlandırma
if (!function_exists('createSlugFromTitle')) {
    function createSlugFromTitle($string) {
        // Türkçe karakterleri değiştir
        $string = str_replace(
            ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç', ' '],
            ['i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c', '-'],
            $string
        );
        
        // Küçük harfe çevir
        $string = mb_strtolower($string, 'UTF-8');
        
        // Alfanümerik olmayan karakterleri kaldır
        $string = preg_replace('/[^a-z0-9-]/', '', $string);
        
        // Birden fazla tire işaretlerini tek tireye dönüştür
        $string = preg_replace('/-+/', '-', $string);
        
        // Baştaki ve sondaki tireleri kaldır
        $string = trim($string, '-');
        
        return $string;
    }
}

// İçerik ID'sini kontrol et
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('warning', 'Düzenlenecek içerik ID belirtilmedi');
    redirect(url('index.php?page=content'));
}

$contentId = intval($_GET['id']);

// Mevcut içeriği veritabanından al
try {
    $content = $db->getRow("SELECT * FROM content WHERE id = ?", [$contentId]);
    
    if (!$content) {
        setFlashMessage('danger', 'Düzenlenecek içerik bulunamadı');
        redirect(url('index.php?page=content'));
    }
} catch (Exception $e) {
    setFlashMessage('danger', 'Veritabanı hatası: ' . $e->getMessage());
    redirect(url('index.php?page=content'));
}

// Form verilerini al
$title = isset($_POST['title']) ? trim($_POST['title']) : $content['title'];
$content_text = isset($_POST['content']) ? trim($_POST['content']) : $content['content'];
$slug = isset($_POST['slug']) ? trim($_POST['slug']) : $content['slug'];
$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : (isset($content['category_id']) ? $content['category_id'] : 0);
$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : (isset($content['project_id']) ? $content['project_id'] : 0);
$status = isset($_POST['status']) ? trim($_POST['status']) : $content['status'];
$featured_image = isset($_POST['featured_image']) ? trim($_POST['featured_image']) : (isset($content['featured_image']) ? $content['featured_image'] : '');
$meta_title = isset($_POST['meta_title']) ? trim($_POST['meta_title']) : (isset($content['meta_title']) ? $content['meta_title'] : '');
$meta_description = isset($_POST['meta_description']) ? trim($_POST['meta_description']) : (isset($content['meta_description']) ? $content['meta_description'] : '');
$meta_keywords = isset($_POST['meta_keywords']) ? trim($_POST['meta_keywords']) : (isset($content['meta_keywords']) ? $content['meta_keywords'] : '');

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Başlık doğrulama
    if (empty($title)) {
        $errors[] = 'İçerik başlığı boş olamaz.';
    }
    
    // İçerik doğrulama
    if (empty($content_text)) {
        $errors[] = 'İçerik boş olamaz.';
    }
    
    // Slug kontrolü ve oluşturma
    if (empty($slug)) {
        // Başlıktan otomatik slug oluştur
        $slug = createSlugFromTitle($title);
    } else {
        // Var olan slug'ı düzenle
        $slug = createSlugFromTitle($slug);
    }
    
    // Slug benzersizlik kontrolü (mevcut içerik hariç)
    try {
        $existingContent = $db->getRow("SELECT id FROM content WHERE slug = ? AND id != ?", [$slug, $contentId]);
        if ($existingContent) {
            $errors[] = 'Bu URL adresi (slug) zaten kullanılıyor. Lütfen başka bir slug girin.';
        }
    } catch (Exception $e) {
        $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
    }
    
    // Hata yoksa veritabanını güncelle
    if (empty($errors)) {
        try {
            // Veritabanında güncellenecek veriyi hazırla
            $data = [
                'title' => $title,
                'content' => $content_text,
                'slug' => $slug,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // İsteğe bağlı alanları ekle
            if (!empty($category_id)) {
                $data['category_id'] = $category_id;
            } else {
                $data['category_id'] = null;
            }
            
            if (!empty($project_id)) {
                $data['project_id'] = $project_id;
            } else {
                $data['project_id'] = null;
            }
            
            if (!empty($featured_image)) {
                $data['featured_image'] = $featured_image;
            } else {
                $data['featured_image'] = null;
            }
            
            if (!empty($meta_title)) {
                $data['meta_title'] = $meta_title;
            } else {
                $data['meta_title'] = $title; // Meta title boşsa başlığı kullan
            }
            
            if (!empty($meta_description)) {
                $data['meta_description'] = $meta_description;
            } else {
                $data['meta_description'] = null;
            }
            
            if (!empty($meta_keywords)) {
                $data['meta_keywords'] = $meta_keywords;
            } else {
                $data['meta_keywords'] = null;
            }
            
            // Güncelleyen kullanıcı bilgisi varsa ekle
            if (isset($_SESSION['user_id'])) {
                $data['updated_by'] = $_SESSION['user_id'];
            }
            
            // Veritabanını güncelle
            $updated = $db->update('content', $data, 'id = ?', [$contentId]);
            
            if ($updated) {
                setFlashMessage('success', 'İçerik başarıyla güncellendi.');
                redirect(url('index.php?page=content&action=view&id=' . $contentId));
            } else {
                $errors[] = 'İçerik güncellenirken bir hata oluştu.';
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

// Kategorileri al
try {
    $categories = $db->getAll("SELECT id, name FROM content_categories ORDER BY name");
} catch (Exception $e) {
    // Kategori tablosu yoksa veya hata olursa boş dizi kullan
    $categories = [];
    setFlashMessage('warning', 'Kategoriler yüklenemedi: ' . $e->getMessage());
}

// Projeleri al
try {
    $projects = $db->getAll("SELECT id, project_name FROM projects ORDER BY project_name ASC");
} catch (Exception $e) {
    // Proje tablosu yoksa veya hata olursa boş dizi kullan
    $projects = [];
    setFlashMessage('warning', 'Projeler yüklenemedi: ' . $e->getMessage());
}

// İçerik durum seçenekleri
$statusOptions = [
    'draft' => 'Taslak',
    'pending' => 'İnceleme Bekliyor',
    'published' => 'Yayınlandı',
    'archived' => 'Arşivlendi'
];

// Sayfa başlığı
$pageTitle = 'İçerik Düzenle: ' . htmlspecialchars($title);
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>İçerik Düzenle</h4>
        <div>
            <a href="<?= url('index.php?page=content&action=view&id=' . $contentId) ?>" class="btn btn-outline-primary me-2">
                <i class="fas fa-eye me-1"></i> Görüntüle
            </a>
            <a href="<?= url('index.php?page=content') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> İçerik Listesine Dön
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-9">
            <!-- Ana İçerik Kartı -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form action="<?= url('index.php?page=content&action=edit&id=' . $contentId) ?>" method="post" id="contentForm" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="title" class="form-label">İçerik Başlığı *</label>
                            <input type="text" class="form-control" id="title" name="title" required value="<?= htmlspecialchars($title) ?>">
                            <div class="form-text">İçerik için açıklayıcı bir başlık girin.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="slug" class="form-label">URL Adresi (Slug)</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="<?= htmlspecialchars($slug) ?>">
                            <div class="form-text">Boş bırakırsanız başlıktan otomatik oluşturulacaktır.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="content" class="form-label">İçerik *</label>
                            <textarea class="form-control" id="content" name="content" rows="10" required><?= htmlspecialchars($content_text) ?></textarea>
                        </div>
                        
                        <!-- SEO Bilgileri Accordion -->
                        <div class="accordion mb-4" id="seoAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingSeo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeo" aria-expanded="false" aria-controls="collapseSeo">
                                        SEO Bilgileri
                                    </button>
                                </h2>
                                <div id="collapseSeo" class="accordion-collapse collapse" aria-labelledby="headingSeo" data-bs-parent="#seoAccordion">
                                    <div class="accordion-body">
                                        <div class="mb-3">
                                            <label for="meta_title" class="form-label">Meta Başlık</label>
                                            <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?= htmlspecialchars($meta_title) ?>">
                                            <div class="form-text">Boş bırakırsanız içerik başlığı kullanılır.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="meta_description" class="form-label">Meta Açıklama</label>
                                            <textarea class="form-control" id="meta_description" name="meta_description" rows="2"><?= htmlspecialchars($meta_description) ?></textarea>
                                            <div class="form-text">Arama motorlarında görüntülenecek kısa açıklama.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="meta_keywords" class="form-label">Meta Anahtar Kelimeler</label>
                                            <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" value="<?= htmlspecialchars($meta_keywords) ?>">
                                            <div class="form-text">Virgülle ayrılmış anahtar kelimeler.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">İçeriği Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3">
            <!-- Yan Bilgiler Kartı -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">İçerik Ayarları</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="status" class="form-label">Durum</label>
                        <select class="form-select" id="status" name="status" form="contentForm">
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $status == $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Kategori</label>
                        <select class="form-select" id="category_id" name="category_id" form="contentForm">
                            <option value="">Kategori Seçiniz</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $category_id == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="project_id" class="form-label">Proje</label>
                        <select class="form-select" id="project_id" name="project_id" form="contentForm">
                            <option value="">Proje Seçiniz</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['id'] ?>" <?= $project_id == $project['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($project['project_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">İçeriği bir projeyle ilişkilendirin.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="featured_image" class="form-label">Öne Çıkan Görsel</label>
                        <input type="text" class="form-control" id="featured_image" name="featured_image" value="<?= htmlspecialchars($featured_image) ?>" form="contentForm">
                        <div class="form-text">Görsel URL'sini girin veya medya kütüphanesinden seçin.</div>
                    </div>
                </div>
            </div>
            
            <!-- İçerik Bilgileri Kartı -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">İçerik Bilgileri</h6>
                </div>
                <div class="card-body">
                    <div class="small text-muted mb-2">Oluşturulma Tarihi:</div>
                    <p class="mb-3"><?= isset($content['created_at']) ? date('d.m.Y H:i', strtotime($content['created_at'])) : '-' ?></p>
                    
                    <div class="small text-muted mb-2">Son Güncelleme:</div>
                    <p class="mb-3"><?= isset($content['updated_at']) ? date('d.m.Y H:i', strtotime($content['updated_at'])) : '-' ?></p>
                    
                    <?php if (isset($content['views'])): ?>
                    <div class="small text-muted mb-2">Görüntülenme:</div>
                    <p class="mb-0"><?= number_format($content['views']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TinyMCE gibi bir içerik editörü eklemek için JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Başlık değiştiğinde otomatik slug oluşturma
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    
    if (titleInput && slugInput) {
        titleInput.addEventListener('blur', function() {
            if (slugInput.value === '') {
                slugInput.value = createSlugFromTitleJS(titleInput.value);
            }
        });
    }
    
    // JavaScript ile slug oluşturma fonksiyonu
    function createSlugFromTitleJS(string) {
        // Türkçe karakterleri değiştir
        const replacements = {
            'ı': 'i', 'ğ': 'g', 'ü': 'u', 'ş': 's', 'ö': 'o', 'ç': 'c',
            'İ': 'i', 'Ğ': 'g', 'Ü': 'u', 'Ş': 's', 'Ö': 'o', 'Ç': 'c',
            ' ': '-'
        };
        
        for (let key in replacements) {
            string = string.replace(new RegExp(key, 'g'), replacements[key]);
        }
        
        // Küçük harfe çevir
        string = string.toLowerCase();
        
        // Alfanümerik olmayan karakterleri kaldır
        string = string.replace(/[^a-z0-9-]/g, '');
        
        // Birden fazla tire işaretlerini tek tireye dönüştür
        string = string.replace(/-+/g, '-');
        
        // Baştaki ve sondaki tireleri kaldır
        string = string.replace(/^-+|-+$/g, '');
        
        return string;
    }
});
</script>