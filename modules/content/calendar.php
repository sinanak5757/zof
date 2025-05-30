<?php
// content/calendar.php - İçerik Takvimi Sayfası

// Gerekli değişkenler ve yardımcı fonksiyonlar
$currentYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$currentMonth = isset($_GET['month']) ? intval($_GET['month']) : date('n');

// Önceki ve sonraki ay için tarihler
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// Ay adları
$monthNames = [
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran', 
    7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
];

// Kullanıcıları getir (yazarlar ve editörler)
try {
    $users = $db->getAll("SELECT id, first_name, last_name FROM users ORDER BY first_name");
} catch (Exception $e) {
    $users = [];
}

// Projeleri getir
try {
    $projects = $db->getAll("SELECT id, project_name FROM projects ORDER BY project_name");
} catch (Exception $e) {
    $projects = [];
}

// Filtreler
$projectFilter = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$userFilter = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Ayın ilk ve son günleri
$firstDayOfMonth = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$daysInMonth = date('t', $firstDayOfMonth);
$firstDayOfWeek = date('N', $firstDayOfMonth); // 1 (Pazartesi) - 7 (Pazar)

// Takvim başlangıç ve bitiş tarihleri (6 hafta göster)
$startDate = date('Y-m-d', strtotime('-' . ($firstDayOfWeek - 1) . ' days', $firstDayOfMonth));
$endDate = date('Y-m-d', strtotime('+41 days', strtotime($startDate)));

// İçerik durumlarına göre renkler
$statusColors = [
    'draft' => 'bg-secondary text-white', // Taslak - Gri
    'writing' => 'bg-info text-white',    // Yazılıyor - Mavi
    'review' => 'bg-warning text-dark',   // İnceleniyor - Sarı
    'scheduled' => 'bg-primary text-white', // Planlandı - Koyu Mavi
    'published' => 'bg-success text-white', // Yayınlandı - Yeşil
    'pending' => 'bg-warning text-dark'   // Beklemede - Sarı
];

// İçerik durumlarının etiketleri
$statusLabels = [
    'draft' => 'Taslak',
    'writing' => 'Yazılıyor',
    'review' => 'İnceleniyor',
    'scheduled' => 'Planlandı',
    'published' => 'Yayınlandı',
    'pending' => 'Beklemede'
];

// Tablodaki mevcut sütunları kontrol et
try {
    $tableColumns = $db->getAll("SHOW COLUMNS FROM content");
    $existingColumns = array_column($tableColumns, 'Field');
} catch (Exception $e) {
    $existingColumns = ['title', 'content', 'status', 'created_at']; // Minimum sütunlar
}

// Verilen aralıktaki içerikleri getir
$sql = "
    SELECT c.*, 
           u.first_name, u.last_name, 
           p.project_name,
           cat.name as category_name
    FROM content c
    LEFT JOIN users u ON c.created_by = u.id
    LEFT JOIN projects p ON c.project_id = p.id
    LEFT JOIN content_categories cat ON c.category_id = cat.id
    WHERE (
        (DATE(c.created_at) BETWEEN ? AND ?) OR
        (c.published_date BETWEEN ? AND ?) OR
        (c.scheduled_date BETWEEN ? AND ?)
    )
";

$params = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate];

// Filtre koşullarını ekle
if ($projectFilter > 0) {
    $sql .= " AND c.project_id = ?";
    $params[] = $projectFilter;
}

if ($userFilter > 0) {
    $sql .= " AND c.created_by = ?";
    $params[] = $userFilter;
}

