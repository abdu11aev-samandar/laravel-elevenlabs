<?php

/**
 * Simple ElevenLabs Package Test
 * 
 * Bu test Laravel config sistemini aylanib o'tib, to'g'ridan-to'g'ri
 * API bilan ishlaydi
 */

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

// Manual config simulation for BaseElevenLabsService
if (!function_exists('config')) {
    function config($key, $default = null) {
        $configs = [
            'elevenlabs.base_uri' => 'https://api.elevenlabs.io/v1/',
            'elevenlabs.timeout' => 30,
        ];
        return $configs[$key] ?? $default;
    }
}

// Test with package classes
use Samandar\LaravelElevenLabs\Services\ElevenLabsService;

class SimplePackageTest
{
    private string $apiKey;
    private ElevenLabsService $service;
    private array $testResults = [];

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function runTests(): void
    {
        echo "🚀 ElevenLabs Laravel Package Test\n";
        echo "===================================\n\n";

        try {
            // Initialize service
            $this->service = new ElevenLabsService($this->apiKey);
            echo "✅ Service initialized successfully\n\n";

            // Test Analytics Service
            $this->testAnalytics();
            
            // Test Voice Service  
            $this->testVoices();
            
            // Test Audio Service
            $this->testAudio();
            
        } catch (Exception $e) {
            echo "❌ Service initialization failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            return;
        }

        $this->printSummary();
    }

    private function testAnalytics(): void
    {
        echo "📊 Analytics Service Test\n";
        echo "-------------------------\n";

        // Test getUserInfo
        $this->runTest('getUserInfo', function() {
            return $this->service->analytics()->getUserInfo();
        });

        // Test getCharacterUsage
        $this->runTest('getCharacterUsage', function() {
            return $this->service->analytics()->getCharacterUsage();
        });

        // Test getModels
        $this->runTest('getModels', function() {
            return $this->service->analytics()->getModels();
        });

        echo "\n";
    }

    private function testVoices(): void
    {
        echo "🎭 Voice Service Test\n";
        echo "----------------------\n";

        // Test getVoices
        $voices = $this->runTest('getVoices', function() {
            return $this->service->voice()->getVoices();
        });

        // Test getVoice with first available voice
        if ($voices && isset($voices['voices'][0])) {
            $voiceId = $voices['voices'][0]['voice_id'];
            echo "  🎵 Using voice: {$voices['voices'][0]['name']} ($voiceId)\n";
            
            $this->runTest('getVoice', function() use ($voiceId) {
                return $this->service->voice()->getVoice($voiceId);
            });
        }

        // Test getSharedVoices
        $this->runTest('getSharedVoices', function() {
            return $this->service->voice()->getSharedVoices();
        });

        echo "\n";
    }

    private function testAudio(): void
    {
        echo "🎵 Audio Service Test\n";
        echo "---------------------\n";

        // Get first available voice for TTS test
        try {
            $voicesResult = $this->service->voice()->getVoices();
            if ($voicesResult && isset($voicesResult['voices'][0])) {
                $voiceId = $voicesResult['voices'][0]['voice_id'];
                $voiceName = $voicesResult['voices'][0]['name'];
                echo "  🎤 Using voice for TTS: $voiceName\n";

                // Test Text-to-Speech
                $ttsResult = $this->runTest('textToSpeech', function() use ($voiceId) {
                    return $this->service->audio()->textToSpeech(
                        'Salom! Bu Laravel ElevenLabs package ning test ovozi. Test muvaffaqiyatli o\'tdi!',
                        $voiceId
                    );
                });

                // Save audio to file if successful
                if ($ttsResult && isset($ttsResult['audio'])) {
                    $outputDir = __DIR__ . '/test_output';
                    if (!is_dir($outputDir)) {
                        mkdir($outputDir, 0755, true);
                    }

                    $audioFile = $outputDir . '/laravel_package_test.mp3';
                    $saved = $this->service->audio()->saveAudioToFile($ttsResult['audio'], $audioFile);
                    
                    if ($saved) {
                        echo "  💾 Audio saved: $audioFile\n";
                    }
                }

                // Test textToSpeechAndSave (combined method)
                $this->runTest('textToSpeechAndSave', function() use ($voiceId) {
                    $filePath = __DIR__ . '/test_output/combined_test.mp3';
                    return $this->service->audio()->textToSpeechAndSave(
                        'Bu kombinatsiyalangan usul bilan yaratilgan test ovozi.',
                        $filePath,
                        $voiceId
                    );
                });
            }
        } catch (Exception $e) {
            echo "  ❌ Audio test setup failed: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    private function runTest(string $methodName, callable $testFunction)
    {
        try {
            echo "  🧪 Testing $methodName... ";
            $result = $testFunction();
            
            if (is_array($result) && isset($result['success']) && $result['success']) {
                echo "✅ Success\n";
                $this->testResults[$methodName] = ['status' => 'success', 'data' => $result];
                return $result;
            } elseif (is_array($result) && isset($result['error'])) {
                echo "❌ Error: " . $result['error'] . "\n";
                $this->testResults[$methodName] = ['status' => 'error', 'error' => $result['error']];
            } else {
                echo "✅ Completed\n";
                $this->testResults[$methodName] = ['status' => 'completed', 'data' => $result];
                return $result;
            }
        } catch (Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "\n";
            $this->testResults[$methodName] = ['status' => 'exception', 'error' => $e->getMessage()];
        }
        
        return null;
    }

    private function printSummary(): void
    {
        echo "📋 TEST SUMMARY\n";
        echo str_repeat("=", 50) . "\n";
        
        $successCount = 0;
        $errorCount = 0;
        $exceptionCount = 0;
        
        foreach ($this->testResults as $method => $result) {
            $status = $result['status'];
            $icon = match($status) {
                'success', 'completed' => '✅',
                'error' => '⚠️',
                'exception' => '❌',
                default => '❓'
            };
            
            echo "$icon $method: " . ucfirst($status);
            
            if (isset($result['error'])) {
                echo " - " . $result['error'];
            }
            
            echo "\n";
            
            match($status) {
                'success', 'completed' => $successCount++,
                'error' => $errorCount++,
                'exception' => $exceptionCount++,
                default => null
            };
        }
        
        echo str_repeat("=", 50) . "\n";
        echo "📊 Total Tests: " . count($this->testResults) . "\n";
        echo "✅ Successful: $successCount\n";
        echo "⚠️  Errors: $errorCount\n";
        echo "❌ Exceptions: $exceptionCount\n";
        
        if ($successCount > 0) {
            echo "\n🎉 Some tests passed successfully!\n";
            echo "📁 Output files: " . __DIR__ . "/test_output/\n";
        }
        
        if ($errorCount > 0 || $exceptionCount > 0) {
            echo "\n💡 Tips:\n";
            echo "   - Check your API key validity\n";
            echo "   - Verify your ElevenLabs subscription plan\n";
            echo "   - Check internet connection\n";
        }
    }
}

// Run the test
if (php_sapi_name() === 'cli') {
    $apiKey = 'sk_ce264428783d15a5cd6577a1128b4048ee11164c1fab436b';
    
    if (empty($apiKey) || $apiKey === 'YOUR_API_KEY_HERE') {
        echo "❌ Please set your API key\n";
        exit(1);
    }
    
    echo "🔑 API Key length: " . strlen($apiKey) . " characters\n\n";
    
    $test = new SimplePackageTest($apiKey);
    $test->runTests();
} else {
    echo "This script should be run from command line\n";
}
