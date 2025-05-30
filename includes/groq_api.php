<?php
// Groq API entegrasyonu

class GroqApi {
    private $apiKey;
    private $model;
    private $apiUrl;
    
    public function __construct() {
        $this->apiKey = GROQ_API_KEY;
        $this->model = GROQ_API_MODEL;
        $this->apiUrl = GROQ_API_URL;
    }
    
    // Ana API istek fonksiyonu
    public function generateCompletion($prompt, $options = []) {
        $defaultOptions = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1024
        ];
        
        $requestData = array_merge($defaultOptions, $options);
        
        $ch = curl_init($this->apiUrl . 'chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Groq API isteği başarısız: ' . $error);
        }
        
        return json_decode($response, true);
    }
    
    // İçerik optimizasyonu
    public function getContentOptimization($content, $keywords, $contentType = 'blog_post') {
        $prompt = "Sen deneyimli bir SEO içerik optimizasyon uzmanısın. Aşağıdaki içeriği analiz et ve SEO performansını artıracak öneriler sun.
        
        İÇERİK TÜRÜ: $contentType
        HEDEF ANAHTAR KELİMELER: $keywords
        
        İÇERİK:
        $content
        
        Lütfen şu başlıklar altında analiz ve öneriler sun:
        1. Başlık Optimizasyonu (H1, H2, H3 kullanımı)
        2. Anahtar Kelime Yerleşimi ve Yoğunluğu
        3. İçerik Yapısı ve Okunabilirlik
        4. İç Bağlantı Önerileri
        5. Meta Açıklama Önerisi
        6. Kullanıcı Niyeti Karşılama Düzeyi
        7. İçerik Uzunluğu ve Derinlik Analizi
        8. Öne Çıkan İyileştirme Alanları (En önemli 3 iyileştirme)";
        
        $result = $this->generateCompletion($prompt, [
            'temperature' => 0.2,
            'max_tokens' => 2048
        ]);
        
        return [
            'content' => $content,
            'keywords' => $keywords,
            'content_type' => $contentType,
            'analysis' => $this->extractContent($result)
        ];
    }
    
    // Anahtar kelime önerileri
    public function getKeywordSuggestions($topic, $industry, $count = 20) {
        $prompt = "Sen uzman bir SEO anahtar kelime araştırmacısısın. Aşağıdaki konu ve sektör için en alakalı, yüksek dönüşüm potansiyeli olan ve farklı kullanıcı niyetlerine hitap eden anahtar kelimeler öner.
        
        KONU: $topic
        SEKTÖR: $industry
        İSTENEN ÖNERİ SAYISI: $count
        
        Anahtar kelimeleri şu şekilde JSON formatında listele:
        [
          {
            \"keyword\": \"anahtar kelime\",
            \"search_intent\": \"bilgisel|ticari|işlemsel|navigasyonel\",
            \"competition_level\": \"düşük|orta|yüksek\",
            \"suggested_content_type\": \"blog|ürün sayfası|kategori sayfası|landing page|faq\"
          }
        ]
        
        Sadece JSON formatında yanıt ver, başka açıklama ekleme.";
        
        $result = $this->generateCompletion($prompt, [
            'temperature' => 0.3,
            'max_tokens' => 1500
        ]);
        
        $content = $this->extractContent($result);
        
        // JSON parse etmeye çalış
        try {
            $keywords = json_decode($content, true);
            return [
                'topic' => $topic,
                'industry' => $industry,
                'suggestions' => $keywords
            ];
        } catch (Exception $e) {
            return [
                'topic' => $topic,
                'industry' => $industry,
                'error' => 'Anahtar kelime önerileri işlenemedi',
                'raw_response' => $content
            ];
        }
    }
    
    // Teknik SEO çözümleri
    public function getTechnicalSeoSolution($issueType, $issueDescription, $pageUrl = '') {
        $prompt = "Sen uzman bir teknik SEO danışmanısın. Aşağıdaki teknik SEO sorunu için en iyi çözüm ve uygulama adımlarını açıkla.
        
        SORUN TÜRÜ: $issueType
        SAYFA URL: $pageUrl
        SORUN AÇIKLAMASI: $issueDescription
        
        Lütfen şu başlıklar altında çözüm sun:
        1. Sorunun Potansiyel SEO Etkisi
        2. Kök Neden Analizi
        3. Çözüm Adımları (teknik detaylarla)
        4. Uygulanacak Best Practices
        5. Çözüm Sonrası Kontrol Adımları";
        
        $result = $this->generateCompletion($prompt, [
            'temperature' => 0.1,
            'max_tokens' => 2048
        ]);
        
        return [
            'issue_type' => $issueType,
            'page_url' => $pageUrl,
            'issue_description' => $issueDescription,
            'solution' => $this->extractContent($result)
        ];
    }
    
    // Rakip analizi
    public function getCompetitorAnalysis($websiteUrl, $competitorUrls, $focusKeywords) {
        $competitorsStr = implode("\n", array_map(function($url, $index) {
            return ($index + 1) . ". " . $url;
        }, $competitorUrls, array_keys($competitorUrls)));
        
        $prompt = "Sen deneyimli bir SEO stratejisti ve rekabet analisti olarak çalışıyorsun. Aşağıdaki web sitesi ve rakipler için kapsamlı bir rekabet analizi ve stratejik SEO önerileri sun.
        
        MÜŞTERİ WEB SİTESİ: $websiteUrl
        
        RAKİP WEB SİTELERİ:
        $competitorsStr
        
        ODAK ANAHTAR KELİMELER: $focusKeywords
        
        Lütfen şu başlıklar altında analiz ve öneriler sun:
        1. Rekabet Özeti ve Pazar Konumlandırması
        2. Her Rakip İçin İçerik Stratejisi Analizi
        3. Teknik SEO Karşılaştırması ve Fırsatlar
        4. Backlink Profili ve İçerik Boşlukları
        5. Kısa Vadeli Kazanım Fırsatları (Hızlı Kazanımlar)
        6. Orta-Uzun Vadeli Stratejik Öneriler
        7. İçerik Takvimi Önerisi (5 öncelikli içerik fikri)";
        
        $result = $this->generateCompletion($prompt, [
            'temperature' => 0.3,
            'max_tokens' => 3000
        ]);
        
        return [
            'website_url' => $websiteUrl,
            'competitor_urls' => $competitorUrls,
            'focus_keywords' => $focusKeywords,
            'analysis' => $this->extractContent($result)
        ];
    }
    
    // İçerik brifingi oluşturma
    public function generateContentBrief($topic, $keywords, $contentType, $wordCount) {
        $prompt = "Sen profesyonel bir SEO içerik stratejistisin. Aşağıdaki bilgiler doğrultusunda kapsamlı bir içerik brifingi hazırla.
        
        KONU: $topic
        HEDEF ANAHTAR KELİMELER: $keywords
        İÇERİK TÜRÜ: $contentType
        HEDEF KELİME SAYISI: $wordCount
        
        Lütfen şu başlıklar altında içerik brifingi oluştur:
        1. İçerik Amacı ve Hedef Kitle
        2. Önerilen Başlık Alternatifleri (5 farklı başlık)
        3. Meta Açıklama Taslağı
        4. İçerik Yapısı ve Alt Başlıklar (H2, H3)
        5. Yanıtlanması Gereken Anahtar Sorular
        6. Dahil Edilmesi Gereken İstatistikler ve Veri Noktaları
        7. İç Bağlantı Fırsatları
        8. Görsel ve Multimedya Önerileri
        9. CTA ve Dönüşüm Hedefleri
        10. SEO Kontrol Listesi";
        
        $result = $this->generateCompletion($prompt, [
            'temperature' => 0.4,
            'max_tokens' => 2500
        ]);
        
        return [
            'topic' => $topic,
            'keywords' => $keywords,
            'content_type' => $contentType,
            'word_count' => $wordCount,
            'brief' => $this->extractContent($result)
        ];
    }
    
    // Sonuçlardan içeriği çıkarma
    private function extractContent($apiResult) {
        if (isset($apiResult['choices'][0]['message']['content'])) {
            return $apiResult['choices'][0]['message']['content'];
        }
        
        return '';
    }
    
    // API isteğini kaydet
    public function logApiRequest($requestType, $requestData, $responseData) {
        global $db;
        
        try {
            // API isteklerini loglama
            $db->insert('api_logs', [
                'api_type' => 'groq',
                'request_type' => $requestType,
                'request_data' => json_encode($requestData),
                'response_data' => json_encode($responseData),
                'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Log hatası alınırsa sessizce devam et
        }
    }
}

// Groq API'yi başlat
$groqApi = new GroqApi();
?>