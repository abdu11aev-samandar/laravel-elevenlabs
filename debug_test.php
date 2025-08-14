<?php

/**
 * Debug ElevenLabs Package Test
 * 
 * Bu test aniq qaysi URL ga so'rov yuborilayotganini ko'rsatadi
 */

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Exception\RequestException;

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

class DebugPackageTest
{
    private string $apiKey;
    private ElevenLabsService $service;
    private array $requestUrls = [];

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function runDebugTest(): void
    {
        echo "🔍 ElevenLabs Package Debug Test\n";
        echo "=================================\n\n";

        try {
            // Initialize service
            $this->service = new ElevenLabsService($this->apiKey);
            echo "✅ Service initialized successfully\n";
            echo "🔧 Base URL configured: https://api.elevenlabs.io/v1\n\n";

            // Test one endpoint with full logging
            echo "📊 Testing getUserInfo endpoint...\n";
            
            $result = $this->service->analytics()->getUserInfo();
            
            echo "📤 Request completed\n";
            echo "📋 Result:\n";
            if (is_array($result)) {
                if (isset($result['success'])) {
                    echo "  Success: " . ($result['success'] ? 'true' : 'false') . "\n";
                }
                if (isset($result['error'])) {
                    echo "  Error: " . $result['error'] . "\n";
                }
            }
            
            // Let's also test a direct Guzzle call to compare
            echo "\n🔗 Testing direct Guzzle call...\n";
            $client = new Client();
            
            try {
                $response = $client->get('https://api.elevenlabs.io/v1/user', [
                    'headers' => [
                        'xi-api-key' => $this->apiKey,
                    ]
                ]);
                
                echo "  Direct call status: " . $response->getStatusCode() . "\n";
                echo "  Response type: " . $response->getHeaderLine('content-type') . "\n";
                
            } catch (Exception $e) {
                echo "  Direct call error: " . $e->getMessage() . "\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Service initialization failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            return;
        }
    }
    
    public function testWithMockHandler(): void
    {
        echo "\n🎭 Testing with Mock Handler to capture requests...\n";
        
        // Create a mock handler to capture requests
        $container = [];
        $history = Middleware::history($container);
        
        $mock = new MockHandler([
            new RequestException("Mock error", new \GuzzleHttp\Psr7\Request('GET', 'test'))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        
        try {
            // We can't easily inject this into the existing service, so let's create our own client
            $testClient = new Client(['handler' => $handlerStack]);
            
            try {
                $testClient->get('https://api.elevenlabs.io/v1/user', [
                    'headers' => ['xi-api-key' => $this->apiKey]
                ]);
            } catch (Exception $e) {
                // Expected to fail with mock
            }
            
            if (!empty($container)) {
                $request = $container[0]['request'];
                echo "📝 Captured request URL: " . $request->getUri() . "\n";
                echo "📝 Request method: " . $request->getMethod() . "\n";
                echo "📝 Request headers:\n";
                foreach ($request->getHeaders() as $name => $values) {
                    echo "     $name: " . implode(', ', $values) . "\n";
                }
            }
            
        } catch (Exception $e) {
            echo "Mock test error: " . $e->getMessage() . "\n";
        }
    }
}

// Run the test
if (php_sapi_name() === 'cli') {
    $apiKey = 'sk_ce264428783d15a5cd6577a1128b4048ee11164c1fab436b';
    
    echo "🔑 API Key length: " . strlen($apiKey) . " characters\n\n";
    
    $test = new DebugPackageTest($apiKey);
    $test->runDebugTest();
    $test->testWithMockHandler();
    
} else {
    echo "This script should be run from command line\n";
}