if (!empty($statusFilter)) {
    $sql .= " AND c.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY c.created_at DESC";

try {
    $contents = $db->getAll($sql, $params);
} catch (Exception $e) {
    $contents = [];
    // Temel sütunlarla tekrar dene
    try {
        $basicSql = "
            SELECT c.*, u.first_name, u.last_name, p.project_name
            FROM content c
            LEFT JOIN users u ON c.created_by = u.id
            LEFT JOIN projects p ON c.project_id = p.id
            WHERE DATE(c.created_at) BETWEEN ? AND ?
        ";
        $basicParams = [$startDate, $endDate];
        
        if ($projectFilter > 0) {
            $basicSql .= " AND c.project_id = ?";
            $basicParams[] = $projectFilter;
        }
        
        if ($userFilter > 0) {
            $basicSql .= " AND c.created_by = ?";
            $basicParams[] = $userFilter;
        }
        
        if (!empty($statusFilter)) {
            $basicSql .= " AND c.status = ?";
            $basicParams[] = $statusFilter;
        }
        
        $contents = $db->getAll($basicSql, $basicParams);
    } catch (Exception $e2) {
        $contents = [];
    }
}

// İçerikleri tarihe göre düzenle - DÜZELTME
$calendarContents = [];
foreach ($contents as $content) {
    $date = '';
    
    // Öncelik sırasına göre tarihi belirle
    if (in_array('scheduled_date', $existingColumns) && !empty($content['scheduled_date']) && $content['scheduled_date'] !== '0000-00-00') {
        $date = date('Y-m-d', strtotime($content['scheduled_date']));
    } elseif (in_array('published_date', $existingColumns) && !empty($content['published_date']) && $content['published_date'] !== '0000-00-00') {
        $date = date('Y-m-d', strtotime($content['published_date']));
    } elseif (!empty($content['created_at'])) {
        $date = date('Y-m-d', strtotime($content['created_at']));
    } else {
        // Eğer hiçbir tarih yoksa, bu içeriği takvimde gösterme
        continue;
    }
    
    // Tarihin takvim aralığında olup olmadığını kontrol et
    if ($date >= $startDate && $date <= $endDate) {
        if (!isset($calendarContents[$date])) {
            $calendarContents[$date] = [];
        }
        $calendarContents[$date][] = $content;
    }
}

// Hızlı içerik ekleme - DÜZELTME
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_add'])) {
    $quickTitle = trim($_POST['quick_title'] ?? '');
    $quickDate = $_POST['quick_date'] ?? date('Y-m-d');
    $quickProject = intval($_POST['quick_project'] ?? 0);
    
    if (!empty($quickTitle)) {
        try {
            // Slug oluşturma fonksiyonu
            function createQuickSlug($string) {
                $string = str_replace(
                    ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç', ' '],
                    ['i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c', '-'],
                    $string
                );
                
                $string = mb_strtolower($string, 'UTF-8');
                $string = preg_replace('/[^a-z0-9-]/', '', $string);
                $string = preg_replace('/-+/', '-', $string);
                $string = trim($string, '-');
                
                return $string;
            }
            
            $slug = createQuickSlug($quickTitle);
            if (empty($slug)) {
                $slug = 'content-' . time();
            }
            
            // Slug benzersizliği kontrol et
            $originalSlug = $slug;
            $counter = 1;
            while (true) {
                $existingSlug = $db->getRow("SELECT id FROM content WHERE slug = ?", [$slug]);
                if (!$existingSlug) {
                    break;
                }
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            $quickData = [
                'title' => $quickTitle,
                'slug' => $slug,
                'content' => '',
                'status' => 'draft',
                'created_at' => $quickDate . ' ' . date('H:i:s'), // DÜZELTME: Seçilen tarihi kullan
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($quickProject > 0) {
                $quickData['project_id'] = $quickProject;
            }
            
            // Seçilen tarihi scheduled_date olarak kaydet
            if (in_array('scheduled_date', $existingColumns)) {
                $quickData['scheduled_date'] = $quickDate;
            }
            
            if (isset($_SESSION['user_id'])) {
                $quickData['created_by'] = $_SESSION['user_id'];
            }
            
            $newContentId = $db->insert('content', $quickData);
            
            if ($newContentId) {
                setFlashMessage('success', 'İçerik başarıyla eklendi.');
            } else {
                setFlashMessage('danger', 'İçerik eklenirken bir hata oluştu.');
            }
        } catch (Exception $e) {
            setFlashMessage('danger', 'Veritabanı hatası: ' . $e->getMessage());
        }
        
        redirect(url('index.php?page=content&action=calendar&year=' . $currentYear . '&month=' . $currentMonth));
    }
}

?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>İçerik Takvimi</h4>
        <div>
            <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#quickAddModal">
                <i class="bi bi-lightning me-1"></i> Hızlı Ekle
            </button>
            <a href="<?= url('index.php?page=content') ?>" class="btn btn-outline-secondary me-2">
                <i class="bi bi-list me-1"></i> İçerik Listesi
            </a>
            <a href="<?= url('index.php?page=content&action=add') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Yeni İçerik
            </a>
        </div>
    </div>
    
    <!-- Filtreler -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <input type="hidden" name="page" value="content">
                <input type="hidden" name="action" value="calendar">
                <input type="hidden" name="year" value="<?= $currentYear ?>">
                <input type="hidden" name="month" value="<?= $currentMonth ?>">
                
                <div class="col-md-3">
                    <select name="project_id" class="form-select">
                        <option value="0">Tüm Projeler</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" <?= $projectFilter == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['project_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <select name="user_id" class="form-select">
                        <option value="0">Tüm Kullanıcılar</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= $userFilter == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Tüm Durumlar</option>
                        <?php foreach ($statusLabels as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $statusFilter === $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <div class="d-flex">
                        <button type="submit" class="btn btn-primary me-2">Filtrele</button>
                        <a href="<?= url('index.php?page=content&action=calendar&year=' . $currentYear . '&month=' . $currentMonth) ?>" class="btn btn-outline-secondary">Sıfırla</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Takvim Başlığı ve Navigasyon -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <a href="<?= url('index.php?page=content&action=calendar&year=' . $prevYear . '&month=' . $prevMonth . 
                          ($projectFilter ? '&project_id=' . $projectFilter : '') . 
                          ($userFilter ? '&user_id=' . $userFilter : '') . 
                          ($statusFilter ? '&status=' . $statusFilter : '')) ?>" 
                   class="btn btn-sm btn-outline-light">
                    <i class="bi bi-chevron-left"></i> Önceki Ay
                </a>
                
                <h5 class="mb-0"><?= $monthNames[$currentMonth] ?> <?= $currentYear ?></h5>
                
                <a href="<?= url('index.php?page=content&action=calendar&year=' . $nextYear . '&month=' . $nextMonth . 
                          ($projectFilter ? '&project_id=' . $projectFilter : '') . 
                          ($userFilter ? '&user_id=' . $userFilter : '') . 
                          ($statusFilter ? '&status=' . $statusFilter : '')) ?>" 
                   class="btn btn-sm btn-outline-light">
                    Sonraki Ay <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>
        
        <!-- Takvim Görünümü -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered calendar-table mb-0">
                    <thead>
                        <tr class="text-center bg-light">
                            <th width="14.28%">Pazartesi</th>
                            <th width="14.28%">Salı</th>
                            <th width="14.28%">Çarşamba</th>
                            <th width="14.28%">Perşembe</th>
                            <th width="14.28%">Cuma</th>
                            <th width="14.28%">Cumartesi</th>
                            <th width="14.28%">Pazar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $currentDate = strtotime($startDate);
                        $endDateTimestamp = strtotime($endDate);
                        $todayTimestamp = strtotime(date('Y-m-d'));
                        
                        while ($currentDate <= $endDateTimestamp) {
                            echo '<tr>';
                            
                            // Haftanın her günü için
                            for ($i = 1; $i <= 7; $i++) {
                                $date = date('Y-m-d', $currentDate);
                                $day = date('j', $currentDate);
                                $month = date('n', $currentDate);
                                $year = date('Y', $currentDate);
                                
                                // Stil sınıfları
                                $cellClass = 'calendar-cell';
                                if ($currentDate == $todayTimestamp) {
                                    $cellClass .= ' bg-light-blue'; // Bugün
                                }
                                if ($month != $currentMonth) {
                                    $cellClass .= ' text-muted other-month'; // Diğer aylar
                                }
                                
                                echo '<td class="' . $cellClass . '" style="height: 120px; vertical-align: top; position: relative;">';
                                
                                // Gün numarası
                                echo '<div class="calendar-date fw-bold mb-1">' . $day . '</div>';
                                
                                // İçerikler
                                if (isset($calendarContents[$date])) {
                                    echo '<div class="calendar-events">';
                                    $count = 0;
                                    foreach ($calendarContents[$date] as $content) {
                                        if ($count >= 3) {
                                            $remaining = count($calendarContents[$date]) - 3;
                                            echo '<div class="calendar-event-more small text-muted">+' . $remaining . ' daha</div>';
                                            break;
                                        }
                                        
                                        $statusClass = isset($statusColors[$content['status']]) ? $statusColors[$content['status']] : 'bg-secondary text-white';
                                        
                                        echo '<div class="calendar-event ' . $statusClass . ' mb-1 p-1 rounded small" 
                                                  data-bs-toggle="tooltip" 
                                                  title="' . htmlspecialchars($content['title']) . 
                                                  (!empty($content['project_name']) ? ' - ' . htmlspecialchars($content['project_name']) : '') . '">';
                                        
                                        echo '<a href="' . url('index.php?page=content&action=view&id=' . $content['id']) . '" class="text-decoration-none text-reset">';
                                        echo '<div class="event-title" style="font-size: 10px; line-height: 1.2;">' . 
                                              htmlspecialchars(substr($content['title'], 0, 15)) . 
                                              (strlen($content['title']) > 15 ? '...' : '') . '</div>';
                                        echo '</a>';
                                        echo '</div>';
                                        
                                        $count++;
                                    }
                                    echo '</div>';
                                }
                                
                                // Yeni içerik ekleme butonu
                                echo '<a href="#" 
                                          class="calendar-add-btn position-absolute" 
                                          style="bottom: 5px; right: 5px; display: none;"
                                          title="Bu Tarihe İçerik Ekle"
                                          onclick="openQuickAdd(\'' . $date . '\')">
                                          <i class="bi bi-plus-circle text-primary"></i>
                                      </a>';
                                
                                echo '</td>';
                                
                                $currentDate = strtotime('+1 day', $currentDate);
                            }
                            
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Durum Açıklamaları -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">İçerik Durumları</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($statusLabels as $status => $label): ?>
                    <div class="col-md-2 mb-2">
                        <div class="d-flex align-items-center">
                            <div class="status-badge <?= $statusColors[$status] ?> me-2" style="width: 20px; height: 20px; border-radius: 3px;"></div>
                            <span><?= $label ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
   <!-- Yaklaşan İçerikler - DÜZELTİLMİŞ -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Yaklaşan Görevler</h5>
    </div>
    <div class="card-body">
        <?php
        // Yaklaşan içerikleri getir (önümüzdeki 7 gün) - DÜZELTME
        $nextWeek = date('Y-m-d', strtotime('+7 days'));
        $today = date('Y-m-d');
        
        try {
            // Önce tablodaki sütunları kontrol et
            $tableColumns = $db->getAll("SHOW COLUMNS FROM content");
            $existingColumns = array_column($tableColumns, 'Field');
            
            // Temel SQL sorgusu
            $upcomingSql = "
                SELECT c.id, c.title, c.status, c.created_at, c.updated_at";
            
            // Kullanıcı bilgisi için JOIN (eğer users tablosu varsa)
            if (in_array('created_by', $existingColumns)) {
                $upcomingSql .= ", u.first_name, u.last_name";
                $fromClause = " FROM content c LEFT JOIN users u ON c.created_by = u.id";
            } else {
                $fromClause = " FROM content c";
            }
            
            // Proje bilgisi için JOIN (eğer project_id sütunu varsa)
            if (in_array('project_id', $existingColumns)) {
                $upcomingSql .= ", p.project_name";
                $fromClause .= " LEFT JOIN projects p ON c.project_id = p.id";
            }
            
            $upcomingSql .= $fromClause;
            
            // WHERE koşulları
            $whereConditions = [];
            $params = [];
            
            // Scheduled date kontrolü
            if (in_array('scheduled_date', $existingColumns)) {
                $whereConditions[] = "(c.scheduled_date BETWEEN ? AND ? AND c.scheduled_date IS NOT NULL)";
                $params[] = $today;
                $params[] = $nextWeek;
            }
            
            // Draft içerikleri de dahil et
            if (!empty($whereConditions)) {
                $whereConditions[] = "(DATE(c.created_at) BETWEEN ? AND ? AND c.status = 'draft')";
                $params[] = $today;
                $params[] = $nextWeek;
                
                $upcomingSql .= " WHERE (" . implode(" OR ", $whereConditions) . ")";
            } else {
                // Eğer scheduled_date yoksa, sadece son 7 günün draft'larını göster
                $upcomingSql .= " WHERE DATE(c.created_at) BETWEEN ? AND ? AND c.status IN ('draft', 'pending')";
                $params = [$today, $nextWeek];
            }
            
            $upcomingSql .= " AND c.status != 'published' ORDER BY c.created_at ASC LIMIT 5";
            
            $upcomingContents = $db->getAll($upcomingSql, $params);
            
            if (count($upcomingContents) > 0) {
                echo '<div class="list-group">';
                foreach ($upcomingContents as $content) {
                    // Güvenli array erişimi
                    $title = $content['title'] ?? 'Başlıksız İçerik';
                    $status = $content['status'] ?? 'draft';
                    $projectName = $content['project_name'] ?? null;
                    $authorName = '';
                    
                    // Yazar adını oluştur
                    if (isset($content['first_name']) || isset($content['last_name'])) {
                        $firstName = $content['first_name'] ?? '';
                        $lastName = $content['last_name'] ?? '';
                        $authorName = trim($firstName . ' ' . $lastName);
                    }
                    
                    // Tarih belirleme
                    $displayDate = '';
                    if (in_array('scheduled_date', $existingColumns) && !empty($content['scheduled_date'])) {
                        $displayDate = date('d.m.Y', strtotime($content['scheduled_date']));
                    } else {
                        $displayDate = date('d.m.Y', strtotime($content['created_at']));
                    }
                    
                    // Durum rengini belirle
                    $statusClass = 'bg-secondary text-white';
                    $statusText = 'Bilinmiyor';
                    
                    switch($status) {
                        case 'draft':
                            $statusClass = 'bg-secondary text-white';
                            $statusText = 'Taslak';
                            break;
                        case 'pending':
                            $statusClass = 'bg-warning text-dark';
                            $statusText = 'Beklemede';
                            break;
                        case 'review':
                            $statusClass = 'bg-info text-white';
                            $statusText = 'İncelemede';
                            break;
                        case 'scheduled':
                            $statusClass = 'bg-primary text-white';
                            $statusText = 'Planlandı';
                            break;
                    }
                    
                    echo '<div class="list-group-item d-flex justify-content-between align-items-start">';
                    echo '<div class="flex-grow-1">';
                    echo '<h6 class="mb-1">' . htmlspecialchars($title) . '</h6>';
                    
                    // Proje bilgisi
                    if (!empty($projectName)) {
                        echo '<p class="mb-1 text-muted"><i class="bi bi-folder me-1"></i>' . htmlspecialchars($projectName) . '</p>';
                    }
                    
                    // Yazar bilgisi
                    if (!empty($authorName)) {
                        echo '<p class="mb-1 text-muted"><i class="bi bi-person me-1"></i>' . htmlspecialchars($authorName) . '</p>';
                    }
                    
                    echo '<small class="text-muted"><i class="bi bi-calendar me-1"></i>' . $displayDate . '</small>';
                    echo '</div>';
                    
                    echo '<div class="d-flex flex-column align-items-end">';
                    echo '<span class="badge ' . $statusClass . ' mb-2">' . $statusText . '</span>';
                    echo '<div class="btn-group" role="group">';
                    echo '<a href="' . url('index.php?page=content&action=view&id=' . $content['id']) . '" class="btn btn-sm btn-outline-primary" title="Görüntüle">';
                    echo '<i class="bi bi-eye"></i>';
                    echo '</a>';
                    echo '<a href="' . url('index.php?page=content&action=edit&id=' . $content['id']) . '" class="btn btn-sm btn-outline-secondary" title="Düzenle">';
                    echo '<i class="bi bi-pencil"></i>';
                    echo '</a>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<div class="text-center py-4">';
                echo '<i class="bi bi-calendar-check display-4 text-muted mb-3"></i>';
                echo '<p class="text-muted mb-0">Yaklaşan görev bulunmuyor.</p>';
                echo '<small class="text-muted">Önümüzdeki 7 gün için planlanmış içerik yok.</small>';
                echo '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="alert alert-warning mb-0">';
            echo '<i class="bi bi-exclamation-triangle me-2"></i>';
            echo '<strong>Uyarı:</strong> Yaklaşan görevler yüklenirken bir sorun oluştu.';
            echo '<br><small class="text-muted">Hata: ' . htmlspecialchars($e->getMessage()) . '</small>';
            echo '<hr class="my-2">';
            echo '<a href="' . url('index.php?page=content&action=add') . '" class="btn btn-sm btn-primary">';
            echo '<i class="bi bi-plus-circle me-1"></i> Yeni İçerik Ekle';
            echo '</a>';
            echo '</div>';
        }
        ?>
    </div>
</div>

<!-- Hızlı İçerik Ekleme Modal -->
<div class="modal fade" id="quickAddModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Hızlı İçerik Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="quick_title" class="form-label">İçerik Başlığı</label>
                        <input type="text" class="form-control" id="quick_title" name="quick_title" required>
                    </div>
                    <div class="mb-3">
                        <label for="quick_date" class="form-label">Planlanan Tarih</label>
                        <input type="date" class="form-control" id="quick_date" name="quick_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="quick_project" class="form-label">Proje</label>
                        <select class="form-select" id="quick_project" name="quick_project">
                            <option value="0">Proje Seçin</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['id'] ?>">
                                    <?= htmlspecialchars($project['project_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="quick_add" class="btn btn-primary">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.calendar-cell {
    cursor: pointer;
    transition: background-color 0.2s;
}

.calendar-cell:hover .calendar-add-btn {
    display: block !important;
}

.bg-light-blue {
    background-color: rgba(13, 110, 253, 0.1) !important;
}

.other-month {
    opacity: 0.5;
}

.calendar-event {
    cursor: pointer;
    transition: all 0.2s;
    font-size: 10px;
    line-height: 1.2;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
}

.calendar-event:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.calendar-table td {
    border: 1px solid #dee2e6;
    padding: 3px;
}

.calendar-date {
    color: #495057;
    font-size: 12px;
}

.calendar-event-more {
    font-style: italic;
    color: #6c757d;
    font-size: 9px;
}

/* Responsive düzenlemeler */
@media (max-width: 768px) {
    .calendar-table th,
    .calendar-table td {
        padding: 2px;
        font-size: 11px;
    }
    
    .calendar-event {
        font-size: 9px;
        padding: 1px 2px;
    }
}
</style>

<script>
// Bootstrap Tooltip'leri etkinleştir
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Hızlı ekleme modal'ını aç - DÜZELTME
function openQuickAdd(date) {
    // Tarihi doğru formatta ayarla
    const dateInput = document.getElementById('quick_date');
    if (dateInput) {
        dateInput.value = date;
    }
    
    // Modal'ı aç
    const modal = document.getElementById('quickAddModal');
    if (modal) {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

// Takvim hücrelerine tıklama olayı ekle
document.addEventListener('DOMContentLoaded', function() {
    // Tüm takvim hücrelerine tıklama olayı ekle
    document.querySelectorAll('.calendar-cell').forEach(function(cell, index) {
        // Her hücre için tarihi hesapla
        const startDate = new Date('<?= $startDate ?>');
        const cellDate = new Date(startDate);
        cellDate.setDate(startDate.getDate() + index);
        
        const dateString = cellDate.getFullYear() + '-' + 
                          String(cellDate.getMonth() + 1).padStart(2, '0') + '-' + 
                          String(cellDate.getDate()).padStart(2, '0');
        
        // Çift tıklama olayı ekle
        cell.addEventListener('dblclick', function() {
            openQuickAdd(dateString);
        });
        
        // Hover olayları
        cell.addEventListener('mouseenter', function() {
            if (!this.classList.contains('bg-light-blue')) {
                this.style.backgroundColor = '#f8f9fa';
            }
        });
        
        cell.addEventListener('mouseleave', function() {
            if (!this.classList.contains('bg-light-blue')) {
                this.style.backgroundColor = '';
            }
        });
    });
});

// Form submit olayını dinle
document.getElementById('quickAddModal').addEventListener('submit', function(e) {
    const titleInput = document.getElementById('quick_title');
    if (!titleInput.value.trim()) {
        e.preventDefault();
        alert('Lütfen bir başlık girin.');
        titleInput.focus();
    }
});
</script>