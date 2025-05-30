<?php
// Anahtar Kelime Sıralama Kontrol Sayfası

// Anahtar kelime ID'sini al
$keywordId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ID kontrolü
if ($keywordId <= 0) {
    setFlashMessage('danger', 'Geçersiz anahtar kelime ID\'si.');
    redirect(url('index.php?page=keywords'));
}

try {
    // Anahtar kelimeyi al
    $keyword = $db->getRow("SELECT k.*, p.project_name FROM keywords k JOIN projects p ON k.project_id = p.id WHERE k.id = ?", [$keywordId]);
    
    if (!$keyword) {
        setFlashMessage('danger', 'Anahtar kelime bulunamadı.');
        redirect(url('index.php?page=keywords'));
    }
    
    // Bu aşamada normalde bir API kullanarak gerçek sıralama kontrolü yapılır
    // Burada demo amaçlı rastgele bir sıralama üretiyoruz
    $newRank = rand(1, 100);
    
    // Veritabanına kaydet
    // 1. Sıralama geçmişine ekle
    $db->insert('keyword_rankings', [
        'keyword_id' => $keywordId,
        'rank' => $newRank,
        'check_date' => date('Y-m-d')
    ]);
    
    // 2. Anahtar kelimenin mevcut sıralamasını güncelle
    $db->update('keywords', [
        'current_rank' => $newRank,
        'last_checked' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$keywordId]);
    
    // Başarılı mesaj
    setFlashMessage('success', 'Anahtar kelime sıralaması kontrol edildi. Mevcut sıralama: ' . $newRank);
    
    // Anahtar kelime detay sayfasına yönlendir
    redirect(url('index.php?page=keywords&action=view&id=' . $keywordId));
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Veritabanı hatası: ' . $e->getMessage());
    redirect(url('index.php?page=keywords'));
}
?>