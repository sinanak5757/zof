<?php
// İçerik Ekleme Sayfası

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

// POST verilerini al
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : (isset($_GET['project_id']) ? intval($_GET['project_id']) : 0);
$status = isset($_POST['status']) ? trim($_POST['status']) : 'draft';
$featured_image = isset($_POST['featured_image']) ? trim($_POST['featured_image']) : '';
$meta_title = isset($_POST['meta_title']) ? trim($_POST['meta_title']) : '';
$meta_description = isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '';
$meta_keywords = isset($_POST['meta_keywords']) ? trim($_POST['meta_keywords']) : '';

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Başlık doğrulama
    if (empty($title)) {
        $errors[] = 'İçerik başlığı boş olamaz.';
    }
    
    // İçerik doğrulama
    if (empty($content)) {
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
    
    // Slug benzersizlik kontrolü
    try {
        $existingContent = $db->getRow("SELECT id FROM content WHERE slug = ?", [$slug]);
        if ($existingContent) {
            $errors[] = 'Bu URL adresi (slug) zaten kullanılıyor. Lütfen başka bir slug girin.';
        }
    } catch (Exception $e) {
        $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
    }
    
    // Hata yoksa veritabanına ekle
    if (empty($errors)) {
        try {
            // Veritabanına eklenecek veriyi hazırla
            $data = [
                'title' => $title,
                'content' => $content,
                'slug' => $slug,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // İsteğe bağlı alanları ekle
            if (!empty($category_id)) {
                $data['category_id'] = $category_id;
            }
            
            if (!empty($project_id)) {
                $data['project_id'] = $project_id;
            }
            
            if (!empty($featured_image)) {
                $data['featured_image'] = $featured_image;
            }
            
            if (!empty($meta_title)) {
                $data['meta_title'] = $meta_title;
            } else {
                $data['meta_title'] = $title; // Meta title boşsa başlığı kullan
            }
            
            if (!empty($meta_description)) {
                $data['meta_description'] = $meta_description;
            }
            
            if (!empty($meta_keywords)) {
                $data['meta_keywords'] = $meta_keywords;
            }
            
            // Oluşturan kullanıcı bilgisi varsa ekle
            if (isset($_SESSION['user_id'])) {
                $data['created_by'] = $_SESSION['user_id'];
            }
            
            // Veritabanına ekle
            $contentId = $db->insert('content', $data);
            
            if ($contentId) {
                setFlashMessage('success', 'İçerik başarıyla eklendi.');
                redirect(url('index.php?page=content&action=view&id=' . $contentId));
            } else {
                $errors[] = 'İçerik eklenirken bir hata oluştu.';
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
$pageTitle = 'Yeni İçerik Ekle';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Yeni İçerik Ekle</h4>
        <a href="<?= url('index.php?page=content') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> İçerik Listesine Dön
        </a>
    </div>
    
    <div class="row">
        <div class="col-lg-9">
            <!-- Ana İçerik Kartı -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form action="<?= url('index.php?page=content&action=add') ?>" method="post" id="contentForm" class="needs-validation" novalidate>
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
                            <textarea class="form-control" id="content" name="content" rows="10" required><?= htmlspecialchars($content) ?></textarea>
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
                            <button type="submit" class="btn btn-primary">İçeriği Kaydet</button>
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
            
            <!-- Yardım Kartı -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Yardım</h6>
                </div>
                <div class="card-body">
                    <p><i class="fas fa-info-circle text-info me-1"></i> İçeriğinizi düzenlemek için içerik düzenleyicisini kullanabilirsiniz.</p>
                    <p><i class="fas fa-lightbulb text-warning me-1"></i> SEO bilgilerinizi doğru bir şekilde yapılandırmak, içeriğinizin arama motorlarında daha iyi sıralanmasına yardımcı olabilir.</p>
                    <p><i class="fas fa-tag text-primary me-1"></i> İçeriğinizi bir kategoriye ve projeye atamak, düzenleme ve arama işlemlerinizi kolaylaştırır.</p>
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