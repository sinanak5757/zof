<?php
// reports/generate.php - AI ile Rapor Oluşturma Sayfası

// Rapor ID'sini kontrol et
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('warning', 'Oluşturulacak rapor ID belirtilmedi');
    redirect(url('index.php?page=reports'));
}

$reportId = intval($_GET['id']);

// Rapor verilerini al
try {
    $report = $db->getRow("
        SELECT r.*, c.client_name, c.website_url as client_website
        FROM reports r
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?", 
        [$reportId]
    );
    
    if (!$report) {
        setFlashMessage('danger', 'Rapor bulunamadı');
        redirect(url('index.php?page=reports'));
    }
} catch (Exception $e) {
    setFlashMessage('danger', 'Veritabanı hatası: ' . $e->getMessage());
    redirect(url('index.php?page=reports'));
}

// Grok AI API anahtarı kontrolü (settings tablosundan alınabilir)
$grokApiKey = '';
try {
    $apiKeySetting = $db->getRow("SELECT setting_value FROM settings WHERE setting_name = 'grok_api_key'");
    $grokApiKey = $apiKeySetting ? $apiKeySetting['setting_value'] : '';
} catch (Exception $e) {
    // API anahtarı ayarı yoksa devam et
}

// AJAX isteği ise AI ile rapor oluştur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_ai_content') {
    header('Content-Type: application/json');
    
    try {
        // AI prompt'u hazırla
        $aiPrompt = prepareAIPrompt($report);
        
        // Grok AI API çağrısı yap
        $generatedContent = callGrokAI($aiPrompt, $grokApiKey);
        
        if ($generatedContent) {
            // Oluşturulan içeriği veritabanına kaydet
            $updateData = [
                'ai_generated_content' => $generatedContent,
                'status' => 'completed',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $updated = $db->update('reports', $updateData, 'id = ?', [$reportId]);
            
            if ($updated) {
                echo json_encode([
                    'success' => true,
                    'content' => $generatedContent,
                    'message' => 'Rapor başarıyla oluşturuldu!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Rapor kaydedilirken hata oluştu.'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'AI içerik oluşturulamadı. Lütfen tekrar deneyin.'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Hata: ' . $e->getMessage()
        ]);
    }
    
    exit;
}

// AI Prompt hazırlama fonksiyonu
function prepareAIPrompt($report) {
    $prompt = "";
    
    if ($report['report_type'] === 'client_report') {
        $prompt = "Profesyonel bir SEO performans raporu oluştur. ";
        $prompt .= "Müşteri: " . ($report['client_name'] ?? 'Müşteri') . "\n";
        $prompt .= "Website: " . ($report['website_url'] ?? '') . "\n";
        $prompt .= "Rapor Dönemi: " . ($report['report_period'] ?? 'Aylık') . "\n";
        
        if (!empty($report['keywords'])) {
            $prompt .= "Ana Anahtar Kelimeler: " . $report['keywords'] . "\n";
        }
        
        if (!empty($report['project_goals'])) {
            $prompt .= "Proje Hedefleri: " . $report['project_goals'] . "\n";
        }
        
        $prompt .= "\nRaporda şu bölümleri dahil et:\n";
        $prompt .= "1. Yönetici Özeti\n";
        $prompt .= "2. SEO Performans Metrikleri\n";
        $prompt .= "3. Anahtar Kelime Analizi\n";
        $prompt .= "4. Teknik SEO Durumu\n";
        $prompt .= "5. İçerik Performansı\n";
        $prompt .= "6. Öneriler ve Gelecek Dönem Planı\n";
        
    } else if ($report['report_type'] === 'proposal_report') {
        $prompt = "Etkili bir SEO teklif raporu oluştur. ";
        $prompt .= "Potansiyel Müşteri: " . ($report['prospect_name'] ?? 'Firma') . "\n";
        $prompt .= "Website: " . ($report['website_url'] ?? '') . "\n";
        $prompt .= "Sektör: " . ($report['industry'] ?? '') . "\n";
        $prompt .= "Bütçe Aralığı: " . ($report['budget_range'] ?? '') . "\n";
        
        if (!empty($report['current_challenges'])) {
            $prompt .= "Mevcut Sorunlar: " . $report['current_challenges'] . "\n";
        }
        
        $prompt .= "\nTeklifte şu bölümleri dahil et:\n";
        $prompt .= "1. Yönetici Özeti\n";
        $prompt .= "2. Mevcut Durum Analizi\n";
        $prompt .= "3. Fırsat Analizi\n";
        $prompt .= "4. Önerilen Strateji\n";
        $prompt .= "5. Hizmet Paketleri ve Fiyatlandırma\n";
        $prompt .= "6. Beklenen Sonuçlar ve ROI\n";
        $prompt .= "7. Zaman Çizelgesi\n";
        $prompt .= "8. Sonraki Adımlar\n";
    }
    
    // Kullanıcının özel AI talimatları varsa ekle
    if (!empty($report['ai_prompt'])) {
        $prompt .= "\n\nÖzel Talimatlar: " . $report['ai_prompt'];
    }
    
    $prompt .= "\n\nRaporu Türkçe, profesyonel ve anlaşılır bir dille hazırla. ";
    $prompt .= "Veriler gerçekçi örnek veriler olsun. ";
    $prompt .= "HTML formatında döndür ve başlıklar için h3, h4 etiketlerini kullan.";
    
    return $prompt;
}

// Grok AI API çağrısı
function callGrokAI($prompt, $apiKey) {
    // Bu fonksiyon gerçek Grok AI API entegrasyonu için hazırlanmıştır
    // Şu an simülasyon olarak örnek içerik döndürür
    
    if (empty($apiKey)) {
        // API anahtarı yoksa örnek içerik döndür
        return generateSampleContent($prompt);
    }
    
    // Gerçek API çağrısı burada yapılacak
    /*
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.x.ai/v1/chat/completions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'model' => 'grok-beta',
            'stream' => false
        ])
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }
    */
    
    // Şimdilik örnek içerik döndür
    return generateSampleContent($prompt);
}

// Örnek içerik oluşturma (API yokken kullanılır)
function generateSampleContent($prompt) {
    // Prompt tipine göre örnek içerik oluştur
    if (strpos($prompt, 'client_report') !== false || strpos($prompt, 'performans raporu') !== false) {
        return generateSampleClientReport();
    } else {
        return generateSampleProposalReport();
    }
}

function generateSampleClientReport() {
    return '
    <h3>📊 Yönetici Özeti</h3>
    <p>Bu ay SEO performansınızda önemli ilerlemeler kaydettik. Organik trafik %25 artış gösterirken, hedeflenen anahtar kelimelerde ortalama %30 sıralama yükselişi sağlandı.</p>
    
    <h3>📈 SEO Performans Metrikleri</h3>
    <h4>Organik Trafik</h4>
    <ul>
        <li>Bu Ay: 15,672 ziyaret (Önceki Ay: 12,450)</li>
        <li>Artış Oranı: %25.9</li>
        <li>Yeni Kullanıcı Oranı: %68</li>
    </ul>
    
    <h4>Anahtar Kelime Performansı</h4>
    <ul>
        <li>İlk 3 sırada: 23 anahtar kelime (+8)</li>
        <li>İlk 10 sırada: 156 anahtar kelime (+47)</li>
        <li>Toplam sıralanan: 2,341 anahtar kelime</li>
    </ul>
    
    <h3>🔧 Teknik SEO Durumu</h3>
    <p>Site hızı optimizasyonları tamamlandı. Core Web Vitals skorlarında %40 iyileşme sağlandı.</p>
    
    <h3>📝 İçerik Performansı</h3>
    <p>Bu ay yayınlanan 8 blog yazısı toplam 3,240 organik trafik getirdi. En başarılı içerik: "SEO Stratejileri 2024" başlıklı yazı.</p>
    
    <h3>🚀 Öneriler ve Gelecek Dönem Planı</h3>
    <ul>
        <li>E-ticaret kategorileri için uzun kuyruk anahtar kelime optimizasyonu</li>
        <li>Sosyal medya entegrasyonu ile içerik paylaşım oranını artırma</li>
        <li>Mobile kullanıcı deneyimi iyileştirmeleri</li>
    </ul>';
}

function generateSampleProposalReport() {
    return '
    <h3>🎯 Yönetici Özeti</h3>
    <p>Website analiz sonuçlarına göre, SEO optimizasyonu ile 6 ay içinde organik trafiğinizi %150-200 oranında artırabiliriz. Teknik sorunların giderilmesi ve içerik stratejisinin uygulanması ile güçlü bir dijital varlık oluşturacağız.</p>
    
    <h3>🔍 Mevcut Durum Analizi</h3>
    <h4>Tespit Edilen Sorunlar</h4>
    <ul>
        <li>Sayfa hızı optimizasyonu gerekli (mevcut skor: 45/100)</li>
        <li>Meta etiketleri eksik veya optimize edilmemiş</li>
        <li>İç link yapısı zayıf</li>
        <li>Mobil uyumluluğta iyileştirme gereken alanlar</li>
    </ul>
    
    <h3>💰 Fırsat Analizi</h3>
    <p>Rakip analizi sonucunda, sektörünüzde aylık 45,000 arama yapıldığını tespit ettik. Bu trafikten %15-20 pay alarak önemli bir büyüme sağlayabilirsiniz.</p>
    
    <h3>🚀 Önerilen Strateji</h3>
    <h4>3 Aşamalı SEO Yaklaşımı</h4>
    <ol>
        <li><strong>Teknik Temellerin Atılması (1-2. Ay)</strong></li>
        <li><strong>İçerik Stratejisi ve Optimizasyon (2-4. Ay)</strong></li>
        <li><strong>Otorite Kazanımı ve Büyüme (4-6. Ay)</strong></li>
    </ol>
    
    <h3>📦 Hizmet Paketleri</h3>
    <h4>Önerilen Paket: Gelişmiş SEO</h4>
    <ul>
        <li>Teknik SEO denetimi ve optimizasyonu</li>
        <li>Anahtar kelime araştırması ve strateji</li>
        <li>İçerik planlaması ve optimizasyonu</li>
        <li>Aylık performans raporları</li>
    </ul>
    
    <h3>📊 Beklenen Sonuçlar</h3>
    <p><strong>6 ay sonunda hedeflenen metrikler:</strong></p>
    <ul>
        <li>Organik trafik artışı: %150-200</li>
        <li>İlk sayfa sıralaması: 25-30 anahtar kelime</li>
        <li>Dönüşüm oranı artışı: %40-60</li>
    </ul>
    
    <h3>📅 Sonraki Adımlar</h3>
    <ol>
        <li>Proje başlangıç toplantısı</li>
        <li>Detaylı site denetimi</li>
        <li>Stratejik plan sunumu</li>
        <li>Uygulama sürecinin başlatılması</li>
    </ol>';
}

// Sayfa başlığı
$pageTitle = 'AI ile Rapor Oluştur: ' . htmlspecialchars($report['title']);
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4><i class="fas fa-robot me-2"></i>AI ile Rapor Oluşturuluyor</h4>
            <p class="text-muted mb-0"><?= htmlspecialchars($report['title']) ?></p>
        </div>
        <div>
            <a href="<?= url('index.php?page=reports&action=edit&id=' . $reportId) ?>" class="btn btn-outline-secondary me-2">
                <i class="fas fa-edit me-1"></i> Manuel Düzenle
            </a>
            <a href="<?= url('index.php?page=reports') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Raporlara Dön
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- AI Oluşturma Kartı -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-gradient-primary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-magic me-2"></i>Grok AI ile İçerik Oluşturma
                    </h6>
                </div>
                <div class="card-body">
                    <div id="generationStatus" class="generation-status">
                        <div class="status-step active" id="step1">
                            <div class="step-icon">
                                <i class="fas fa-cog fa-spin"></i>
                            </div>
                            <div class="step-content">
                                <h6>Rapor Verilerini Hazırlıyor</h6>
                                <p class="mb-0">Müşteri bilgileri ve rapor parametreleri analiz ediliyor...</p>
                            </div>
                        </div>
                        
                        <div class="status-step" id="step2">
                            <div class="step-icon">
                                <i class="fas fa-brain"></i>
                            </div>
                            <div class="step-content">
                                <h6>AI Analiz Yapıyor</h6>
                                <p class="mb-0">Grok AI verileri işliyor ve içgörüler oluşturuyor...</p>
                            </div>
                        </div>
                        
                        <div class="status-step" id="step3">
                            <div class="step-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="step-content">
                                <h6>Rapor İçeriği Oluşturuluyor</h6>
                                <p class="mb-0">Profesyonel rapor formatında içerik hazırlanıyor...</p>
                            </div>
                        </div>
                        
                        <div class="status-step" id="step4">
                            <div class="step-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="step-content">
                                <h6>Tamamlandı</h6>
                                <p class="mb-0">Rapor başarıyla oluşturuldu ve kaydedildi!</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="progress mb-4" style="height: 6px;">
                        <div class="progress-bar bg-primary" id="progressBar" role="progressbar" style="width: 25%"></div>
                    </div>
                    
                    <!-- Başlatma Butonu -->
                    <div class="text-center" id="startSection">
                        <button type="button" class="btn btn-primary btn-lg" onclick="startAIGeneration()">
                            <i class="fas fa-robot me-2"></i>AI ile Rapor Oluşturmaya Başla
                        </button>
                        <p class="text-muted mt-2 mb-0">Bu işlem 1-3 dakika sürebilir</p>
                    </div>
                    
                    <!-- Hata Mesajı -->
                    <div class="alert alert-danger d-none" id="errorAlert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Hata:</strong> <span id="errorMessage"></span>
                        <hr>
                        <button class="btn btn-outline-danger btn-sm" onclick="retryGeneration()">
                            <i class="fas fa-redo me-1"></i> Tekrar Dene
                        </button>
                    </div>
                    
                    <!-- Başarı Mesajı -->
                    <div class="alert alert-success d-none" id="successAlert">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Başarılı!</strong> Rapor AI tarafından oluşturuldu.
                        <hr>
                        <div class="btn-group">
                            <a href="<?= url('index.php?page=reports&action=view&id=' . $reportId) ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-eye me-1"></i> Raporu Görüntüle
                            </a>
                            <a href="<?= url('index.php?page=reports&action=edit&id=' . $reportId) ?>" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-edit me-1"></i> Düzenle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Oluşturulan İçerik Önizleme -->
            <div class="card shadow mb-4 d-none" id="contentPreview">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Oluşturulan İçerik Önizleme</h6>
                </div>
                <div class="card-body">
                    <div id="generatedContent" class="generated-content">
                        <!-- AI tarafından oluşturulan içerik buraya gelecek -->
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button class="btn btn-outline-primary btn-sm me-2" onclick="regenerateContent()">
                                <i class="fas fa-redo me-1"></i> Yeniden Oluştur
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="editContent()">
                                <i class="fas fa-edit me-1"></i> Manuel Düzenle
                            </button>
                        </div>
                        <div>
                            <button class="btn btn-success" onclick="approveContent()">
                                <i class="fas fa-check me-1"></i> Onayla ve Kaydet
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Rapor Bilgileri -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Rapor Bilgileri</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm">
                            <tbody>
                                <tr>
                                    <th width="40%">Rapor Türü:</th>
                                    <td>
                                        <?php
                                        $reportTypes = [
                                            'client_report' => 'Müşteri Raporu',
                                            'proposal_report' => 'Teklif Raporu'
                                        ];
                                        echo $reportTypes[$report['report_type']] ?? $report['report_type'];
                                        ?>
                                    </td>
                                </tr>
                                <?php if ($report['report_type'] === 'client_report' && !empty($report['client_name'])): ?>
                                <tr>
                                    <th>Müşteri:</th>
                                    <td><?= htmlspecialchars($report['client_name']) ?></td>
                                </tr>
                                <?php elseif ($report['report_type'] === 'proposal_report' && !empty($report['prospect_name'])): ?>
                                <tr>
                                    <th>Potansiyel Müşteri:</th>
                                    <td><?= htmlspecialchars($report['prospect_name']) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Website:</th>
                                    <td>
                                        <?php if (!empty($report['website_url'])): ?>
                                            <a href="<?= htmlspecialchars($report['website_url']) ?>" target="_blank" class="text-decoration-none">
                                                <?= htmlspecialchars($report['website_url']) ?>
                                                <i class="fas fa-external-link-alt ms-1 small"></i>
                                            </a>
                                        <?php else: ?>
                                            <em class="text-muted">Belirtilmemiş</em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Oluşturulma:</th>
                                    <td><?= date('d.m.Y H:i', strtotime($report['created_at'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Durum:</th>
                                    <td>
                                        <span class="badge bg-warning text-dark">Oluşturuluyor</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- AI Talimatları -->
            <?php if (!empty($report['ai_prompt'])): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-magic me-1"></i>AI Talimatları
                    </h6>
                </div>
                <div class="card-body">
                    <div class="ai-prompt-box">
                        <?= nl2br(htmlspecialchars($report['ai_prompt'])) ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- İpuçları -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-lightbulb me-1"></i>AI Oluşturma İpuçları
                    </h6>
                </div>
                <div class="card-body">
                    <div class="tips-list">
                        <div class="tip-item mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <small>AI, verdiğiniz talimatları dikkate alarak raporu kişiselleştirir</small>
                        </div>
                        <div class="tip-item mb-3">
                            <i class="fas fa-clock text-info me-2"></i>
                            <small>Oluşturma süreci 1-3 dakika arasında değişebilir</small>
                        </div>
                        <div class="tip-item mb-3">
                            <i class="fas fa-edit text-warning me-2"></i>
                            <small>Oluşturulan içeriği istediğiniz zaman düzenleyebilirsiniz</small>
                        </div>
                        <div class="tip-item mb-0">
                            <i class="fas fa-redo text-primary me-2"></i>
                            <small>Sonuçtan memnun değilseniz yeniden oluşturabilirsiniz</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Teknik Detaylar -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cog me-1"></i>Teknik Detaylar
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small text-muted">
                        <p class="mb-2"><strong>AI Modeli:</strong> Grok AI (Beta)</p>
                        <p class="mb-2"><strong>Dil:</strong> Türkçe</p>
                        <p class="mb-2"><strong>Çıktı Formatı:</strong> HTML</p>
                        <p class="mb-0"><strong>AI Geliştirme:</strong> 
                            <?= $report['ai_enhanced'] ? '<span class="text-success">Aktif</span>' : '<span class="text-muted">Pasif</span>' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.generation-status {
    padding: 1.5rem 0;
}

.status-step {
    display: flex;
    align-items-center;
    margin-bottom: 1.5rem;
    opacity: 0.4;
    transition: all 0.3s ease;
}

.status-step.active {
    opacity: 1;
}

.status-step.completed {
    opacity: 1;
}

.step-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    transition: all 0.3s ease;
}

.status-step.active .step-icon {
    background: #007bff;
    color: white;
}

.status-step.completed .step-icon {
    background: #28a745;
    color: white;
}

.step-content h6 {
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.step-content p {
    color: #6c757d;
    font-size: 0.9rem;
}

.bg-gradient-primary {
    background: linear-gradient(87deg, #5e72e4 0, #825ee4 100%) !important;
}

.ai-prompt-box {
    background: #f8f9fc;
    border-left: 4px solid #5e72e4;
    padding: 1rem;
    border-radius: 0.375rem;
    font-size: 0.9rem;
    line-height: 1.6;
}

.generated-content {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #e3e6f0;
    border-radius: 0.375rem;
    padding: 1rem;
    background: #fff;
}

.tips-list .tip-item {
    display: flex;
    align-items: flex-start;
}

.progress {
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.5s ease;
}
</style>

<script>
let currentStep = 1;
let generationInProgress = false;

// AI oluşturma sürecini başlat
function startAIGeneration() {
    if (generationInProgress) return;
    
    generationInProgress = true;
    document.getElementById('startSection').style.display = 'none';
    
    // Adımları sırayla çalıştır
    setTimeout(() => activateStep(2), 1000);
    setTimeout(() => activateStep(3), 3000);
    setTimeout(() => generateAIContent(), 5000);
}

// Adımı aktifleştir
function activateStep(stepNumber) {
    // Önceki adımı tamamlandı olarak işaretle
    if (stepNumber > 1) {
        const prevStep = document.getElementById(`step${stepNumber - 1}`);
        prevStep.classList.remove('active');
        prevStep.classList.add('completed');
        prevStep.querySelector('.fas').className = 'fas fa-check';
    }
    
    // Yeni adımı aktifleştir
    const currentStepEl = document.getElementById(`step${stepNumber}`);
    currentStepEl.classList.add('active');
    
    // Progress bar güncelle
    const progress = (stepNumber / 4) * 100;
    document.getElementById('progressBar').style.width = progress + '%';
    
    currentStep = stepNumber;
}

// AI içerik oluşturma API çağrısı
function generateAIContent() {
    fetch('<?= url("index.php?page=reports&action=generate&id=" . $reportId) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=generate_ai_content'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            activateStep(4);
            showGeneratedContent(data.content);
            document.getElementById('successAlert').classList.remove('d-none');
        } else {
            showError(data.message || 'Bilinmeyen bir hata oluştu');
        }
        generationInProgress = false;
    })
    .catch(error => {
        showError('Ağ hatası: ' + error.message);
        generationInProgress = false;
    });
}

// Oluşturulan içeriği göster
function showGeneratedContent(content) {
    document.getElementById('generatedContent').innerHTML = content;
    document.getElementById('contentPreview').classList.remove('d-none');
}

// Hata göster
function showError(message) {
    document.getElementById('errorMessage').textContent = message;
    document.getElementById('errorAlert').classList.remove('d-none');
    
    // Adımları sıfırla
    document.querySelectorAll('.status-step').forEach((step, index) => {
        step.classList.remove('active', 'completed');
        if (index === 0) {
            step.classList.add('active');
        }
        const icon = step.querySelector('.fas');
        if (index === 0) {
            icon.className = 'fas fa-cog fa-spin';
        } else {
            icon.className = ['fas fa-brain', 'fas fa-file-alt', 'fas fa-check'][index];
        }
    });
    
    document.getElementById('progressBar').style.width = '25%';
    document.getElementById('startSection').style.display = 'block';
    currentStep = 1;
}

// Tekrar deneme
function retryGeneration() {
    document.getElementById('errorAlert').classList.add('d-none');
    document.getElementById('contentPreview').classList.add('d-none');
    document.getElementById('successAlert').classList.add('d-none');
    startAIGeneration();
}

// İçeriği yeniden oluştur
function regenerateContent() {
    if (confirm('Mevcut içerik silinecek ve yeniden oluşturulacak. Devam etmek istiyor musunuz?')) {
        document.getElementById('contentPreview').classList.add('d-none');
        document.getElementById('successAlert').classList.add('d-none');
        
        // Adımları sıfırla
        document.querySelectorAll('.status-step').forEach(step => {
            step.classList.remove('active', 'completed');
        });
        document.getElementById('step1').classList.add('active');
        document.getElementById('progressBar').style.width = '25%';
        
        setTimeout(() => startAIGeneration(), 500);
    }
}

// İçeriği manuel düzenle
function editContent() {
    window.location.href = '<?= url("index.php?page=reports&action=edit&id=" . $reportId) ?>';
}

// İçeriği onayla
function approveContent() {
    window.location.href = '<?= url("index.php?page=reports&action=view&id=" . $reportId) ?>';
}

// Sayfa yüklendiğinde otomatik başlat (isteğe bağlı)
document.addEventListener('DOMContentLoaded', function() {
    // URL parametresinde auto=1 varsa otomatik başlat
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('auto') === '1') {
        setTimeout(startAIGeneration, 1000);
    }
});
</script>