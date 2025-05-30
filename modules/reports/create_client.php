<?php
// reports/create_client.php - Müşteri Raporu Oluşturma Sayfası

// Müşteri ID'si varsa al
$selectedClientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// Form verilerini al
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : $selectedClientId;
$website_url = isset($_POST['website_url']) ? trim($_POST['website_url']) : '';
$report_period = isset($_POST['report_period']) ? $_POST['report_period'] : 'monthly';
$keywords = isset($_POST['keywords']) ? trim($_POST['keywords']) : '';
$project_goals = isset($_POST['project_goals']) ? trim($_POST['project_goals']) : '';
$ai_prompt = isset($_POST['ai_prompt']) ? trim($_POST['ai_prompt']) : '';
$include_sections = isset($_POST['include_sections']) ? $_POST['include_sections'] : [];

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validasyon
    if (empty($title)) {
        $errors[] = 'Rapor başlığı boş olamaz.';
    }
    
    if ($client_id <= 0) {
        $errors[] = 'Lütfen bir müşteri seçin.';
    }
    
    if (empty($website_url)) {
        $errors[] = 'Website URL\'si boş olamaz.';
    } elseif (!filter_var($website_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Geçerli bir website URL\'si girin.';
    }
    
    // Hata yoksa veritabanına kaydet
    if (empty($errors)) {
        try {
            // Rapor verilerini hazırla
            $reportData = [
                'title' => $title,
                'report_type' => 'client_report',
                'client_id' => $client_id,
                'website_url' => $website_url,
                'report_period' => $report_period,
                'keywords' => $keywords,
                'project_goals' => $project_goals,
                'ai_prompt' => $ai_prompt,
                'include_sections' => json_encode($include_sections),
                'status' => 'draft',
                'ai_enhanced' => !empty($ai_prompt) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (isset($_SESSION['user_id'])) {
                $reportData['created_by'] = $_SESSION['user_id'];
            }
            
            // Raporu kaydet
            $reportId = $db->insert('reports', $reportData);
            
            if ($reportId) {
                // AI ile rapor oluştur parametresi varsa
                if (isset($_POST['generate_with_ai']) && !empty($ai_prompt)) {
                    setFlashMessage('info', 'Rapor taslağı oluşturuldu. AI ile içerik oluşturuluyor...');
                    redirect(url('index.php?page=reports&action=generate&id=' . $reportId));
                } else {
                    setFlashMessage('success', 'Rapor başarıyla oluşturuldu.');
                    redirect(url('index.php?page=reports&action=edit&id=' . $reportId));
                }
            } else {
                $errors[] = 'Rapor oluşturulurken bir hata oluştu.';
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

// Müşterileri al
try {
    $clients = $db->getAll("SELECT id, client_name, website_url FROM clients ORDER BY client_name ASC");
} catch (Exception $e) {
    $clients = [];
    setFlashMessage('warning', 'Müşteriler yüklenemedi: ' . $e->getMessage());
}

// Rapor dönemleri
$reportPeriods = [
    'weekly' => 'Haftalık',
    'monthly' => 'Aylık',
    'quarterly' => 'Üç Aylık',
    'yearly' => 'Yıllık',
    'custom' => 'Özel Dönem'
];

// Rapor bölümleri
$availableSections = [
    'executive_summary' => 'Yönetici Özeti',
    'traffic_analysis' => 'Trafik Analizi',
    'keyword_performance' => 'Anahtar Kelime Performansı',
    'technical_seo' => 'Teknik SEO',
    'content_analysis' => 'İçerik Analizi',
    'backlink_analysis' => 'Backlink Analizi',
    'competitor_analysis' => 'Rakip Analizi',
    'conversion_analysis' => 'Dönüşüm Analizi',
    'recommendations' => 'Öneriler ve Aksiyonlar',
    'next_steps' => 'Gelecek Dönem Planı'
];

// Seçili müşterinin bilgilerini al
$selectedClient = null;
if ($client_id > 0) {
    try {
        $selectedClient = $db->getRow("SELECT * FROM clients WHERE id = ?", [$client_id]);
        if ($selectedClient && empty($website_url)) {
            $website_url = $selectedClient['website_url'] ?? '';
        }
    } catch (Exception $e) {
        // Hata durumunda devam et
    }
}

// Sayfa başlığı
$pageTitle = 'Yeni Müşteri Raporu Oluştur';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4><i class="fas fa-chart-bar me-2"></i>Müşteri Raporu Oluştur</h4>
            <p class="text-muted mb-0">Detaylı SEO performans raporu hazırlayın</p>
        </div>
        <a href="<?= url('index.php?page=reports') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Raporlara Dön
        </a>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Ana Form Kartı -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Rapor Bilgileri</h6>
                </div>
                <div class="card-body">
                    <form action="<?= url('index.php?page=reports&action=create&type=client_report') ?>" method="post" id="clientReportForm" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">Rapor Başlığı *</label>
                                <input type="text" class="form-control" id="title" name="title" required value="<?= htmlspecialchars($title) ?>">
                                <div class="form-text">Rapor için açıklayıcı bir başlık girin.</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="client_id" class="form-label">Müşteri *</label>
                                <select class="form-select" id="client_id" name="client_id" required>
                                    <option value="">Müşteri Seçin</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?= $client['id'] ?>" 
                                                data-website="<?= htmlspecialchars($client['website_url'] ?? '') ?>"
                                                <?= $client_id == $client['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($client['client_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="website_url" class="form-label">Website URL *</label>
                                <input type="url" class="form-control" id="website_url" name="website_url" required value="<?= htmlspecialchars($website_url) ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="report_period" class="form-label">Rapor Dönemi</label>
                                <select class="form-select" id="report_period" name="report_period">
                                    <?php foreach ($reportPeriods as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $report_period == $value ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="keywords" class="form-label">Ana Anahtar Kelimeler</label>
                            <textarea class="form-control" id="keywords" name="keywords" rows="3" placeholder="anahtar kelime 1, anahtar kelime 2, anahtar kelime 3..."><?= htmlspecialchars($keywords) ?></textarea>
                            <div class="form-text">Virgülle ayırarak ana anahtar kelimeleri girin.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="project_goals" class="form-label">Proje Hedefleri</label>
                            <textarea class="form-control" id="project_goals" name="project_goals" rows="4" placeholder="Bu proje için belirlenen ana hedefleri açıklayın..."><?= htmlspecialchars($project_goals) ?></textarea>
                        </div>
                        
                        <!-- Rapor Bölümleri -->
                        <div class="mb-4">
                            <label class="form-label">Raporda Yer Alacak Bölümler</label>
                            <div class="row">
                                <?php foreach ($availableSections as $section => $sectionLabel): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="include_sections[]" value="<?= $section ?>" id="section_<?= $section ?>" 
                                                   <?= in_array($section, $include_sections) || empty($include_sections) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="section_<?= $section ?>">
                                                <?= htmlspecialchars($sectionLabel) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- AI Destekli Rapor Geliştirme -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-gradient-primary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-robot me-2"></i>AI Destekli Rapor Optimizasyonu (Grok AI)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="ai-info-box mb-3 p-3 bg-light rounded">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-info-circle text-info me-2"></i>
                            <strong>AI Nasıl Yardımcı Olur?</strong>
                        </div>
                        <ul class="mb-0 small">
                            <li>Performans verilerini analiz eder ve içgörüler sunar</li>
                            <li>Rakip analizi ve pazar konumlandırması yapar</li>
                            <li>Özelleştirilmiş öneriler ve aksiyon planları oluşturur</li>
                            <li>Profesyonel rapor dili ve yapısı sağlar</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ai_prompt" class="form-label">
                            <i class="fas fa-magic me-1"></i>AI Rapor Geliştirme Talimatları
                        </label>
                        <textarea class="form-control" id="ai_prompt" name="ai_prompt" rows="4" form="clientReportForm" 
                                  placeholder="Raporda özel olarak vurgulanmasını istediğiniz konuları belirtin..."><?= htmlspecialchars($ai_prompt) ?></textarea>
                        <div class="form-text">AI bu talimatları kullanarak raporu kişiselleştirecek ve zenginleştirecektir.</div>
                    </div>
                    
                    <!-- AI Öneri Chips -->
                    <div class="mb-3">
                        <label class="form-label">Hızlı Seçenekler:</label>
                        <div class="ai-suggestions">
                            <button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2" onclick="addToAIPrompt('Organik trafik artışına özel odaklanma')">
                                📈 Organik Trafik Odaklı
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2" onclick="addToAIPrompt('Teknik SEO sorunlarını detaylandırma')">
                                🔧 Teknik SEO Detayı
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2" onclick="addToAIPrompt('İçerik stratejisi önerileri')">
                                📝 İçerik Stratejisi
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2" onclick="addToAIPrompt('Rakip analizi derinleştirme')">
                                🎯 Rakip Analizi
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2" onclick="addToAIPrompt('E-ticaret SEO optimizasyonu')">
                                🛒 E-ticaret SEO
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2" onclick="addToAIPrompt('Yerel SEO stratejileri')">
                                📍 Yerel SEO
                            </button>
                        </div>
                    </div>
                    
                    <!-- AI Rapor Önizleme -->
                    <div class="ai-preview d-none" id="aiPreview">
                        <div class="alert alert-info">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Yükleniyor...</span>
                                </div>
                                <strong>AI rapor içeriği oluşturuyor...</strong>
                            </div>
                            <small class="d-block mt-1">Bu işlem 30-60 saniye sürebilir.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Rapor Önizleme -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Rapor Önizleme</h6>
                </div>
                <div class="card-body">
                    <div id="reportPreview" class="report-preview-box">
                        <div class="text-center text-muted">
                            <i class="fas fa-file-alt fa-3x mb-3"></i>
                            <p>Form dolduruldukça rapor önizlemesi burada görünecek</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Rapor Şablonları -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Hızlı Şablonlar</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadTemplate('ecommerce')">
                            <i class="fas fa-shopping-cart me-1"></i> E-ticaret Raporu
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadTemplate('corporate')">
                            <i class="fas fa-building me-1"></i> Kurumsal Rapor
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadTemplate('local')">
                            <i class="fas fa-map-marker-alt me-1"></i> Yerel İşletme
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadTemplate('startup')">
                            <i class="fas fa-rocket me-1"></i> Startup Raporu
                        </button>
                    </div>
                    <hr>
                    <small class="text-muted">
                        <i class="fas fa-lightbulb me-1"></i>
                        Şablonlar otomatik olarak uygun bölümleri seçer ve AI talimatlarını doldurur.
                    </small>
                </div>
            </div>
            
            <!-- Kaydetme Seçenekleri -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Kaydetme Seçenekleri</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" form="clientReportForm" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Taslak Olarak Kaydet
                        </button>
                        <button type="submit" form="clientReportForm" name="generate_with_ai" value="1" class="btn btn-primary">
                            <i class="fas fa-robot me-1"></i> AI ile Oluştur
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="previewReport()">
                            <i class="fas fa-eye me-1"></i> Önizleme
                        </button>
                    </div>
                    
                    <hr>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="saveTemplate" name="save_template">
                        <label class="form-check-label small" for="saveTemplate">
                            Bu ayarları şablon olarak kaydet
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Yardım -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-question-circle me-1"></i>Yardım
                    </h6>
                </div>
                <div class="card-body">
                    <div class="accordion" id="helpAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingAI">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAI">
                                    AI Özelliği Nasıl Kullanılır?
                                </button>
                            </h2>
                            <div id="collapseAI" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                <div class="accordion-body small">
                                    AI özelliği Grok API kullanarak raporu zenginleştirir. Özel talimatlar vererek AI'ın odaklanacağı konuları belirleyebilirsiniz.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingSections">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSections">
                                    Hangi Bölümler Seçilmeli?
                                </button>
                            </h2>
                            <div id="collapseSections" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                <div class="accordion-body small">
                                    Müşteri ihtiyaçlarına göre bölümler seçin. E-ticaret siteleri için dönüşüm analizi, kurumsal siteler için teknik SEO önemlidir.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ai-suggestions .btn {
    font-size: 0.8rem;
}

.report-preview-box {
    min-height: 200px;
    border: 2px dashed #e3e6f0;
    border-radius: 0.35rem;
    padding: 1rem;
}

.bg-gradient-primary {
    background: linear-gradient(87deg, #5e72e4 0, #825ee4 100%) !important;
}

.ai-info-box {
    border-left: 4px solid #36b9cc;
}

.form-check-input:checked {
    background-color: #5e72e4;
    border-color: #5e72e4;
}

.card-header.bg-gradient-primary {
    color: white !important;
}

.card-header.bg-gradient-primary h6 {
    color: white !important;
}
</style>

<script>
// Müşteri seçimi değiştiğinde website URL'sini doldur
document.getElementById('client_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const websiteUrl = selectedOption.getAttribute('data-website');
    const websiteUrlInput = document.getElementById('website_url');
    
    if (websiteUrl && !websiteUrlInput.value) {
        websiteUrlInput.value = websiteUrl;
    }
    
    updateReportPreview();
});

// AI prompt'a önerileri ekle
function addToAIPrompt(suggestion) {
    const aiPrompt = document.getElementById('ai_prompt');
    const currentValue = aiPrompt.value.trim();
    
    if (currentValue) {
        aiPrompt.value = currentValue + '\n' + suggestion;
    } else {
        aiPrompt.value = suggestion;
    }
    
    // Textarea yüksekliğini ayarla
    aiPrompt.style.height = 'auto';
    aiPrompt.style.height = aiPrompt.scrollHeight + 'px';
}

// Rapor şablonlarını yükle
function loadTemplate(templateType) {
    const templates = {
        ecommerce: {
            sections: ['executive_summary', 'traffic_analysis', 'keyword_performance', 'conversion_analysis', 'competitor_analysis', 'recommendations'],
            aiPrompt: 'E-ticaret sitesi için dönüşüm odaklı analiz yapın. Ürün sayfalarının performansını, alışveriş funnel\'ının optimizasyonunu ve e-ticaret SEO stratejilerini vurgulayın.'
        },
        corporate: {
            sections: ['executive_summary', 'traffic_analysis', 'technical_seo', 'content_analysis', 'backlink_analysis', 'recommendations'],
            aiPrompt: 'Kurumsal website için teknik SEO ve marka otoritesi odaklı analiz yapın. Sayfa hızı, site mimarisi ve kurumsal içerik stratejilerini öne çıkarın.'
        },
        local: {
            sections: ['executive_summary', 'traffic_analysis', 'keyword_performance', 'content_analysis', 'recommendations'],
            aiPrompt: 'Yerel işletme için yerel SEO stratejilerine odaklanın. Google My Business optimizasyonu, yerel anahtar kelimeler ve müşteri yorumlarını analiz edin.'
        },
        startup: {
            sections: ['executive_summary', 'traffic_analysis', 'keyword_performance', 'competitor_analysis', 'next_steps'],
            aiPrompt: 'Startup için büyüme odaklı SEO stratejisi hazırlayın. Hızlı kazanımlar, maliyet-etkin yöntemler ve ölçeklenebilir SEO yaklaşımlarını vurgulayın.'
        }
    };
    
    const template = templates[templateType];
    if (!template) return;
    
    // Bölümleri seç
    document.querySelectorAll('input[name="include_sections[]"]').forEach(checkbox => {
        checkbox.checked = template.sections.includes(checkbox.value);
    });
    
    // AI prompt'u doldur
    document.getElementById('ai_prompt').value = template.aiPrompt;
    
    // Başlık önerisinde bulun
    const clientSelect = document.getElementById('client_id');
    const selectedClient = clientSelect.options[clientSelect.selectedIndex].text;
    const titleInput = document.getElementById('title');
    
    if (selectedClient && selectedClient !== 'Müşteri Seçin') {
        const templateTitles = {
            ecommerce: `${selectedClient} - E-ticaret SEO Performans Raporu`,
            corporate: `${selectedClient} - Kurumsal SEO Analiz Raporu`,
            local: `${selectedClient} - Yerel SEO Performans Raporu`,
            startup: `${selectedClient} - Startup SEO Büyüme Raporu`
        };
        
        if (!titleInput.value) {
            titleInput.value = templateTitles[templateType];
        }
    }
    
    updateReportPreview();
    
    // Bilgilendirme mesajı göster
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
    alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        <strong>${templateType.charAt(0).toUpperCase() + templateType.slice(1)} şablonu yüklendi!</strong>
        Ayarlar otomatik olarak dolduruldu.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const form = document.getElementById('clientReportForm');
    form.insertBefore(alertDiv, form.firstChild);
    
    // 3 saniye sonra mesajı kaldır
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);
}

// Rapor önizlemesini güncelle
function updateReportPreview() {
    const title = document.getElementById('title').value;
    const clientSelect = document.getElementById('client_id');
    const clientName = clientSelect.options[clientSelect.selectedIndex].text;
    const websiteUrl = document.getElementById('website_url').value;
    const period = document.getElementById('report_period').value;
    
    const previewBox = document.getElementById('reportPreview');
    
    if (title || (clientName && clientName !== 'Müşteri Seçin')) {
        previewBox.innerHTML = `
            <div class="preview-header mb-3">
                <h6 class="text-primary">${title || 'Rapor Başlığı'}</h6>
                <small class="text-muted">${clientName !== 'Müşteri Seçin' ? clientName : 'Müşteri'}</small>
            </div>
            <div class="preview-details">
                ${websiteUrl ? `<p class="small mb-1"><strong>Website:</strong> ${websiteUrl}</p>` : ''}
                <p class="small mb-1"><strong>Dönem:</strong> ${document.getElementById('report_period').options[document.getElementById('report_period').selectedIndex].text}</p>
                <p class="small mb-0"><strong>Tarih:</strong> ${new Date().toLocaleDateString('tr-TR')}</p>
            </div>
        `;
    } else {
        previewBox.innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-file-alt fa-3x mb-3"></i>
                <p>Form dolduruldukça rapor önizlemesi burada görünecek</p>
            </div>
        `;
    }
}

// Rapor önizlemesi
function previewReport() {
    const form = document.getElementById('clientReportForm');
    const formData = new FormData(form);
    
    // Yeni pencerede önizleme aç
    const previewWindow = window.open('', '_blank', 'width=800,height=600');
    previewWindow.document.write(`
        <html>
        <head>
            <title>Rapor Önizleme</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="p-4">
            <div class="container">
                <h3>${formData.get('title') || 'Rapor Başlığı'}</h3>
                <p class="text-muted">Bu bir önizlemedir. Gerçek rapor AI tarafından oluşturulacaktır.</p>
                <hr>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Rapor içeriği seçilen bölümler ve AI talimatlarına göre oluşturulacaktır.
                </div>
            </div>
        </body>
        </html>
    `);
}

// Form alanları değiştiğinde önizlemeyi güncelle
document.getElementById('title').addEventListener('input', updateReportPreview);
document.getElementById('website_url').addEventListener('input', updateReportPreview);
document.getElementById('report_period').addEventListener('change', updateReportPreview);

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    updateReportPreview();
    
    // Form validation
    const form = document.getElementById('clientReportForm');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>