<?php
// AI Asistan ana sayfası

// Her bir AI asistan özelliği için kart bilgilerini tanımla
$aiFeatures = [
    [
        'id' => 'content-optimization',
        'title' => 'İçerik Optimizasyonu',
        'icon' => 'bi-file-earmark-text',
        'color' => 'primary',
        'description' => 'Mevcut içeriklerinizi SEO açısından analiz eder ve iyileştirme önerileri sunar.',
        'link' => url('index.php?page=ai-assistant&action=content-optimization')
    ],
    [
        'id' => 'keyword-suggestions',
        'title' => 'Anahtar Kelime Önerileri',
        'icon' => 'bi-key',
        'color' => 'success',
        'description' => 'Hedef konu ve sektör için yeni anahtar kelime önerileri sunar.',
        'link' => url('index.php?page=ai-assistant&action=keyword-suggestions')
    ],
    [
        'id' => 'technical-seo',
        'title' => 'Teknik SEO Çözümleri',
        'icon' => 'bi-gear',
        'color' => 'danger',
        'description' => 'Teknik SEO sorunları için analiz ve çözüm önerileri sunar.',
        'link' => url('index.php?page=ai-assistant&action=technical-seo')
    ],
    [
        'id' => 'competitor-analysis',
        'title' => 'Rakip Analizi',
        'icon' => 'bi-graph-up',
        'color' => 'info',
        'description' => 'Rakiplerinizin SEO stratejilerini analiz eder ve rekabet avantajları sunar.',
        'link' => url('index.php?page=ai-assistant&action=competitor-analysis')
    ],
    [
        'id' => 'content-brief',
        'title' => 'İçerik Brifingi',
        'icon' => 'bi-pencil-square',
        'color' => 'warning',
        'description' => 'SEO odaklı içerik üretimi için detaylı içerik brifingi oluşturur.',
        'link' => url('index.php?page=ai-assistant&action=content-brief')
    ],
    [
        'id' => 'performance-insights',
        'title' => 'Performans İçgörüleri',
        'icon' => 'bi-bar-chart',
        'color' => 'secondary',
        'description' => 'SEO performans verilerinizi analiz eder ve anlamlı içgörüler sunar.',
        'link' => url('index.php?page=ai-assistant&action=performance-insights')
    ]
];

// Son AI önerileri
$recentSuggestions = $db->getAll("
    SELECT ai.*, p.project_name
    FROM ai_suggestions ai
    JOIN projects p ON ai.project_id = p.id
    ORDER BY ai.created_at DESC
    LIMIT 5
");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-robot me-2"></i> AI Asistan</h4>
</div>

<div class="row mb-4">
    <?php foreach ($aiFeatures as $feature): ?>
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-3 me-3 bg-<?php echo $feature['color']; ?> bg-opacity-10 rounded-circle">
                            <i class="bi <?php echo $feature['icon']; ?> text-<?php echo $feature['color']; ?> fs-4"></i>
                        </div>
                        <h5 class="card-title mb-0"><?php echo $feature['title']; ?></h5>
                    </div>
                    <p class="card-text flex-grow-1"><?php echo $feature['description']; ?></p>
                    <a href="<?php echo $feature['link']; ?>" class="btn btn-outline-<?php echo $feature['color']; ?> mt-2">Kullan</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Son Kullanılan AI Önerileri -->
<div class="card mt-5">
    <div class="card-header">
        <h5 class="mb-0">Son AI Önerileri</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Proje</th>
                        <th>Öneri Türü</th>
                        <th>Durum</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentSuggestions) > 0): ?>
                        <?php foreach ($recentSuggestions as $suggestion): ?>
                            <tr>
                                <td><?php echo formatDate($suggestion['created_at']); ?></td>
                                <td><?php echo sanitize($suggestion['project_name']); ?></td>
                                <td>
                                    <?php
                                    $suggestionTypes = [
                                        'content_optimization' => 'İçerik Optimizasyonu',
                                        'keyword_recommendation' => 'Anahtar Kelime Önerisi',
                                        'technical_fix' => 'Teknik SEO Çözümü',
                                        'strategy' => 'Strateji Önerisi',
                                        'other' => 'Diğer'
                                    ];
                                    echo $suggestionTypes[$suggestion['request_type']] ?? 'Bilinmeyen';
                                    ?>
                                </td>
                                <td>
                                    <?php if ($suggestion['applied']): ?>
                                        <span class="badge badge-subtle-success">Uygulandı</span>
                                    <?php else: ?>
                                        <span class="badge badge-subtle-warning">Bekliyor</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?php echo url('index.php?page=ai-assistant&action=view&id=' . $suggestion['id']); ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Görüntüle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (!$suggestion['applied']): ?>
                                        <a href="<?php echo url('index.php?page=ai-assistant&action=apply&id=' . $suggestion['id']); ?>" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Uygula">
                                            <i class="bi bi-check-lg"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?php echo url('index.php?page=ai-assistant&action=delete&id=' . $suggestion['id'] . '&csrf=' . generateCsrfToken()); ?>" class="btn btn-sm btn-outline-danger confirm-action" data-bs-toggle="tooltip" title="Sil">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">Henüz AI önerisi bulunmuyor.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Groq API Durumu -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Groq API Durumu</h5>
    </div>
    <div class="card-body">
        <?php
        // Groq API'yi test et
        try {
            $testResponse = $groqApi->generateCompletion("Test mesajı", [
                'max_tokens' => 10
            ]);
            
            $isConnected = isset($testResponse['choices']) && is_array($testResponse['choices']);
            
            if ($isConnected) {
                $model = $testResponse['model'] ?? GROQ_API_MODEL;
                echo '<div class="alert alert-success mb-0"><i class="bi bi-check-circle me-2"></i> Groq API bağlantısı aktif. Kullanılan model: ' . $model . '</div>';
            } else {
                echo '<div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-2"></i> Groq API bağlantısı kuruldu, ancak beklenmeyen bir yanıt alındı.</div>';
            }
        } catch (Exception $e) {
            echo '<div class="alert alert-danger mb-0"><i class="bi bi-x-circle me-2"></i> Groq API bağlantı hatası: ' . $e->getMessage() . '</div>';
        }
        ?>
        
        <div class="mt-3">
            <p><strong>API Anahtarı:</strong> <?php echo substr(GROQ_API_KEY, 0, 8) . '...' . substr(GROQ_API_KEY, -4); ?></p>
            <p><strong>Model:</strong> <?php echo GROQ_API_MODEL; ?></p>
            <p><strong>API URL:</strong> <?php echo GROQ_API_URL; ?></p>
            
            <p class="mb-0 small text-muted">
                Ayarları değiştirmek için <a href="<?php echo url('index.php?page=settings&action=api'); ?>">API Ayarları</a> sayfasını ziyaret edin.
            </p>
        </div>
    </div>
</div>