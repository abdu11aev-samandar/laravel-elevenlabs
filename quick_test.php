<?php

/**
 * ElevenLabs Quick Test - Asosiy funksiyalarni tezda sinash
 * 
 * Ishlatish:
 * 1. API_KEY ni qo'ying
 * 2. php quick_test.php
 */

require_once 'vendor/autoload.php';

use Samandar\LaravelElevenLabs\Services\ElevenLabsService;

// API KEY ni bu yerga qo'ying
$apiKey = 'sk_ce264428783d15a5cd6577a1128b4048ee11164c1fab436b';

if ($apiKey === 'YOUR_ELEVENLABS_API_KEY_HERE') {
    echo "❌ API key ni sozlang!\n";
    exit(1);
}

echo "🚀 ElevenLabs Quick Test\n";
echo "========================\n\n";

$service = new ElevenLabsService($apiKey);

// 1. User info olish
echo "1️⃣  User info...\n";
$userInfo = $service->analytics()->getUserInfo();
if ($userInfo['success']) {
    echo "✅ User: " . ($userInfo['subscription']['tier'] ?? 'N/A') . " plan\n";
    echo "📊 Character limit: " . ($userInfo['subscription']['character_limit'] ?? 'N/A') . "\n";
} else {
    echo "❌ User info error: " . ($userInfo['error'] ?? 'Unknown') . "\n";
}

// 2. Available voices
echo "\n2️⃣  Available voices...\n";
$voices = $service->voice()->getVoices();
if ($voices['success'] && !empty($voices['voices'])) {
    echo "✅ " . count($voices['voices']) . " ta ovoz mavjud:\n";
    foreach (array_slice($voices['voices'], 0, 3) as $voice) {
        echo "   🎭 " . $voice['name'] . " (" . $voice['voice_id'] . ")\n";
    }
    $firstVoice = $voices['voices'][0]['voice_id'];
} else {
    echo "❌ Voices error\n";
    exit(1);
}

// 3. Text-to-Speech test
echo "\n3️⃣  Text-to-Speech test...\n";
$ttsResult = $service->audio()->textToSpeech(
    'Salom! Bu ElevenLabs API ning test ovozi.',
    $firstVoice
);

if ($ttsResult['success']) {
    echo "✅ TTS muvaffaqiyatli!\n";
    
    // Audio ni faylga saqlash
    $outputDir = __DIR__ . '/test_output';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    $audioFile = $outputDir . '/quick_test.mp3';
    $saved = $service->audio()->saveAudioToFile($ttsResult['audio'], $audioFile);
    
    if ($saved) {
        echo "💾 Audio saqlandi: $audioFile\n";
        echo "🔊 Faylni audio playerda eshitishingiz mumkin!\n";
    }
} else {
    echo "❌ TTS error: " . ($ttsResult['error'] ?? 'Unknown') . "\n";
}

// 4. Character usage
echo "\n4️⃣  Character usage...\n";
$usage = $service->analytics()->getCharacterUsage();
if ($usage['success']) {
    $used = $usage['usage']['character_count'] ?? 0;
    $limit = $usage['usage']['character_limit'] ?? 0;
    echo "✅ Belgilar: $used / $limit ishlatilgan\n";
    
    if ($limit > 0) {
        $percentage = round(($used / $limit) * 100, 1);
        echo "📈 Foiz: $percentage% ishlatilgan\n";
    }
} else {
    echo "❌ Usage error\n";
}

// 5. Voice cloning test (agar audio fayllar mavjud bo'lsa)
echo "\n5️⃣  Voice cloning test...\n";
$voiceCloningSamples = [
    __DIR__ . '/voice_samples/sample1.wav',
    __DIR__ . '/voice_samples/sample2.wav'
];

// Check if sample files exist
$existingSamples = array_filter($voiceCloningSamples, 'file_exists');

if (!empty($existingSamples)) {
    echo "🎭 Voice cloning sample fayllari topildi:\n";
    foreach ($existingSamples as $sample) {
        echo "   📁 " . basename($sample) . "\n";
    }
    
    $cloneResult = $service->voice()->addVoice(
        'Quick Test Voice ' . date('H:i:s'),
        $existingSamples,
        'Quick test uchun clone voice',
        ['test' => 'true']
    );
    
    if ($cloneResult['success']) {
        echo "✅ Voice cloning muvaffaqiyatli!\n";
        echo "🆔 Voice ID: " . ($cloneResult['voice']['voice_id'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Voice cloning error: " . ($cloneResult['error'] ?? 'Unknown') . "\n";
    }
} else {
    echo "⚠️  Voice cloning fayllar topilmadi\n";
    echo "   Voice cloning test uchun WAV fayllarni voice_samples/ papkaga qo'ying\n";
}

echo "\n🎉 Quick test tugallandi!\n";
echo "📁 Natijalar: " . (__DIR__ . '/test_output/') . "\n";
