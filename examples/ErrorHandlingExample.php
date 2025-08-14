<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Samandar\LaravelElevenLabs\Services\Audio\AudioService;
use Samandar\LaravelElevenLabs\Services\Voice\VoiceService;
use Samandar\LaravelElevenLabs\Exceptions\ElevenLabsException;
use Samandar\LaravelElevenLabs\Exceptions\RateLimitException;
use Samandar\LaravelElevenLabs\Exceptions\ServerErrorException;

/**
 * Example demonstrating the new error handling and retry logic
 */
class ErrorHandlingExample
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Example 1: Basic usage with automatic retry
     */
    public function basicUsageWithRetry()
    {
        echo "=== Example 1: Basic Usage with Automatic Retry ===\n";
        
        $audioService = new AudioService($this->apiKey);
        
        try {
            $result = $audioService->textToSpeech(
                'Hello world!',
                '21m00Tcm4TlvDq8ikWAM' // Rachel voice
            );
            
            if ($result['success']) {
                echo "✅ Text-to-speech successful\n";
                echo "Audio length: " . strlen($result['audio']) . " bytes\n";
            } else {
                echo "❌ Request failed: " . $result['error'] . "\n";
                
                // Check if it's a specific error type
                if (isset($result['exception']) && $result['exception'] instanceof RateLimitException) {
                    $exception = $result['exception'];
                    echo "Rate limited! Retry after: " . $exception->getRetryAfterSeconds() . " seconds\n";
                }
            }
        } catch (Exception $e) {
            echo "❌ Unexpected error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Example 2: Handling different error types
     */
    public function handleDifferentErrorTypes()
    {
        echo "\n=== Example 2: Handling Different Error Types ===\n";
        
        $voiceService = new VoiceService($this->apiKey);
        
        $result = $voiceService->getVoices();
        
        if (!$result['success']) {
            if (isset($result['exception'])) {
                $exception = $result['exception'];
                
                if ($exception instanceof RateLimitException) {
                    echo "🚫 Rate Limited (429)\n";
                    echo "Retry after: " . $exception->getRetryAfterSeconds() . " seconds\n";
                    echo "Status: " . $exception->getStatusCode() . "\n";
                    
                } elseif ($exception instanceof ServerErrorException) {
                    echo "🔥 Server Error (5xx)\n";
                    echo "Status: " . $exception->getStatusCode() . "\n";
                    echo "This error was retried automatically\n";
                    
                } elseif ($exception instanceof ElevenLabsException) {
                    if ($exception->isClientError()) {
                        echo "⚠️  Client Error (4xx)\n";
                        echo "This error was NOT retried (client errors are not retryable)\n";
                    } else {
                        echo "❓ Other Error\n";
                    }
                    echo "Status: " . $exception->getStatusCode() . "\n";
                }
                
                // Get additional error details
                if ($exception->getResponseBody()) {
                    echo "Response body: " . $exception->getResponseBody() . "\n";
                }
                
                if ($exception->getErrorData()) {
                    echo "Error data: " . json_encode($exception->getErrorData()) . "\n";
                }
            } else {
                echo "❌ Generic error: " . $result['error'] . "\n";
            }
        } else {
            echo "✅ Successfully retrieved " . count($result['data']['voices'] ?? []) . " voices\n";
        }
    }

    /**
     * Example 3: Configuration-based retry settings
     */
    public function demonstrateConfiguration()
    {
        echo "\n=== Example 3: Configuration Settings ===\n";
        echo "The following environment variables control retry and logging behavior:\n\n";
        
        $configs = [
            'ELEVENLABS_RETRY_ENABLED' => 'Enable/disable retry logic (default: true)',
            'ELEVENLABS_RETRY_MAX_ATTEMPTS' => 'Maximum retry attempts (default: 3)',
            'ELEVENLABS_RETRY_BASE_DELAY_MS' => 'Base delay in milliseconds (default: 1000)',
            'ELEVENLABS_RETRY_MAX_DELAY_MS' => 'Maximum delay in milliseconds (default: 60000)',
            'ELEVENLABS_RETRY_RESPECT_RETRY_AFTER' => 'Respect Retry-After header (default: true)',
            'ELEVENLABS_RETRY_USE_JITTER' => 'Use jitter in backoff (default: true)',
            'ELEVENLABS_LOGGING_ENABLED' => 'Enable logging (default: true)',
            'ELEVENLABS_LOG_REQUESTS' => 'Log HTTP requests (default: false)',
            'ELEVENLABS_LOG_RESPONSES' => 'Log HTTP responses (default: true)',
            'ELEVENLABS_LOG_RETRIES' => 'Log retry attempts (default: true)',
        ];
        
        foreach ($configs as $key => $description) {
            $value = $_ENV[$key] ?? 'not set';
            echo "{$key}={$value}\n  → {$description}\n\n";
        }
    }

    /**
     * Example 4: Log file location
     */
    public function showLogLocation()
    {
        echo "=== Example 4: Log File Location ===\n";
        
        $logPath = __DIR__ . '/../storage/logs/elevenlabs.log';
        echo "ElevenLabs logs are written to: {$logPath}\n";
        
        if (file_exists($logPath)) {
            echo "Log file exists, size: " . filesize($logPath) . " bytes\n";
            
            // Show last few lines
            $lines = file($logPath);
            if ($lines && count($lines) > 0) {
                echo "Last log entry:\n";
                echo end($lines);
            }
        } else {
            echo "Log file doesn't exist yet (will be created on first use)\n";
        }
    }

    /**
     * Run all examples
     */
    public function runAll()
    {
        $this->basicUsageWithRetry();
        $this->handleDifferentErrorTypes();
        $this->demonstrateConfiguration();
        $this->showLogLocation();
    }
}

// Usage example
if (isset($argv[1])) {
    $apiKey = $argv[1];
    $example = new ErrorHandlingExample($apiKey);
    $example->runAll();
} else {
    echo "Usage: php ErrorHandlingExample.php YOUR_API_KEY\n";
    echo "Note: This is a demonstration. With invalid API key, you'll see error handling in action.\n";
}
