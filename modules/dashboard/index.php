<?php
// Dashboard ana sayfası

// Özet verileri al
$clientCount = $db->getRow("SELECT COUNT(*) as count FROM clients WHERE status = 'active'")['count'];
$projectCount = $db->getRow("SELECT COUNT(*) as count FROM projects WHERE status IN ('planning', 'in_progress')")['count'];
$taskCount = $db->getRow("SELECT COUNT(*) as count FROM tasks WHERE status != 'completed'")['count'];
$keywordCount = $db->getRow("SELECT COUNT(*) as count FROM keywords")['count'];

// Son eklenen projeler
$recentProjects = $db->getAll("
    SELECT p.*, c.company_name 
    FROM projects p
    JOIN clients c ON p.client_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 5
");

// Yaklaşan görevler
$upcomingTasks = $db->getAll("
    SELECT t.*, p.project_name, u.first_name, u.last_name 
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE t.status != 'completed' AND t.due_date >= CURDATE()
    ORDER BY t.due_date ASC
    LIMIT 5
");

// AI önerileri
$aiSuggestions = $db->getAll("
    SELECT * FROM ai_suggestions
    WHERE applied = FALSE
    ORDER BY created_at DESC
    LIMIT 3
");
?>

<div class="row">
    <!-- İstatistik Kartları -->
    <div class="col-md-3">
        <div class="card stat-card text-white bg-primary">
            <div class="card-body">
                <div class="icon"><i class="bi bi-people"></i></div>
                <div class="stat-title">Aktif Müşteriler</div>
                <div class="stat-value"><?php echo $clientCount; ?></div>
                <?php
                $newClientsThisMonth = $db->getRow("SELECT COUNT(*) as count FROM clients WHERE status = 'active' AND MONTH(client_since) = MONTH(CURRENT_DATE()) AND YEAR(client_since) = YEAR(CURRENT_DATE())")['count'];
                if ($newClientsThisMonth > 0) {
                    echo '<div class="stat-change positive"><i class="bi bi-arrow-up"></i> ' . $newClientsThisMonth . ' yeni bu ay</div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card text-white bg-success">
            <div class="card-body">
                <div class="icon"><i class="bi bi-briefcase"></i></div>
                <div class="stat-title">Aktif Projeler</div>
                <div class="stat-value"><?php echo $projectCount; ?></div>
                <?php
                $newProjectsThisMonth = $db->getRow("SELECT COUNT(*) as count FROM projects WHERE status IN ('planning', 'in_progress') AND MONTH(start_date) = MONTH(CURRENT_DATE()) AND YEAR(start_date) = YEAR(CURRENT_DATE())")['count'];
                if ($newProjectsThisMonth > 0) {
                    echo '<div class="stat-change positive"><i class="bi bi-arrow-up"></i> ' . $newProjectsThisMonth . ' yeni bu ay</div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card text-white bg-warning">
            <div class="card-body">
                <div class="icon"><i class="bi bi-list-check"></i></div>
                <div class="stat-title">Bekleyen Görevler</div>
                <div class="stat-value"><?php echo $taskCount; ?></div>
                <?php
                $urgentTasks = $db->getRow("SELECT COUNT(*) as count FROM tasks WHERE status != 'completed' AND priority = 'urgent'")['count'];
                if ($urgentTasks > 0) {
                    echo '<div class="stat-change negative"><i class="bi bi-exclamation-triangle"></i> ' . $urgentTasks . ' acil görev</div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card text-white bg-info">
            <div class="card-body">
                <div class="icon"><i class="bi bi-key"></i></div>
                <div class="stat-title">Takip Edilen A. Kelimeler</div>
                <div class="stat-value"><?php echo $keywordCount; ?></div>
                <?php
                $improvedKeywords = $db->getRow("
                    SELECT COUNT(*) as count 
                    FROM keyword_rankings kr1
                    JOIN keyword_rankings kr2 ON kr1.keyword_id = kr2.keyword_id
                    WHERE kr1.check_date = (SELECT MAX(check_date) FROM keyword_rankings)
                    AND kr2.check_date = (SELECT MAX(check_date) FROM keyword_rankings WHERE check_date < (SELECT MAX(check_date) FROM keyword_rankings))
                    AND kr1.position < kr2.position
                ")['count'];
                if ($improvedKeywords > 0) {
                    echo '<div class="stat-change positive"><i class="bi bi-arrow-up"></i> ' . $improvedKeywords . ' iyileşen kelime</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- AI Önerileri -->
<?php if (count($aiSuggestions) > 0): ?>
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-robot me-2"></i> Yapay Zeka Önerileri</h5>
        <a href="<?php echo url('index.php?page=ai-assistant'); ?>" class="btn btn-sm btn-outline-primary">Tüm Öneriler</a>
    </div>
    <div class="card-body">
        <?php foreach ($aiSuggestions as $suggestion): ?>
            <?php
            $projectName = $db->getRow("SELECT project_name FROM projects WHERE id = ?", [$suggestion['project_id']])['project_name'];
            $suggestionData = json_decode($suggestion['response_data'], true);
            $title = '';
            $content = '';
            
            switch ($suggestion['request_type']) {
                case 'content_optimization':
                    $title = 'İçerik Optimizasyon Önerisi';
                    $content = isset($suggestionData['analysis']) ? $suggestionData['analysis'] : '';
                    break;
                    
                case 'keyword_recommendation':
                    $title = 'Anahtar Kelime Önerisi';
                    $content = 'Yeni anahtar kelime önerileri mevcut.';
                    break;
                    
                case 'technical_fix':
                    $title = 'Teknik SEO Düzeltme Önerisi';
                    $content = isset($suggestionData['solution']) ? $suggestionData['solution'] : '';
                    break;
                    
                case 'strategy':
                    $title = 'Strateji Önerisi';
                    $content = isset($suggestionData['analysis']) ? $suggestionData['analysis'] : '';
                    break;
                    
                default:
                    $title = 'AI Önerisi';
                    $content = 'Yeni bir öneri mevcut.';
            }
            
            // İçeriği kısalt
            $content = truncate($content, 200);
            ?>
            <div class="ai-suggestion">
                <div class="suggestion-title"><?php echo $title; ?> - <?php echo $projectName; ?></div>
                <div class="suggestion-content"><?php echo $content; ?></div>
                <div class="mt-2">
                    <a href="<?php echo url('index.php?page=ai-assistant&action=view&id=' . $suggestion['id']); ?>" class="btn btn-sm btn-primary">Detayları Gör</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="row mt-4">
    <!-- Son Projeler -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Son Eklenen Projeler</h5>
                <a href="<?php echo url('index.php?page=projects'); ?>" class="btn btn-sm btn-outline-primary">Tüm Projeler</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Proje Adı</th>
                                <th>Müşteri</th>
                                <th>Durum</th>
                                <th>Baş. Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recentProjects) > 0): ?>
                                <?php foreach ($recentProjects as $project): ?>
                                    <tr>
                                        <td><?php echo sanitize($project['project_name']); ?></td>
                                        <td><?php echo sanitize($project['company_name']); ?></td>
                                        <td>
                                            <?php
                                            $statusClasses = [
                                                'planning' => 'badge-subtle-info',
                                                'in_progress' => 'badge-subtle-primary',
                                                'on_hold' => 'badge-subtle-warning',
                                                'completed' => 'badge-subtle-success'
                                            ];
                                            $statusNames = [
                                                'planning' => 'Planlama',
                                                'in_progress' => 'Devam Ediyor',
                                                'on_hold' => 'Beklemede',
                                                'completed' => 'Tamamlandı'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $statusClasses[$project['status']]; ?>"><?php echo $statusNames[$project['status']]; ?></span>
                                        </td>
                                        <td><?php echo formatDate($project['start_date'], 'd.m.Y'); ?></td>
                                        <td>
                                            <a href="<?php echo url('index.php?page=projects&action=view&id=' . $project['id']); ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Görüntüle"><i class="bi bi-eye"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Henüz proje bulunmuyor.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Yaklaşan Görevler -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Yaklaşan Görevler</h5>
                <a href="<?php echo url('index.php?page=tasks'); ?>" class="btn btn-sm btn-outline-primary">Tüm Görevler</a>
            </div>
            <div class="card-body">
                <?php if (count($upcomingTasks) > 0): ?>
                    <?php foreach ($upcomingTasks as $task): ?>
                        <?php
                        $daysLeft = (strtotime($task['due_date']) - time()) / (60 * 60 * 24);
                        $daysLeftText = $daysLeft < 1 ? 'Bugün' : (floor($daysLeft) . ' gün');
                        $assignedTo = $task['first_name'] ? $task['first_name'] . ' ' . $task['last_name'] : 'Atanmamış';
                        
                        $taskIcons = [
                            'technical_seo' => 'bi-gear',
                            'on_page_seo' => 'bi-file-text',
                            'content_creation' => 'bi-pencil-square',
                            'link_building' => 'bi-link',
                            'reporting' => 'bi-graph-up',
                            'other' => 'bi-three-dots'
                        ];
                        $taskIcon = $taskIcons[$task['task_type']] ?? 'bi-check-square';
                        ?>
                        <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                            <div class="me-3">
                                <div class="btn btn-sm btn-light rounded-circle">
                                    <i class="bi <?php echo $taskIcon; ?>"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-1"><?php echo sanitize($task['task_name']); ?></h6>
                                <p class="mb-1 small text-muted"><?php echo sanitize($task['project_name']); ?></p>
                                <div class="small d-flex align-items-center mt-1">
                                    <span class="me-3"><i class="bi bi-calendar me-1"></i> <?php echo $daysLeftText; ?></span>
                                    <span><i class="bi bi-person me-1"></i> <?php echo $assignedTo; ?></span>
                                </div>
                            </div>
                            <div class="ms-auto">
                                <a href="<?php echo url('index.php?page=tasks&action=view&id=' . $task['id']); ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Görüntüle"><i class="bi bi-eye"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center my-3">Yaklaşan görev bulunmuyor.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- SEO Performans Grafiği -->
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">SEO Performans Özeti</h5>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                Son 30 Gün
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                <li><a class="dropdown-item" href="#">Son 7 Gün</a></li>
                <li><a class="dropdown-item" href="#">Son 30 Gün</a></li>
                <li><a class="dropdown-item" href="#">Son 90 Gün</a></li>
            </ul>
        </div>
    </div>
    <div class="card-body">
        <div style="height: 300px;">
            <p class="text-center text-muted my-5">SEO performans grafiği burada gösterilecek...</p>
            <!-- Örnek bir grafik burada yer alacak (JavaScript kütüphanesi kullanılacak) -->
        </div>
    </div>
</div>