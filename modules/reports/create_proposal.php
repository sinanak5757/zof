<?php
// reports/create_proposal.php - Teklif Raporu Oluşturma Sayfası

// Form verilerini al
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$prospect_name = isset($_POST['prospect_name']) ? trim($_POST['prospect_name']) : '';
$prospect_email = isset($_POST['prospect_email']) ? trim($_POST['prospect_email']) : '';
$website_url = isset($_POST['website_url']) ? trim($_POST['website_url']) : '';
$industry = isset($_POST['industry']) ? $_POST['industry'] : '';
$budget_range = isset($_POST['budget_range']) ? $_POST['budget_range'] : '';
$current_challenges = isset($_POST['current_challenges']) ? trim($_POST['current_challenges']) : '';
$ai_prompt = isset($_POST['ai_prompt']) ? trim($_POST['ai_prompt']) : '';
$service_packages = isset($_POST['service_packages']) ? $_POST['service_packages'] : [];
$proposal_sections = isset($_POST['proposal_sections']) ? $_POST['proposal_sections'] : [];
$competition_analysis = isset($_POST['competition_analysis']) ? 1 : 0;
$quick_wins = isset($_POST['quick_wins']) ? 1 : 0;

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validasyon
    if (empty($title)) {
        $errors[] = 'Teklif başlığı boş olamaz.';
    }
    
    if (empty($prospect_name)) {
        $errors[] = 'Firma/Kişi adı boş olamaz.';
    }
    
    if (empty($website_url)) {
        $errors[] = 'Website URL\'si boş olamaz.';
    } elseif (!filter_var($website_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Geçerli bir website URL\'si girin.';
    }
    
    if (!empty($prospect_email) && !filter_var($prospect_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi girin.';
    }
    
    // Hata yoksa veritabanına kaydet
    if (empty($errors)) {
        try {
            // Teklif verilerini hazırla
            $proposalData = [
                'title' => $title,
                'report_type' => 'proposal_report',
                'prospect_name' => $prospect_name,
                'prospect_email' => $prospect_email,
                'website_url' => $website_url,
                'industry' => $industry,
                'budget_range' => $budget_range,
                'current_challenges' => $current_challenges,
                'ai_prompt' => $ai_prompt,
                'service_packages' => json_encode($service_packages),
                'proposal_sections' => json_encode($proposal_sections),
                'competition_analysis' => $competition_analysis,
                'quick_wins' => $quick_wins,
                'status' => 'draft',
                'ai_enhanced' => !empty($ai_prompt) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (isset($_SESSION['user_id'])) {
                $proposalData['created_by'] = $_SESSION['user_id'];
            }
            
            // Teklifi kaydet
            $proposalId = $db->insert('reports', $proposalData);
            
            if ($proposalId) {
                // AI ile teklif oluştur parametresi varsa
                if (isset($_POST['generate_with_ai']) && !empty($ai_prompt)) {
                    setFlashMessage('info', 'Teklif taslağı oluşturuldu. AI ile içerik oluşturuluyor...');
                    redirect(url('index.php?page=reports&action=generate&id=' . $proposalId));
                } else {
                    setFlashMessage('success', 'Teklif başarıyla oluşturuldu.');
                    redirect(url('index.php?page=reports&action=edit&id=' . $proposalId));
                }
            } else {
                $errors[] = 'Teklif oluşturulurken bir hata oluştu.';
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

// Sektör seçenekleri
$industries = [
    'ecommerce' => 'E-ticaret',
    'corporate' => 'Kurumsal',
    'healthcare' => 'Sağlık',
    'education' => 'Eğitim',
    'finance' => 'Finans',
    'tourism' => 'Turizm',
    'restaurant' => 'Restoran/Yemek',
    'realestate' => 'Emlak',
    'automotive' => 'Otomotiv',
    'technology' => 'Teknoloji',
    'consulting' => 'Danışmanlık',
    'manufacturing' => 'Üretim',
    'other' => 'Diğer'
];

// Bütçe aralıkları
$budgetRanges = [
    '5000-10000' => '5.000₺ - 10.000₺',
    '10000-25000' => '10.000₺ - 25.000₺',
    '25000-50000' => '25.000₺ - 50.000₺',
    '50000-100000' => '50.000₺ - 100.000₺',
    '100000+' => '100.000₺+'
];

// Hizmet paketleri
$servicePackages = [
    'basic_seo' => 'Temel SEO Paketi',
    'advanced_seo' => 'Gelişmiş SEO Paketi',
    'enterprise_seo' => 'Kurumsal SEO Paketi',
    'technical_audit' => 'Teknik SEO Denetimi',
    'content_strategy' => 'İçerik Stratejisi',
    'link_building' => 'Link Building',
    'local_seo' => 'Yerel SEO',
    'ecommerce_seo' => 'E-ticaret SEO',
    'penalty_recovery' => 'Ceza Kurtarma'
];

// Teklif bölümleri
$proposalSectionOptions = [
    'executive_summary' => 'Yönetici Özeti',
    'current_analysis' => 'Mevcut Durum Analizi',
    'opportunity_analysis' => 'Fırsat Analizi',
    'competitor_overview' => 'Rakip Değerlendirmesi',
    'strategy_recommendation' => 'Strateji Önerileri',
    'service_packages' => 'Hizmet Paketleri',
    'timeline' => 'Zaman Çizelgesi',
    'investment_roi' => 'Yatırım ve ROI',
    'case_studies' => 'Başarı Hikayeleri',
    'team_introduction' => 'Ekip Tanıtımı',
    'next_steps' => 'Sonraki Adımlar'
];

// Sayfa başlığı
$pageTitle = 'Yeni Teklif Raporu Oluştur';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4><i class="fas fa-file-contract me-2"></i>Teklif Raporu Oluştur</h4>
            <p class="text-muted mb-0">Potansiyel müşteriler için etkileyici SEO teklifleri hazırlayın</p>
        </div>
        <a href="<?= url('index.php?page=reports') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Raporlara Dön
        </a>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Firma Bilgileri -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Potansiyel Müşteri Bilgileri</h6>
                </div>
                <div class="card-body">
                    <form action="<?= url('index.php?page=reports&action=create&type=proposal_report') ?>" method="post" id="proposalReportForm" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">Teklif Başlığı *</label>
                                <input type="text" class="form-control" id="title" name="title" required value="<?= htmlspecialchars($title) ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="prospect_name" class="form-label">Firma/Kişi Adı *</label>
                                <input type="text" class="form-control" id="prospect_name" name="prospect_name" required value="<?= htmlspecialchars($prospect_name) ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="prospect_email" class="form-label">E-posta Adresi</label>
                                <input type="email" class="form-control" id="prospect_email" name="prospect_email" value="<?= htmlspecialchars($prospect_email) ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="website_url" class="form-label">Website URL *</label>
                                <input type="url" class="form-control" id="website_url" name="website_url" required value="<?= htmlspecialchars($website_url) ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="industry" class="form-label">Sektör</label>
                                <select class="form-select" id="industry" name="industry">
                                    <option value="">Sektör Seçin</option>
                                    <?php foreach ($industries as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $industry == $value ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="budget_range" class="form-label">Bütçe Aralığı</label>
                                <select class="form-select" id="budget_range" name="budget_range">
                                    <option value="">Bütçe Seçin</option>
                                    <?php foreach ($budgetRanges as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $budget_range == $value ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="current_challenges" class="form-label">Mevcut SEO Sorunları ve Hedefler</label>
                            <textarea class="form-control" id="current_challenges" name="current_challenges" rows="4" placeholder="Müşterinin karşılaştığı SEO sorunlarını ve hedeflerini açıklayın..."><?= htmlspecialchars($current_challenges) ?></textarea>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Hizmet Paketleri -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Önerilecek Hizmet Paketleri</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($servicePackages as $package => $packageLabel): ?>
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="service_packages[]" value="<?= $package ?>" id="package_<?= $package ?>" 
                                           <?= in_array($package, $service_packages) ? 'checked' : '' ?> form="proposalReportForm">
                                    <label class="form-check-label" for="package_<?= $package ?>">
                                        <?= htmlspecialchars($packageLabel) ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Teklif Bölümleri -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Teklifte Yer Alacak Bölümler</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($proposalSectionOptions as $section => $sectionLabel): ?>
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="proposal_sections[]" value="<?= $section ?>" id="section_<?= $section ?>" 
                                           <?= in_array($section, $proposal_sections) || empty($proposal_sections) ? 'checked' : '' ?> form="proposalReportForm">
                                    <label class="form-check-label" for="section_<?= $section ?>">
                                        <?= htmlspecialchars($sectionLabel) ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="competition_analysis" value="1" id="competition_analysis" 
                                       <?= $competition_analysis ? 'checked' : '' ?> form="proposalReportForm">
                                <label class="form-check-label" for="competition_analysis">
                                    <strong>Rakip Analizi Dahil Et</strong>
                                    <small class="d-block text-muted">En az 3 rakip site analizi</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="quick_wins" value="1" id="quick_wins" 
                                       <?= $quick_wins ? 'checked' : '' ?> form="proposalReportForm">
                                <label class="form-check-label" for="quick_wins">
                                    <strong>Hızlı Kazanımlar Bölümü</strong>
                                    <small class="d-block text-muted">30 gün içinde uygulanabilir öneriler</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- AI Destekli Teklif Optimizasyonu -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-gradient-warning text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-robot me-2"></i>AI Destekli Teklif Optimizasyonu (Grok AI)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="ai-info-box mb-3 p-3 bg-light rounded">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-lightbulb text-warning me-2"></i>
                            <strong>AI Teklif Optimizasyonu</strong>
                        </div>
                        <ul class="mb-0 small">
                            <li>Sektöre özel teklifler ve stratejiler oluşturur</li>
                            <li>ROI hesaplamaları ve gerçekçi hedefler sunar</li>
                            <li>Rakiplerden farklılaşan değer önerileri geliştirir</li>
                            <li>Müşteri odaklı ve ikna edici dil kullanır</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ai_prompt" class="form-label">
                            <i class="fas fa-magic me-1"></i>AI Teklif Geliştirme Talimatları
                        </label>
                        <textarea class="form-control" id="ai_prompt" name="ai_prompt" rows="4" form="proposalReportForm" 
                                  placeholder="Teklifte öne çıkarılmasını istediğiniz hizmetleri ve yaklaşımları belirtin..."><?= htmlspecialchars($ai_prompt) ?></textarea>
                        <div class="form-text">AI bu talimatları kullanarak teklifinizi kişiselleştirecek ve zenginleştirecektir.</div>
                    </div>
                    
                    <!-- AI Öneri Chips -->
                    <div class="mb-3">
                        <label class="form-label">Teklif Vurguları:</label>
                        <div class="ai-suggestions">
                            <button type="button" class="btn btn-sm btn-outline-warning me-2 mb-2" onclick="addToAIPrompt('Hızlı sonuçlar ve 30 günlük kazanımları vurgula')">
                                ⚡ Hızlı Sonuçlar
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning me-2 mb-2" onclick="addToAIPrompt('Detaylı ROI hesaplamaları ve yatırım geri dönüşü ekle')">
                                💰 ROI Odaklı
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning me-2 mb-2" onclick="addToAIPrompt('Başarı hikayeleri ve vaka çalışmaları dahil et')">
                                🏆 Başarı Hikayeleri
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning me-2 mb-2" onclick="addToAIPrompt('Rakiplerden farkımızı ve benzersiz yaklaşımımızı öne çıkar')">
                                🎯 Farklılaşma
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning me-2 mb-2" onclick="addToAIPrompt('Sektöre özel strateji ve deneyim vurgusu')">
                                🏢 Sektör Uzmanlığı
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning me-2 mb-2" onclick="addToAIPrompt('Şeffaf süreç ve düzenli raporlama vurgusu')">
                                📊 Şeffaflık
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Teklif Önizleme -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Teklif Önizleme</h6>
                </div>
                <div class="card-body">
                    <div id="proposalPreview" class="proposal-preview-box">
                        <div class="text-center text-muted">
                            <i class="fas fa-file-contract fa-3x mb-3"></i>
                            <p>Form dolduruldukça teklif önizlemesi burada görünecek</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hızlı Teklif Şablonları -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Teklif Şablonları</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadProposalTemplate('startup')">
                            <i class="fas fa-rocket me-1"></i> Startup Teklifi
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadProposalTemplate('enterprise')">
                            <i class="fas fa-building me-1"></i> Kurumsal Teklif
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadProposalTemplate('ecommerce')">
                            <i class="fas fa-shopping-cart me-1"></i> E-ticaret Teklifi
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadProposalTemplate('local')">
                            <i class="fas fa-map-marker-alt me-1"></i> Yerel İşletme
                        </button>
                    </div>
                    <hr>
                    <small class="text-muted">
                        <i class="fas fa-lightbulb me-1"></i>
                        Şablonlar sektöre uygun hizmet paketleri ve AI talimatlarını otomatik doldurur.
                    </small>
                </div>
            </div>
            
            <!-- Rakip Analizi Araçları -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line me-1"></i>Hızlı Analiz Araçları
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="analyzeWebsite()">
                            <i class="fas fa-search me-1"></i> Site Analizi
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="findCompetitors()">
                            <i class="fas fa-users me-1"></i> Rakip Bul
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="keywordResearch()">
                            <i class="fas fa-key me-1"></i> Anahtar Kelime Araştırma
                        </button>
                    </div>
                    <hr>
                    <div id="analysisResults" class="analysis-results d-none">
                        <div class="small text-muted">Analiz sonuçları burada görünecek</div>
                    </div>
                </div>
            </div>
            
            <!-- Kaydetme Seçenekleri -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Kaydetme Seçenekleri</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" form="proposalReportForm" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Taslak Olarak Kaydet
                        </button>
                        <button type="submit" form="proposalReportForm" name="generate_with_ai" value="1" class="btn btn-warning text-white">
                            <i class="fas fa-robot me-1"></i> AI ile Oluştur
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="previewProposal()">
                            <i class="fas fa-eye me-1"></i> Önizleme
                        </button>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-12 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="autoSend" name="auto_send">
                                <label class="form-check-label small" for="autoSend">
                                    Oluşturulduktan sonra otomatik gönder
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="saveProposalTemplate" name="save_proposal_template">
                                <label class="form-check-label small" for="saveProposalTemplate">
                                    Bu ayarları şablon olarak kaydet
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.proposal-preview-box {
    min-height: 200px;
    border: 2px dashed #e3e6f0;
    border-radius: 0.35rem;
    padding: 1rem;
}

.bg-gradient-warning {
    background: linear-gradient(87deg, #fb6340 0, #fbb140 100%) !important;
}

.ai-suggestions .btn {
    font-size: 0.8rem;
}

.analysis-results {
    background: #f8f9fc;
    border-radius: 0.35rem;
    padding: 0.75rem;
    margin-top: 0.5rem;
}

.form-check-input:checked {
    background-color: #5e72e4;
    border-color: #5e72e4;
}
</style>

<script>
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

// Teklif şablonlarını yükle
function loadProposalTemplate(templateType) {
    const templates = {
        startup: {
            packages: ['basic_seo', 'content_strategy', 'technical_audit'],
            sections: ['executive_summary', 'current_analysis', 'opportunity_analysis', 'strategy_recommendation', 'service_packages', 'timeline', 'investment_roi'],
            aiPrompt: 'Startup için büyüme odaklı, maliyet-etkin SEO stratejisi sunun. Hızlı kazanımları, ölçeklenebilir yaklaşımları ve sınırlı bütçeye uygun çözümleri vurgulayın.',
            quickWins: true,
            competition: true
        },
        enterprise: {
            packages: ['enterprise_seo', 'technical_audit', 'content_strategy', 'link_building'],
            sections: ['executive_summary', 'current_analysis', 'competitor_overview', 'strategy_recommendation', 'service_packages', 'timeline', 'investment_roi', 'team_introduction'],
            aiPrompt: 'Kurumsal müşteri için kapsamlı, uzun vadeli SEO stratejisi hazırlayın. Teknik uzmanlık, süreç yönetimi ve ölçeklenebilir çözümleri öne çıkarın.',
            quickWins: false,
            competition: true
        },
        ecommerce: {
            packages: ['ecommerce_seo', 'technical_audit', 'content_strategy'],
            sections: ['executive_summary', 'current_analysis', 'opportunity_analysis', 'strategy_recommendation', 'service_packages', 'investment_roi'],
            aiPrompt: 'E-ticaret sitesi için dönüşüm odaklı SEO stratejisi sunun. Ürün sayfası optimizasyonu, kategori yapısı ve satış artırıcı SEO yaklaşımlarını vurgulayın.',
            quickWins: true,
            competition: true
        },
        local: {
            packages: ['local_seo', 'basic_seo', 'content_strategy'],
            sections: ['executive_summary', 'current_analysis', 'opportunity_analysis', 'strategy_recommendation', 'service_packages', 'investment_roi'],
            aiPrompt: 'Yerel işletme için yerel SEO odaklı strateji sunun. Google My Business optimizasyonu, yerel arama görünürlüğü ve müşteri edinme odaklı yaklaşımları vurgulayın.',
            quickWins: true,
            competition: false
        }
    };
    
    const template = templates[templateType];
    if (!template) return;
    
    // Hizmet paketlerini seç
    document.querySelectorAll('input[name="service_packages[]"]').forEach(checkbox => {
        checkbox.checked = template.packages.includes(checkbox.value);
    });
    
    // Bölümleri seç
    document.querySelectorAll('input[name="proposal_sections[]"]').forEach(checkbox => {
        checkbox.checked = template.sections.includes(checkbox.value);
    });
    
    // Ekstra seçenekleri ayarla
    document.getElementById('quick_wins').checked = template.quickWins;
    document.getElementById('competition_analysis').checked = template.competition;
    
    // AI prompt'u doldur
    document.getElementById('ai_prompt').value = template.aiPrompt;
    
    // Başlık önerisinde bulun
    const prospectName = document.getElementById('prospect_name').value;
    const titleInput = document.getElementById('title');
    
    if (prospectName) {
        const templateTitles = {
            startup: `${prospectName} - Startup SEO Büyüme Teklifi`,
            enterprise: `${prospectName} - Kurumsal SEO Stratejisi Teklifi`,
            ecommerce: `${prospectName} - E-ticaret SEO Optimizasyon Teklifi`,
            local: `${prospectName} - Yerel SEO Görünürlük Teklifi`
        };
        
        if (!titleInput.value) {
            titleInput.value = templateTitles[templateType];
        }
    }
    
    updateProposalPreview();
    
    // Bilgilendirme mesajı göster
    showSuccessMessage(`${templateType.charAt(0).toUpperCase() + templateType.slice(1)} teklif şablonu yüklendi!`);
}

// Teklif önizlemesini güncelle
function updateProposalPreview() {
    const title = document.getElementById('title').value;
    const prospectName = document.getElementById('prospect_name').value;
    const websiteUrl = document.getElementById('website_url').value;
    const industry = document.getElementById('industry');
    const budgetRange = document.getElementById('budget_range');
    
    const previewBox = document.getElementById('proposalPreview');
    
    if (title || prospectName) {
        let industryText = industry.options[industry.selectedIndex].text;
        let budgetText = budgetRange.options[budgetRange.selectedIndex].text;
        
        previewBox.innerHTML = `
            <div class="preview-header mb-3">
                <h6 class="text-primary">${title || 'Teklif Başlığı'}</h6>
                <small class="text-muted">${prospectName || 'Potansiyel Müşteri'}</small>
            </div>
            <div class="preview-details">
                ${websiteUrl ? `<p class="small mb-1"><strong>Website:</strong> ${websiteUrl}</p>` : ''}
                ${industryText !== 'Sektör Seçin' ? `<p class="small mb-1"><strong>Sektör:</strong> ${industryText}</p>` : ''}
                ${budgetText !== 'Bütçe Seçin' ? `<p class="small mb-1"><strong>Bütçe:</strong> ${budgetText}</p>` : ''}
                <p class="small mb-0"><strong>Hazırlanma:</strong> ${new Date().toLocaleDateString('tr-TR')}</p>
            </div>
        `;
    } else {
        previewBox.innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-file-contract fa-3x mb-3"></i>
                <p>Form dolduruldukça teklif önizlemesi burada görünecek</p>
            </div>
        `;
    }
}

// Website analizi
function analyzeWebsite() {
    const websiteUrl = document.getElementById('website_url').value;
    if (!websiteUrl) {
        alert('Önce website URL\'sini girin.');
        return;
    }
    
    const resultsDiv = document.getElementById('analysisResults');
    resultsDiv.classList.remove('d-none');
    resultsDiv.innerHTML = `
        <div class="d-flex align-items-center mb-2">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
            <strong>Website analiz ediliyor...</strong>
        </div>
    `;
    
    // Simülasyon - gerçek uygulamada API çağrısı yapılır
    setTimeout(() => {
        resultsDiv.innerHTML = `
            <div class="small">
                <div class="d-flex justify-content-between mb-1">
                    <span>Sayfa Hızı:</span>
                    <span class="text-warning">⚠️ Orta (65/100)</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Mobile Uyumluluk:</span>
                    <span class="text-success">✅ İyi</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>SEO Skoru:</span>
                    <span class="text-danger">❌ Zayıf (45/100)</span>
                </div>
                <hr class="my-2">
                <button class="btn btn-sm btn-outline-primary w-100" onclick="addAnalysisToPrompt()">
                    Analizi Teklife Ekle
                </button>
            </div>
        `;
    }, 2000);
}

// Rakip bulma
function findCompetitors() {
    const websiteUrl = document.getElementById('website_url').value;
    const industry = document.getElementById('industry').value;
    
    if (!websiteUrl) {
        alert('Önce website URL\'sini girin.');
        return;
    }
    
    const resultsDiv = document.getElementById('analysisResults');
    resultsDiv.classList.remove('d-none');
    resultsDiv.innerHTML = `
        <div class="d-flex align-items-center mb-2">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
            <strong>Rakipler araştırılıyor...</strong>
        </div>
    `;
    
    setTimeout(() => {
        resultsDiv.innerHTML = `
            <div class="small">
                <strong>Ana Rakipler:</strong>
                <div class="mt-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span>rakip1.com</span>
                        <span class="text-success">🔝 Güçlü</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>rakip2.com</span>
                        <span class="text-warning">📈 Orta</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>rakip3.com</span>
                        <span class="text-info">📊 Benzer</span>
                    </div>
                </div>
                <hr class="my-2">
                <button class="btn btn-sm btn-outline-primary w-100" onclick="addCompetitorsToPrompt()">
                    Rakip Analizini Teklife Ekle
                </button>
            </div>
        `;
    }, 2500);
}

// Anahtar kelime araştırması
function keywordResearch() {
    const websiteUrl = document.getElementById('website_url').value;
    const industry = document.getElementById('industry').value;
    
    if (!websiteUrl) {
        alert('Önce website URL\'sini girin.');
        return;
    }
    
    const resultsDiv = document.getElementById('analysisResults');
    resultsDiv.classList.remove('d-none');
    resultsDiv.innerHTML = `
        <div class="d-flex align-items-center mb-2">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
            <strong>Anahtar kelimeler araştırılıyor...</strong>
        </div>
    `;
    
    setTimeout(() => {
        resultsDiv.innerHTML = `
            <div class="small">
                <strong>Potansiyel Anahtar Kelimeler:</strong>
                <div class="mt-2">
                    <span class="badge bg-primary me-1 mb-1">seo hizmeti</span>
                    <span class="badge bg-primary me-1 mb-1">dijital pazarlama</span>
                    <span class="badge bg-primary me-1 mb-1">web tasarım</span>
                    <span class="badge bg-secondary me-1 mb-1">arama motoru</span>
                </div>
                <hr class="my-2">
                <button class="btn btn-sm btn-outline-primary w-100" onclick="addKeywordsToPrompt()">
                    Anahtar Kelimeleri Teklife Ekle
                </button>
            </div>
        `;
    }, 1800);
}

// Analiz sonuçlarını AI prompt'a ekle
function addAnalysisToPrompt() {
    addToAIPrompt('Mevcut site analizi: Sayfa hızı optimizasyonu gerekli (65/100), Mobile uyumlu, SEO skoru düşük (45/100). Bu sorunlara odaklı çözüm önerileri sun.');
}

function addCompetitorsToPrompt() {
    addToAIPrompt('Ana rakipler: rakip1.com (güçlü), rakip2.com (orta), rakip3.com (benzer seviye). Rakiplerden farklılaştıracak stratejiler geliştir.');
}

function addKeywordsToPrompt() {
    addToAIPrompt('Hedef anahtar kelimeler: seo hizmeti, dijital pazarlama, web tasarım, arama motoru. Bu kelimeler için rekabet analizi ve strateji önerisi sun.');
}

// Teklif önizlemesi
function previewProposal() {
    const form = document.getElementById('proposalReportForm');
    const formData = new FormData(form);
    
    // Yeni pencerede önizleme aç
    const previewWindow = window.open('', '_blank', 'width=900,height=700,scrollbars=yes');
    previewWindow.document.write(`
        <html>
        <head>
            <title>Teklif Önizleme</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
                .proposal-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 2rem; margin-bottom: 2rem; }
                .section-title { color: #667eea; font-weight: 600; margin-top: 2rem; margin-bottom: 1rem; }
            </style>
        </head>
        <body>
            <div class="proposal-header text-center">
                <h2>${formData.get('title') || 'SEO Teklif Raporu'}</h2>
                <p class="mb-0">Hazırlayan: SEO Ajansınız | Tarih: ${new Date().toLocaleDateString('tr-TR')}</p>
            </div>
            <div class="container">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="section-title">Müşteri Bilgileri</h4>
                        <p><strong>Firma:</strong> ${formData.get('prospect_name') || 'Belirtilmemiş'}</p>
                        <p><strong>Website:</strong> ${formData.get('website_url') || 'Belirtilmemiş'}</p>
                        
                        <h4 class="section-title">Önerilen Hizmetler</h4>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Bu bir önizlemedir. Tam teklif içeriği AI tarafından oluşturulacaktır.
                        </div>
                        
                        <h4 class="section-title">Mevcut Durum Analizi</h4>
                        <p>Website analizi ve SEO denetimi sonucunda belirlenen iyileştirme alanları...</p>
                        
                        <h4 class="section-title">Önerilen Strateji</h4>
                        <p>Size özel geliştirilen SEO stratejisi ve uygulama planı...</p>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6>Teklif Özeti</h6>
                            </div>
                            <div class="card-body">
                                <p class="small">AI tarafından oluşturulacak kapsamlı teklif içeriği burada yer alacaktır.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
    `);
}

// Başarı mesajı göster
function showSuccessMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        <strong>${message}</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);
}

// Form alanları değiştiğinde önizlemeyi güncelle
document.getElementById('title').addEventListener('input', updateProposalPreview);
document.getElementById('prospect_name').addEventListener('input', updateProposalPreview);
document.getElementById('website_url').addEventListener('input', updateProposalPreview);
document.getElementById('industry').addEventListener('change', updateProposalPreview);
document.getElementById('budget_range').addEventListener('change', updateProposalPreview);

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    updateProposalPreview();
    
    // Form validation
    const form = document.getElementById('proposalReportForm');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
    
    // Prospect name değiştiğinde title önerisinde bulun
    document.getElementById('prospect_name').addEventListener('blur', function() {
        const titleInput = document.getElementById('title');
        if (!titleInput.value && this.value) {
            titleInput.value = `${this.value} - SEO Optimizasyon Teklifi`;
            updateProposalPreview();
        }
    });
});
</script>