<?php
// Kimlik doğrulama ile ilgili fonksiyonlar

// Kullanıcı girişi kontrol
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Giriş kontrolü ve yönlendirme
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('warning', 'Bu sayfayı görüntülemek için giriş yapmalısınız.');
        redirect(url('login.php'));
    }
}

// Kullanıcı rolü kontrolü
function requireRole($requiredRole) {
    requireLogin();
    
    global $db;
    $userId = $_SESSION['user_id'];
    
    $user = $db->getRow("SELECT role FROM users WHERE id = ?", [$userId]);
    
    if ($user['role'] != $requiredRole && $user['role'] != 'admin') {
        setFlashMessage('danger', 'Bu sayfayı görüntülemek için yetkiniz bulunmuyor.');
        redirect(url('index.php'));
    }
}

// Kullanıcı girişi
function loginUser($email, $password) {
    global $db;
    
    $user = $db->getRow("SELECT * FROM users WHERE email = ?", [$email]);
    
    if (!$user) {
        return false;
    }
    
    if (password_verify($password, $user['password'])) {
        // Oturum bilgilerini ayarla
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        // Son giriş zamanını güncelle
        $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
        
        return true;
    }
    
    return false;
}

// Kullanıcı kaydı
function registerUser($userData) {
    global $db;
    
    // E-posta adresi zaten kullanılıyor mu?
    $existingUser = $db->getRow("SELECT id FROM users WHERE email = ?", [$userData['email']]);
    
    if ($existingUser) {
        return [
            'success' => false,
            'message' => 'Bu e-posta adresi zaten kullanılıyor.'
        ];
    }
    
    // Şifreyi hashle
    $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
    
    // Kullanıcıyı kaydet
    $userId = $db->insert('users', $userData);
    
    if ($userId) {
        return [
            'success' => true,
            'user_id' => $userId
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Kullanıcı kaydı sırasında bir hata oluştu.'
        ];
    }
}

// Şifre sıfırlama
function resetPassword($email) {
    global $db;
    
    $user = $db->getRow("SELECT id FROM users WHERE email = ?", [$email]);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Bu e-posta adresiyle kayıtlı bir kullanıcı bulunamadı.'
        ];
    }
    
    // Benzersiz bir token oluştur
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Token'ı veritabanına kaydet
    $db->update('users', [
        'reset_token' => $token,
        'reset_token_expiry' => $expiry
    ], 'id = ?', [$user['id']]);
    
    // Şifre sıfırlama e-postası gönder (burada e-posta gönderme kodu eklenecek)
    
    return [
        'success' => true,
        'message' => 'Şifre sıfırlama talimatları e-posta adresinize gönderildi.'
    ];
}

// Mevcut kullanıcı bilgilerini alma
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $db;
    return $db->getRow("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

// Kullanıcı çıkışı
function logoutUser() {
    // Oturum değişkenlerini temizle
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
    unset($_SESSION['user_role']);
    
    // Oturumu sonlandır
    session_destroy();
}
?>