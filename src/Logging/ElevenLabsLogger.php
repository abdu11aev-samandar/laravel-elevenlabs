<?php

namespace Samandar\LaravelElevenLabs\Logging;

use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerInterface;

/**
 * Specialized logger for ElevenLabs operations
 */
class ElevenLabsLogger
{
    private LoggerInterface $logger;
    private bool $enabled;
    private array $sensitiveFields;

    public function __construct(?LoggerInterface $logger = null, bool $enabled = true)
    {
        $this->enabled = $enabled;
        $this->sensitiveFields = [
            'xi-api-key',
            'api_key',
            'apikey',
            'authorization',
            'token',
            'secret',
            'password',
        ];

        if ($logger) {
            $this->logger = $logger;
        } else {
            $this->logger = $this->createDefaultLogger();
        }
    }

    /**
     * Create default logger that writes to storage/logs/elevenlabs.log
     */
    private function createDefaultLogger(): LoggerInterface
    {
        try {
            // Always use fallback path for now to avoid Laravel dependency issues
            $logPath = __DIR__ . '/../../storage/logs/elevenlabs.log';
            
            // Ensure directory exists
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $logger = new Logger('elevenlabs');
            $handler = new RotatingFileHandler($logPath, 0, Logger::DEBUG);
            
            // Custom format for better readability
            $formatter = new LineFormatter(
                "[%datetime%] %level_name%: %message% %context% %extra%\n",
                'Y-m-d H:i:s',
                true,
                true
            );
            $handler->setFormatter($formatter);
            
            $logger->pushHandler($handler);
            return $logger;
        } catch (\Exception $e) {
            // Fallback to Laravel log if available, or null logger
            try {
                if (class_exists('\\Illuminate\\Support\\Facades\\Log') && function_exists('app') && app()->bound('log')) {
                    return Log::channel('single');
                }
            } catch (\Exception $laravelException) {
                // Laravel not available
            }
            
            // Return a null logger that does nothing
            return new \Psr\Log\NullLogger();
        }
    }

    /**
     * Log HTTP request details
     */
    public function logRequest(
        string $method,
        string $endpoint,
        array $headers = [],
        ?array $body = null,
        ?float $startTime = null
    ): void {
        if (!$this->enabled) {
            return;
        }

        $context = [
            'method' => $method,
            'endpoint' => $endpoint,
            'headers' => $this->sanitizeData($headers),
        ];

        if ($body !== null) {
            $context['body'] = $this->sanitizeData($body);
        }

        if ($startTime !== null) {
            $context['request_time'] = microtime(true);
        }

        $this->logger->info("ElevenLabs API Request: {$method} {$endpoint}", $context);
    }

    /**
     * Log HTTP response details
     */
    public function logResponse(
        string $method,
        string $endpoint,
        int $statusCode,
        ?array $responseHeaders = null,
        ?string $responseBody = null,
        ?float $startTime = null
    ): void {
        if (!$this->enabled) {
            return;
        }

        $context = [
            'method' => $method,
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
        ];

        if ($responseHeaders !== null) {
            $context['response_headers'] = $this->sanitizeData($responseHeaders);
        }

        // Only log response body for errors or if it's small
        if ($responseBody !== null && (
            $statusCode >= 400 || 
            strlen($responseBody) < 1000
        )) {
            $context['response_body'] = $this->sanitizeResponseBody($responseBody);
        }

        if ($startTime !== null) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $context['duration_ms'] = $duration;
        }

        $level = $statusCode >= 400 ? 'error' : 'info';
        $this->logger->log(
            $level,
            "ElevenLabs API Response: {$method} {$endpoint} [{$statusCode}]",
            $context
        );
    }

    /**
     * Log retry attempt
     */
    public function logRetry(
        int $attempt,
        int $maxAttempts,
        string $context,
        string $error,
        int $delayMs
    ): void {
        if (!$this->enabled) {
            return;
        }

        $this->logger->warning(
            "ElevenLabs API Retry: Attempt {$attempt}/{$maxAttempts} for {$context}",
            [
                'attempt' => $attempt,
                'max_attempts' => $maxAttempts,
                'context' => $context,
                'error' => $error,
                'delay_ms' => $delayMs,
            ]
        );
    }

    /**
     * Log retry exhaustion
     */
    public function logRetryExhausted(string $context, int $totalAttempts, string $finalError): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->logger->error(
            "ElevenLabs API Retry Exhausted: All {$totalAttempts} attempts failed for {$context}",
            [
                'context' => $context,
                'total_attempts' => $totalAttempts,
                'final_error' => $finalError,
            ]
        );
    }

    /**
     * Log rate limit information
     */
    public function logRateLimit(
        string $endpoint,
        ?int $retryAfter = null,
        ?array $rateLimitHeaders = null
    ): void {
        if (!$this->enabled) {
            return;
        }

        $context = [
            'endpoint' => $endpoint,
            'retry_after' => $retryAfter,
        ];

        if ($rateLimitHeaders) {
            $context['rate_limit_headers'] = $rateLimitHeaders;
        }

        $this->logger->warning("ElevenLabs API Rate Limited: {$endpoint}", $context);
    }

    /**
     * Log general error
     */
    public function logError(string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->logger->error("ElevenLabs API Error: {$message}", $this->sanitizeData($context));
    }

    /**
     * Log general info
     */
    public function logInfo(string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->logger->info("ElevenLabs API Info: {$message}", $this->sanitizeData($context));
    }

    /**
     * Sanitize sensitive data from arrays
     */
    private function sanitizeData(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            
            // Check if key is sensitive
            $isSensitive = false;
            foreach ($this->sensitiveFields as $sensitiveField) {
                if (strpos($lowerKey, $sensitiveField) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            if ($isSensitive) {
                if (is_string($value) && strlen($value) > 8) {
                    $sanitized[$key] = substr($value, 0, 4) . '***' . substr($value, -4);
                } else {
                    $sanitized[$key] = '***';
                }
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize response body (truncate if too long, mask binary data)
     */
    private function sanitizeResponseBody(string $body): string
    {
        // Check if it's binary data
        if (!mb_check_encoding($body, 'UTF-8')) {
            return '[Binary data - ' . strlen($body) . ' bytes]';
        }

        // Truncate long responses
        if (strlen($body) > 5000) {
            return substr($body, 0, 5000) . '... [truncated]';
        }

        return $body;
    }

    /**
     * Enable or disable logging
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Check if logging is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get the underlying logger instance
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
