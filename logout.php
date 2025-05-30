<?php
// Gerekli dosyaları dahil et
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Kullanıcı çıkışı
logoutUser();

// Çıkış mesajı göster ve giriş sayfasına yönlendir
setFlashMessage('success', 'Başarıyla çıkış yaptınız.');
redirect(url('login.php'));
?>