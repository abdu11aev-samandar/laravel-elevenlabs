<?php

/**
 * ElevenLabs API Standalone Test Script
 * 
 * Bu skript Laravel config sistemiga bog'liq bo'lmagan holda
 * API ni test qiladi
 */

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class StandaloneElevenLabsTest
{
    private string $apiKey;
    private Client $client;
    private array $testResults = [];
    private string $outputDir;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client([
            'base_uri' => 'https://api.elevenlabs.io/v1',
            'headers' => [
                'xi-api-key' => $this->apiKey,
            ],
            'timeout' => 30,
        ]);
        $this->outputDir = __DIR__ . '/test_output';
        
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    public function runAllTests(): void
    {
        echo "🚀 ElevenLabs API Standalone Test boshlandi...\n\n";
        
        // Basic API tests
        $this->testUserInfo();
        $this->testVoices();
        $this->testModels();
        $this->testTextToSpeech();
        $this->testCharacterUsage();
        
        // Print summary
        $this->printSummary();
    }

    private function testUserInfo(): void
    {
        echo "1️⃣ User Info test qilinmoqda...\n";
        
        $this->runTest('getUserInfo', function() {
            $response = $this->client->get('/user');
            return json_decode($response->getBody()->getContents(), true);
        });
    }

    private function testVoices(): void
    {
        echo "\n2️⃣ Voices test qilinmoqda...\n";
        
        $voices = $this->runTest('getVoices', function() {
            $response = $this->client->get('/voices');
            return json_decode($response->getBody()->getContents(), true);
        });
        
        // Test individual voice details
        if ($voices && isset($voices['voices'][0])) {
            $voiceId = $voices['voices'][0]['voice_id'];
            echo "  🎭 Birinchi ovoz: {$voices['voices'][0]['name']} ($voiceId)\n";
            
            $this->runTest('getVoiceDetails', function() use ($voiceId) {
                $response = $this->client->get("/voices/$voiceId");
                return json_decode($response->getBody()->getContents(), true);
            });
        }
    }

    private function testModels(): void
    {
        echo "\n3️⃣ Models test qilinmoqda...\n";
        
        $this->runTest('getModels', function() {
            $response = $this->client->get('/models');
            return json_decode($response->getBody()->getContents(), true);
        });
    }

    private function testTextToSpeech(): void
    {
        echo "\n4️⃣ Text-to-Speech test qilinmoqda...\n";
        
        // Get first available voice
        try {
            $voicesResponse = $this->client->get('/voices');
            $voicesData = json_decode($voicesResponse->getBody()->getContents(), true);
            
            if (isset($voicesData['voices'][0])) {
                $voiceId = $voicesData['voices'][0]['voice_id'];
                echo "  🎵 Ishlatilayotgan ovoz: {$voicesData['voices'][0]['name']}\n";
                
                $audioData = $this->runTest('textToSpeech', function() use ($voiceId) {
                    $response = $this->client->post("/text-to-speech/$voiceId", [
                        'json' => [
                            'text' => 'Salom! Bu ElevenLabs API ning test ovozi. Ushbu test muvaffaqiyatli o\'tdi.',
                            'model_id' => 'eleven_multilingual_v2',
                            'voice_settings' => [
                                'stability' => 0.5,
                                'similarity_boost' => 0.5,
                                'style' => 0.0,
                                'use_speaker_boost' => true
                            ]
                        ]
                    ]);
                    return $response->getBody()->getContents();
                });
                
                // Save audio to file
                if ($audioData) {
                    $audioFile = $this->outputDir . '/test_tts_standalone.mp3';
                    if (file_put_contents($audioFile, $audioData)) {
                        echo "  💾 Audio fayl saqlandi: $audioFile\n";
                    }
                }
            }
        } catch (Exception $e) {
            echo "  ❌ TTS test xatoligi: " . $e->getMessage() . "\n";
        }
    }

    private function testCharacterUsage(): void
    {
        echo "\n5️⃣ Character Usage test qilinmoqda...\n";
        
        $usage = $this->runTest('getCharacterUsage', function() {
            $response = $this->client->get('/user/subscription');
            return json_decode($response->getBody()->getContents(), true);
        });
        
        if ($usage) {
            $characterCount = $usage['character_count'] ?? 0;
            $characterLimit = $usage['character_limit'] ?? 0;
            echo "  📊 Belgilar: $characterCount / $characterLimit ishlatilgan\n";
            
            if ($characterLimit > 0) {
                $percentage = round(($characterCount / $characterLimit) * 100, 1);
                echo "  📈 Foiz: $percentage% ishlatilgan\n";
            }
        }
    }

    private function runTest(string $methodName, callable $testFunction)
    {
        try {
            echo "  🧪 $methodName... ";
            $result = $testFunction();
            
            if ($result) {
                echo "✅ Muvaffaqiyatli\n";
                $this->testResults[$methodName] = ['status' => 'success', 'data' => $result];
                return $result;
            } else {
                echo "❌ Bo'sh natija\n";
                $this->testResults[$methodName] = ['status' => 'empty'];
            }
        } catch (GuzzleException $e) {
            $statusCode = $e->getCode();
            $message = $e->getMessage();
            
            if ($statusCode === 401) {
                echo "❌ API Key xato yoki yaroqsiz\n";
            } elseif ($statusCode === 403) {
                echo "❌ Ruxsat berilmagan (plan cheklovlari)\n";
            } elseif ($statusCode === 429) {
                echo "❌ So'rovlar limiti tugagan\n";
            } else {
                echo "❌ HTTP xatolik: $statusCode - $message\n";
            }
            
            $this->testResults[$methodName] = [
                'status' => 'error', 
                'error' => "HTTP $statusCode: $message"
            ];
        } catch (Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "\n";
            $this->testResults[$methodName] = [
                'status' => 'exception', 
                'error' => $e->getMessage()
            ];
        }
        
        return null;
    }

    private function printSummary(): void
    {
        echo "\n📋 TEST NATIJALARI SUMMARY:\n";
        echo str_repeat("=", 50) . "\n";
        
        $successCount = 0;
        $errorCount = 0;
        $exceptionCount = 0;
        
        foreach ($this->testResults as $method => $result) {
            $status = $result['status'];
            $icon = match($status) {
                'success' => '✅',
                'error' => '⚠️',
                'exception' => '❌',
                'empty' => '⚪',
                default => '❓'
            };
            
            echo "$icon $method: " . ucfirst($status);
            
            if (isset($result['error'])) {
                echo " - " . $result['error'];
            }
            
            echo "\n";
            
            match($status) {
                'success' => $successCount++,
                'error' => $errorCount++,
                'exception' => $exceptionCount++,
                default => null
            };
        }
        
        echo str_repeat("=", 50) . "\n";
        echo "📊 JAMI: " . count($this->testResults) . " test\n";
        echo "✅ Muvaffaqiyatli: $successCount\n";
        echo "⚠️  Xatolik: $errorCount\n";
        echo "❌ Exception: $exceptionCount\n";
        
        if ($successCount > 0) {
            echo "\n🎉 Ba'zi testlar muvaffaqiyatli o'tdi!\n";
            echo "📁 Audio fayllar: {$this->outputDir}/\n";
        }
        
        if ($errorCount > 0 || $exceptionCount > 0) {
            echo "\n💡 Maslahat:\n";
            echo "   - API key ni tekshiring\n";
            echo "   - Internet aloqani tekshiring\n";
            echo "   - ElevenLabs hisob tarifingizni tekshiring\n";
        }
    }

    public function testApiKeyValidity(): void
    {
        echo "\n🔑 API Key yaroqliligi test qilinmoqda...\n";
        
        try {
            $response = $this->client->get('/user');
            $userData = json_decode($response->getBody()->getContents(), true);
            
            if (isset($userData['subscription'])) {
                echo "✅ API Key yaroqli!\n";
                echo "👤 User ID: " . ($userData['subscription']['tier'] ?? 'N/A') . "\n";
                echo "📦 Plan: " . ($userData['subscription']['tier'] ?? 'N/A') . "\n";
                
                if (isset($userData['subscription']['character_limit'])) {
                    echo "📊 Belgilar limiti: " . number_format($userData['subscription']['character_limit']) . "\n";
                }
                if (isset($userData['subscription']['character_count'])) {
                    echo "📈 Ishlatilgan: " . number_format($userData['subscription']['character_count']) . "\n";
                }
            } else {
                echo "⚠️  API Key yaroqli, lekin ma'lumot to'liq emas\n";
            }
            
        } catch (GuzzleException $e) {
            $statusCode = $e->getCode();
            
            if ($statusCode === 401) {
                echo "❌ API Key yaroqsiz yoki noto'g'ri!\n";
                echo "   ElevenLabs dashboard dan yangi key oling\n";
            } else {
                echo "❌ Boshqa xatolik: HTTP $statusCode\n";
                echo "   " . $e->getMessage() . "\n";
            }
        }
    }
}

// ============================================================================
// MAIN EXECUTION
// ============================================================================

if (php_sapi_name() === 'cli') {
    echo "ElevenLabs API Standalone Test Script\n";
    echo "====================================\n";
    
    $apiKey = 'sk_ce264428783d15a5cd6577a1128b4048ee11164c1fab436b';
    
    if (empty($apiKey) || $apiKey === 'YOUR_ELEVENLABS_API_KEY_HERE') {
        echo "❌ Xatolik: API key ni sozlang!\n";
        echo "Skript boshida \$apiKey qatorini o'zgartiring.\n";
        exit(1);
    }
    
    echo "🔑 API Key uzunligi: " . strlen($apiKey) . " belgi\n";
    
    $tester = new StandaloneElevenLabsTest($apiKey);
    
    // First check API key validity
    $tester->testApiKeyValidity();
    
    // Then run all tests
    $tester->runAllTests();
    
} else {
    echo "Bu skript faqat CLI dan ishga tushirilishi kerak\n";
}
