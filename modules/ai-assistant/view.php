<?php
// AI öneri detay sayfası

// Öneri ID'si
$suggestionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($suggestionId <= 0) {
    setFlashMessage('danger', 'Geçersiz öneri ID\'si.');
    redirect(url('index.php?page=ai-assistant'));
}

// Öneri bilgilerini al
$suggestion = $db->getRow("
    SELECT ai.*, p.project_name, c.company_name
    FROM ai_suggestions ai
    JOIN projects p ON ai.project_id = p.id
    JOIN clients c ON p.client_id = c.id
    WHERE ai.id = ?
", [$suggestionId]);

if (!$suggestion) {
    setFlashMessage('danger', 'Öneri bulunamadı.');
    redirect(url('index.php?page=ai-assistant'));
}

// İstek ve yanıt verilerini çöz
$requestData = json_decode($suggestion['request_data'], true);
$responseData = json_decode($suggestion['response_data'], true);

// Öneri türüne göre görüntüleme
$suggestionTypes = [
    'content_optimization' => 'İçerik Optimizasyonu',
    'keyword_recommendation' => 'Anahtar Kelime Önerisi',
    'technical_fix' => 'Teknik SEO Çözümü',
    'strategy' => 'Strateji Önerisi',
    'other' => 'Diğer'
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>
        <i class="bi bi-robot me-2"></i> 
        <?php echo $suggestionTypes[$suggestion['request_type']] ?? 'AI Önerisi'; ?>
    </h4>
    <div>
        <?php if (!$suggestion['applied']): ?>
            <a href="<?php echo url('index.php?page=ai-assistant&action=apply&id=' . $suggestionId . '&csrf=' . generateCsrfToken()); ?>" class="btn btn-success me-2">
                <i class="bi bi-check-lg me-1"></i> Uygula
            </a>
        <?php endif; ?>
        <a href="<?php echo url('index.php?page=ai-assistant'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Geri
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Öneri Bilgileri</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="35%">Proje:</th>
                        <td><?php echo sanitize($suggestion['project_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Müşteri:</th>
                        <td><?php echo sanitize($suggestion['company_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Öneri Türü:</th>
                        <td><?php echo $suggestionTypes[$suggestion['request_type']] ?? 'Bilinmeyen'; ?></td>
                    </tr>
                    <tr>
                        <th>Oluşturulma Tarihi:</th>
                        <td><?php echo formatDate($suggestion['created_at']); ?></td>
                    </tr>
                    <tr>
                        <th>Durum:</th>
                        <td>
                            <?php if ($suggestion['applied']): ?>
                                <span class="badge badge-subtle-success">Uygulandı</span>
                            <?php else: ?>
                                <span class="badge badge-subtle-warning">Bekliyor</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">İstek Parametreleri</h5>
            </div>
            <div class="card-body">
                <?php if ($suggestion['request_type'] === 'content_optimization'): ?>
                    <p><strong>İçerik Türü:</strong> 
                        <?php 
                        $contentTypes = [
                            'blog_post' => 'Blog Yazısı',
                            'landing_page' => 'Landing Page',
                            'product_description' => 'Ürün Açıklaması',
                            'category_page' => 'Kategori Sayfası',
                            'service_page' => 'Hizmet Sayfası',
                            'other' => 'Diğer'
                        ];
                        echo $contentTypes[$requestData['content_type']] ?? $requestData['content_type']; 
                        ?>
                    </p>
                    <p><strong>Hedef Anahtar Kelimeler:</strong> <?php echo sanitize($requestData['keywords']); ?></p>
                    <div class="mt-3">
                        <p class="mb-2"><strong>İçerik:</strong></p>
                        <div class="border p-3 rounded bg-light small" style="max-height: 200px; overflow-y: auto;">
                            <?php echo nl2br(sanitize(substr($requestData['content'], 0, 500) . (strlen($requestData['content']) > 500 ? '...' : ''))); ?>
                        </div>
                    </div>
                <?php elseif ($suggestion['request_type'] === 'keyword_recommendation'): ?>
                    <p><strong>Konu:</strong> <?php echo sanitize($requestData['topic']); ?></p>
                    <p><strong>Sektör:</strong> <?php echo sanitize($requestData['industry']); ?></p>
                    <p><strong>İstenen Öneri Sayısı:</strong> <?php echo intval($requestData['count'] ?? 20); ?></p>
                <?php elseif ($suggestion['request_type'] === 'technical_fix'): ?>
                    <p><strong>Sorun Türü:</strong> <?php echo sanitize($requestData['issue_type']); ?></p>
                    <p><strong>Sayfa URL:</strong> <?php echo sanitize($requestData['page_url']); ?></p>
                    <div class="mt-3">
                        <p class="mb-2"><strong>Sorun Açıklaması:</strong></p>
                        <div class="border p-3 rounded bg-light small">
                            <?php echo nl2br(sanitize($requestData['issue_description'])); ?>
                        </div>
                    </div>
                <?php elseif ($suggestion['request_type'] === 'competitor_analysis'): ?>
                    <p><strong>Web Sitesi:</strong> <?php echo sanitize($requestData['website_url']); ?></p>
                    <p><strong>Odak Anahtar Kelimeler:</strong> <?php echo sanitize($requestData['focus_keywords']); ?></p>
                    <div class="mt-3">
                        <p class="mb-2"><strong>Rakip Web Siteleri:</strong></p>
                        <div class="border p-3 rounded bg-light small">
                            <ul class="mb-0">
                                <?php foreach ($requestData['competitor_urls'] as $url): ?>
                                    <li><?php echo sanitize($url); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <pre class="mb-0"><?php print_r($requestData); ?></pre>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- AI Analiz Sonuçları -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">AI Analiz Sonuçları</h5>
    </div>
    <div class="card-body">
        <?php if ($suggestion['request_type'] === 'content_optimization'): ?>
            <div class="analysis-content">
                <?php echo nl2br(sanitize($responseData['analysis'])); ?>
            </div>
        <?php elseif ($suggestion['request_type'] === 'keyword_recommendation'): ?>
            <?php if (isset($responseData['suggestions']) && is_array($responseData['suggestions'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Anahtar Kelime</th>
                                <th>Kullanıcı Niyeti</th>
                                <th>Rekabet Seviyesi</th>
                                <th>Önerilen İçerik Türü</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($responseData['suggestions'] as $keyword): ?>
                                <tr>
                                    <td><?php echo sanitize($keyword['keyword']); ?></td>
                                    <td><?php echo sanitize($keyword['search_intent']); ?></td>
                                    <td>
                                        <?php 
                                            $competitionLevel = $keyword['competition_level'] ?? '';
                                            $badgeClass = '';
                                            
                                            if ($competitionLevel === 'düşük') {
                                                $badgeClass = 'badge-subtle-success';
                                            } elseif ($competitionLevel === 'orta') {
                                                $badgeClass = 'badge-subtle-warning';
                                            } elseif ($competitionLevel === 'yüksek') {
                                                $badgeClass = 'badge-subtle-danger';
                                            }
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo sanitize($competitionLevel); ?></span>
                                    </td>
                                    <td><?php echo sanitize($keyword['suggested_content_type']); ?></td>
                                    <td>
                                        <a href="<?php echo url('index.php?page=keywords&action=add&project_id=' . $suggestion['project_id'] . '&keyword=' . urlencode($keyword['keyword'])); ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-plus-circle me-1"></i> Ekle
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i> Anahtar kelime önerileri alınamadı veya hatalı format.
                </div>
            <?php endif; ?>
        <?php elseif ($suggestion['request_type'] === 'technical_fix'): ?>
            <div class="analysis-content">
                <?php echo nl2br(sanitize($responseData['solution'])); ?>
            </div>
        <?php elseif ($suggestion['request_type'] === 'competitor_analysis'): ?>
            <div class="analysis-content">
                <?php echo nl2br(sanitize($responseData['analysis'])); ?>
            </div>
        <?php else: ?>
            <pre class="mb-0"><?php print_r($responseData); ?></pre>
        <?php endif; ?>
    </div>
</div>