<?php
// Görevler listesi

// Proje filtresi
$projectFilter = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

// Arama filtresi
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$priorityFilter = isset($_GET['priority']) ? $_GET['priority'] : '';
$assignedToFilter = isset($_GET['assigned_to']) ? intval($_GET['assigned_to']) : 0;

// SQL sorgusu oluştur
$sql = "
    SELECT t.*, p.project_name, c.company_name, u.first_name, u.last_name
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    JOIN clients c ON p.client_id = c.id
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE 1=1
";
$params = [];

if ($projectFilter > 0) {
    $sql .= " AND t.project_id = ?";
    $params[] = $projectFilter;
}

if (!empty($searchTerm)) {
    $sql .= " AND (t.task_name LIKE ? OR p.project_name LIKE ? OR c.company_name LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if (!empty($statusFilter)) {
    $sql .= " AND t.status = ?";
    $params[] = $statusFilter;
}

if (!empty($priorityFilter)) {
    $sql .= " AND t.priority = ?";
    $params[] = $priorityFilter;
}

if ($assignedToFilter > 0) {
    $sql .= " AND t.assigned_to = ?";
    $params[] = $assignedToFilter;
}

// Eğer kendime atanan görevler filtresi varsa, sadece kullanıcının kendi görevlerini göster
if (isset($_GET['my_tasks']) && $_GET['my_tasks'] == 1) {
    $sql .= " AND t.assigned_to = ?";
    $params[] = $_SESSION['user_id'];
}

$sql .= " ORDER BY t.due_date ASC, t.priority DESC";

// Projeleri ve kullanıcıları al (filtre için)
try {
    $projects = $db->getAll("
        SELECT p.id, p.project_name, c.company_name
        FROM projects p
        JOIN clients c ON p.client_id = c.id
        ORDER BY c.company_name ASC, p.project_name ASC
    ");
    
    $users = $db->getAll("
        SELECT id, first_name, last_name 
        FROM users 
        WHERE role != 'client'
        ORDER BY first_name, last_name
    ");
    
    // Görevleri al
    $tasks = $db->getAll($sql, $params);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Veri yüklenirken bir hata oluştu: ' . $e->getMessage() . '</div>';
    $projects = [];
    $users = [];
    $tasks = [];
}

// Durum sınıfları ve isimleri
$statusClasses = [
    'not_started' => 'bg-secondary',
    'in_progress' => 'bg-primary',
    'review' => 'bg-info',
    'completed' => 'bg-success'
];

$statusNames = [
    'not_started' => 'Başlanmadı',
    'in_progress' => 'Devam Ediyor',
    'review' => 'İncelemede',
    'completed' => 'Tamamlandı'
];

// Öncelik sınıfları ve isimleri
$priorityClasses = [
    'urgent' => 'bg-danger',
    'high' => 'bg-warning',
    'medium' => 'bg-info',
    'low' => 'bg-secondary'
];

$priorityNames = [
    'urgent' => 'Acil',
    'high' => 'Yüksek',
    'medium' => 'Orta',
    'low' => 'Düşük'
];
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Görevler</h4>
        <div>
            <a href="<?php echo url('index.php?page=tasks&my_tasks=1'); ?>" class="btn btn-outline-primary me-2">
                <i class="bi bi-person-check me-1"></i> Bana Atanan Görevler
            </a>
            <a href="<?php echo url('index.php?page=tasks&action=add'); ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Yeni Görev
            </a>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <input type="hidden" name="page" value="tasks">
                
                <div class="col-md-3">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Görev, proje veya müşteri ara..." name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <select name="project_id" class="form-select">
                        <option value="0">Tüm Projeler</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" <?php echo $projectFilter === $project['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['project_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Tüm Durumlar</option>
                        <option value="not_started" <?php echo $statusFilter === 'not_started' ? 'selected' : ''; ?>>Başlanmadı</option>
                        <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>Devam Ediyor</option>
                        <option value="review" <?php echo $statusFilter === 'review' ? 'selected' : ''; ?>>İncelemede</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Tamamlandı</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <select name="priority" class="form-select">
                        <option value="">Tüm Öncelikler</option>
                        <option value="urgent" <?php echo $priorityFilter === 'urgent' ? 'selected' : ''; ?>>Acil</option>
                        <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>Yüksek</option>
                        <option value="medium" <?php echo $priorityFilter === 'medium' ? 'selected' : ''; ?>>Orta</option>
                        <option value="low" <?php echo $priorityFilter === 'low' ? 'selected' : ''; ?>>Düşük</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <select name="assigned_to" class="form-select">
                        <option value="0">Tüm Atananlar</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $assignedToFilter === $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <div class="d-grid">
                        <a href="<?php echo url('index.php?page=tasks'); ?>" class="btn btn-outline-secondary">Sıfırla</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Görev Listesi -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Görev Adı</th>
                            <th>Proje</th>
                            <th>Durum</th>
                            <th>Öncelik</th>
                            <th>Atanan Kişi</th>
                            <th>Son Tarih</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($tasks) > 0): ?>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                                    <td><?php echo htmlspecialchars($task['project_name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $statusClasses[$task['status']] ?? 'bg-secondary'; ?>">
                                            <?php echo $statusNames[$task['status']] ?? $task['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $priorityClasses[$task['priority']] ?? 'bg-secondary'; ?>">
                                            <?php echo $priorityNames[$task['priority']] ?? $task['priority']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($task['assigned_to']) {
                                            echo htmlspecialchars($task['first_name'] . ' ' . $task['last_name']);
                                        } else {
                                            echo '<span class="text-muted">Atanmamış</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($task['due_date']) {
                                            $dueDate = new DateTime($task['due_date']);
                                            $today = new DateTime();
                                            $diff = $today->diff($dueDate);
                                            
                                            echo date('d.m.Y', strtotime($task['due_date']));
                                            
                                            if ($today > $dueDate && $task['status'] != 'completed') {
                                                echo ' <span class="badge bg-danger">Gecikmiş</span>';
                                            } elseif ($diff->days <= 2 && $today <= $dueDate && $task['status'] != 'completed') {
                                                echo ' <span class="badge bg-warning">Yaklaşıyor</span>';
                                            }
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?php echo url('index.php?page=tasks&action=view&id=' . $task['id']); ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Görüntüle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo url('index.php?page=tasks&action=edit&id=' . $task['id']); ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Düzenle">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?php echo url('index.php?page=tasks&action=delete&id=' . $task['id']); ?>" class="btn btn-sm btn-outline-danger confirm-action" data-bs-toggle="tooltip" title="Sil">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <?php if (!empty($searchTerm) || !empty($statusFilter) || !empty($priorityFilter) || $projectFilter > 0 || $assignedToFilter > 0): ?>
                                        Arama kriterlerinize uygun görev bulunamadı.
                                    <?php else: ?>
                                        Henüz görev bulunmuyor. Eklemek için "Yeni Görev" butonunu kullanabilirsiniz.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>