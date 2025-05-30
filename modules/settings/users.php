<?php
// Kullanıcı yönetimi
// Not: Sadece admin kullanıcılar görebilir
if ($_SESSION['user_role'] !== 'admin') {
    setFlashMessage('danger', 'Bu sayfayı görüntülemek için yetkiniz bulunmuyor.');
    redirect(url('index.php?page=dashboard'));
}

// Yeni kullanıcı ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $role = $_POST['role'] ?? 'seo_specialist';
    
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Kullanıcı adı gereklidir.';
    }
    
    if (empty($email)) {
        $errors[] = 'E-posta adresi gereklidir.';
    }
    
    if (empty($password)) {
        $errors[] = 'Şifre gereklidir.';
    }
    
    if (empty($firstName) || empty($lastName)) {
        $errors[] = 'Ad ve soyad gereklidir.';
    }
    
    if (empty($errors)) {
        try {
            // Kullanıcı zaten var mı kontrol et
            $existingUser = $db->getRow("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
            
            if ($existingUser) {
                $errors[] = 'Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.';
            } else {
                // Şifreyi hashle
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Kullanıcıyı ekle
                $userId = $db->insert('users', [
                    'username' => $username,
                    'password' => $hashedPassword,
                    'email' => $email,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'role' => $role
                ]);
                
                if ($userId) {
                    setFlashMessage('success', 'Kullanıcı başarıyla eklendi.');
                    redirect(url('index.php?page=settings&action=users'));
                } else {
                    $errors[] = 'Kullanıcı eklenirken bir hata oluştu.';
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }
}

// Kullanıcı silme işlemi
if (isset($_GET['delete_user']) && !empty($_GET['delete_user'])) {
    $userId = intval($_GET['delete_user']);
    
    // Kendi hesabını silmesini engelle
    if ($userId === intval($_SESSION['user_id'])) {
        setFlashMessage('danger', 'Kendi hesabınızı silemezsiniz.');
        redirect(url('index.php?page=settings&action=users'));
    }
    
    try {
        $deleted = $db->delete('users', 'id = ?', [$userId]);
        
        if ($deleted) {
            setFlashMessage('success', 'Kullanıcı başarıyla silindi.');
        } else {
            setFlashMessage('danger', 'Kullanıcı silinirken bir hata oluştu.');
        }
    } catch (Exception $e) {
        setFlashMessage('danger', 'Veritabanı hatası: ' . $e->getMessage());
    }
    
    redirect(url('index.php?page=settings&action=users'));
}

// Kullanıcıları al
try {
    $users = $db->getAll("SELECT * FROM users ORDER BY role, first_name, last_name");
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Kullanıcı verileri yüklenirken bir hata oluştu: ' . $e->getMessage() . '</div>';
    $users = [];
}

// Rol isimleri
$roleNames = [
    'admin' => 'Yönetici',
    'manager' => 'Yönetici',
    'seo_specialist' => 'SEO Uzmanı',
    'content_writer' => 'İçerik Yazarı',
    'client' => 'Müşteri'
];
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Kullanıcı Yönetimi</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus me-1"></i> Yeni Kullanıcı
        </button>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Ad Soyad</th>
                            <th>Kullanıcı Adı</th>
                            <th>E-posta</th>
                            <th>Rol</th>
                            <th>Son Giriş</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'manager' ? 'warning' : 'info'); ?>">
                                            <?php echo $roleNames[$user['role']] ?? $user['role']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Henüz giriş yapmadı'; ?></td>
                                    <td class="text-end">
                                        <a href="<?php echo url('index.php?page=settings&action=edit_user&id=' . $user['id']); ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Düzenle">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <a href="<?php echo url('index.php?page=settings&action=users&delete_user=' . $user['id']); ?>" class="btn btn-sm btn-outline-danger confirm-action" data-bs-toggle="tooltip" title="Sil">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">Henüz kullanıcı bulunmuyor.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Yeni Kullanıcı Ekleme Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Yeni Kullanıcı Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Kullanıcı Adı *</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre *</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">Ad *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Soyad *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Rol</label>
                        <select class="form-select" id="role" name="role">
                            <option value="seo_specialist">SEO Uzmanı</option>
                            <option value="content_writer">İçerik Yazarı</option>
                            <option value="manager">Yönetici</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Kullanıcı Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>