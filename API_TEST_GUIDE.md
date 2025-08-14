# ElevenLabs API Test Guide

Bu qo'llanma real ElevenLabs API key bilan paketni qanday test qilishni ko'rsatadi.

## 🚀 Tez Test (Quick Test)

### 1-qadam: API Key sozlash

`quick_test.php` faylini oching va API key ni qo'ying:

```php
$apiKey = 'sk-your-actual-api-key-here';
```

### 2-qadam: Test ishga tushirish

```bash
php quick_test.php
```

Bu test quyidagi funksiyalarni sinaydi:
- ✅ User info va subscription
- ✅ Available voices ro'yxati
- ✅ Text-to-Speech (audio yaratish)
- ✅ Character usage statistics
- ✅ Voice cloning (agar sample fayllar bo'lsa)

## 🔬 To'liq Test (Full Test)

### 1-qadam: API Key sozlash

`real_api_test.php` faylini oching va API key ni qo'ying:

```php
$apiKey = 'sk-your-actual-api-key-here';
```

### 2-qadam: To'liq test ishga tushirish

```bash
php real_api_test.php
```

Bu test barcha servislardagi metodlarni sinaydi:

### Analytics Service
- ✅ getUserInfo()
- ✅ getUserSubscription() 
- ✅ getModels()
- ✅ getCharacterUsage()
- ✅ getHistory()

### Voice Service
- ✅ getVoices()
- ✅ getVoice()
- ✅ getSharedVoices()
- ✅ getPronunciationDictionaries()
- ✅ createVoicePreviews()

### Audio Service
- ✅ textToSpeech()
- ✅ textToSpeechAndSave()
- ✅ saveAudioToFile()
- ✅ soundGeneration()

### AI Service
- ✅ getConversationalAISettings()
- ✅ getWorkspaceSecrets()
- ✅ getKnowledgeBases()
- ✅ getAgents()
- ✅ getConversations()

### Studio Service
- ✅ getStudioProjects()

### Workspace Service
- ✅ getWorkspaceResources()
- ✅ getWorkspaceMembers()

## 🎭 Voice Cloning Test

Voice cloning uchun alohida test:

### 1-qadam: Audio sample fayllar tayyorlash

```bash
mkdir voice_samples
# WAV format fayllarni voice_samples/ papkaga qo'ying
# sample1.wav, sample2.wav, va hokazo
```

### 2-qadam: Voice cloning testi

`real_api_test.php` da quyidagi qatorni aktivlashtiring:

```php
// Uncomment this line:
$tester->testVoiceCloning();
```

## 📁 Natijalar

Testlar quyidagi fayllarni yaratadi:

```
test_output/
├── test_tts.mp3                 # TTS audio
├── test_tts_combined.mp3        # Combined TTS method
├── quick_test.mp3               # Quick test audio
└── other_generated_files...     # Boshqa audio fayllar
```

## ⚠️ Muhim Eslatmalar

### API Limitlar
- **Free plan**: 10,000 belgi/oy
- **Starter plan**: 30,000 belgi/oy
- **Creator plan**: 100,000 belgi/oy

### Voice Cloning Talablari
- Kamida 1 minut audio kerak
- WAV yoki MP3 format
- Sifatli audio (kam shovqin)
- 25MB dan kam fayl hajmi

### Xato Holatlar
- `401 Unauthorized`: API key noto'g'ri
- `402 Payment Required`: Limit tugagan
- `429 Too Many Requests`: Juda ko'p so'rov

## 🔧 Debugging

### Verbose rejimda ishga tushirish

```php
// BaseElevenLabsService da debug yoqish
$this->debugMode = true;
```

### Xatolarni ko'rish

```php
try {
    $result = $service->audio()->textToSpeech($text);
    if (!$result['success']) {
        echo "Error: " . $result['error'];
        echo "Details: " . json_encode($result);
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
```

## 💡 Maslahatlar

1. **Testdan oldin**: API key va internet aloqasini tekshiring
2. **Birinchi test**: `quick_test.php` bilan boshlang
3. **Voice cloning**: Sifatli audio sample fayllar tayyorlang
4. **Limitlar**: Character usage ni kuzatib turing
5. **Xatolar**: Log fayllarni tekshiring

## 📞 Yordam

Agar test paytida muammolar bo'lsa:

1. API key to'g'riligini tekshiring
2. Internet aloqasini tekshiring
3. ElevenLabs service statusini tekshiring
4. Package versiyasini yangilang

```bash
composer update samandar/laravel-elevenlabs
```

## ✨ Test Natijalari

Muvaffaqiyatli testdan so'ng quyidagilarni ko'rasiz:

```
🎉 Ba'zi testlar muvaffaqiyatli o'tdi!
📁 Audio fayllar: /path/to/test_output/
📊 JAMI: 20 test
✅ Muvaffaqiyatli: 18
⚠️  Xatolik: 2
❌ Exception: 0
```

Happy testing! 🚀
