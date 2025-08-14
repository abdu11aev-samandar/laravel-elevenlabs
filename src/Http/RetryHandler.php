<?php

namespace Samandar\LaravelElevenLabs\Http;

use Samandar\LaravelElevenLabs\Exceptions\ElevenLabsException;
use Samandar\LaravelElevenLabs\Exceptions\RateLimitException;
use Samandar\LaravelElevenLabs\Exceptions\ServerErrorException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Handles retry logic with exponential backoff for ElevenLabs API requests
 */
class RetryHandler
{
    private int $maxAttempts;
    private int $baseDelayMs;
    private int $maxDelayMs;
    private bool $respectRetryAfter;
    private bool $useJitter;
    private ?LoggerInterface $logger;

    public function __construct(
        int $maxAttempts = 3,
        int $baseDelayMs = 1000,
        int $maxDelayMs = 60000,
        bool $respectRetryAfter = true,
        bool $useJitter = true,
        ?LoggerInterface $logger = null
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->baseDelayMs = $baseDelayMs;
        $this->maxDelayMs = $maxDelayMs;
        $this->respectRetryAfter = $respectRetryAfter;
        $this->useJitter = $useJitter;
        $this->logger = $logger;
    }

    /**
     * Execute a callable with retry logic
     *
     * @param callable $callable The function to execute
     * @param string $context Context for logging (e.g., "GET /voices")
     * @return mixed
     * @throws ElevenLabsException
     */
    public function execute(callable $callable, string $context = 'HTTP request')
    {
        $attempt = 1;
        $lastException = null;

        while ($attempt <= $this->maxAttempts) {
            try {
                $this->log('debug', "Attempt {$attempt}/{$this->maxAttempts} for {$context}");
                return $callable();
            } catch (GuzzleException $e) {
                $lastException = $e;
                
                // Convert to our exception type
                $elevenLabsException = $this->convertException($e);
                
                // Check if we should retry
                if (!$this->shouldRetry($elevenLabsException, $attempt)) {
                    throw $elevenLabsException;
                }

                // Calculate delay
                $delayMs = $this->calculateDelay($elevenLabsException, $attempt);
                
                $this->log('warning', 
                    "Attempt {$attempt} failed for {$context}: {$e->getMessage()}. " .
                    "Retrying in {$delayMs}ms"
                );

                // Wait before retry
                usleep($delayMs * 1000);
                $attempt++;
            }
        }

        // If we get here, all attempts failed
        $elevenLabsException = $this->convertException($lastException);
        $this->log('error', "All {$this->maxAttempts} attempts failed for {$context}");
        
        throw $elevenLabsException;
    }

    /**
     * Convert Guzzle exception to ElevenLabs exception
     */
    private function convertException(GuzzleException $e): ElevenLabsException
    {
        $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;
        $statusCode = $response ? $response->getStatusCode() : 0;

        // Parse error data from response
        $errorData = $this->parseErrorData($response);

        switch ($statusCode) {
            case 429:
                return new RateLimitException(
                    $e->getMessage(),
                    $statusCode,
                    $e,
                    $response,
                    $e,
                    $errorData
                );
            
            case 500:
            case 502:
            case 503:
            case 504:
                return new ServerErrorException(
                    $e->getMessage(),
                    $statusCode,
                    $e,
                    $response,
                    $e,
                    $errorData
                );
            
            default:
                return new ElevenLabsException(
                    $e->getMessage(),
                    $statusCode ?: $e->getCode(),
                    $e,
                    $response,
                    $e,
                    $errorData
                );
        }
    }

    /**
     * Parse error data from API response
     */
    private function parseErrorData(?ResponseInterface $response): ?array
    {
        if (!$response) {
            return null;
        }

        try {
            $body = $response->getBody();
            $body->rewind();
            $content = $body->getContents();
            
            return json_decode($content, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Determine if we should retry the request
     */
    private function shouldRetry(ElevenLabsException $exception, int $attempt): bool
    {
        // Don't retry if we've reached max attempts
        if ($attempt >= $this->maxAttempts) {
            return false;
        }

        // Only retry for retryable errors (429, 5xx)
        return $exception->isRetryable();
    }

    /**
     * Calculate delay for next attempt using exponential backoff
     */
    private function calculateDelay(ElevenLabsException $exception, int $attempt): int
    {
        // Respect Retry-After header if present and configured to do so
        if ($this->respectRetryAfter && $exception->isRateLimited()) {
            $retryAfter = $exception->getRetryAfterSeconds();
            if ($retryAfter !== null) {
                $retryAfterMs = $retryAfter * 1000;
                return min($retryAfterMs, $this->maxDelayMs);
            }
        }

        // Exponential backoff: base_delay * (2 ^ (attempt - 1))
        $delayMs = $this->baseDelayMs * (2 ** ($attempt - 1));
        
        // Add jitter to prevent thundering herd
        if ($this->useJitter) {
            $jitterRange = $delayMs * 0.1; // 10% jitter
            $jitter = mt_rand(-$jitterRange, $jitterRange);
            $delayMs += $jitter;
        }

        // Cap at max delay
        return min($delayMs, $this->maxDelayMs);
    }

    /**
     * Log a message if logger is available
     */
    private function log(string $level, string $message): void
    {
        if ($this->logger) {
            $this->logger->log($level, "[ElevenLabs RetryHandler] {$message}");
        }
    }
}
