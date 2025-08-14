<?php

namespace Samandar\LaravelElevenLabs\Services\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Samandar\LaravelElevenLabs\Http\RetryHandler;
use Samandar\LaravelElevenLabs\Logging\ElevenLabsLogger;
use Samandar\LaravelElevenLabs\Exceptions\ElevenLabsException;
use Samandar\LaravelElevenLabs\Exceptions\RateLimitException;
use Samandar\LaravelElevenLabs\Exceptions\ServerErrorException;
use Samandar\LaravelElevenLabs\Exceptions\ClientErrorException;
use Illuminate\Support\Facades\Log;

abstract class BaseElevenLabsService
{
    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl = 'https://api.elevenlabs.io/v1/';
    protected RetryHandler $retryHandler;
    protected ElevenLabsLogger $logger;
    protected bool $retryEnabled = true;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        
        // Try to use Laravel config if available, otherwise use defaults
        try {
            if (function_exists('config') && app()->bound('config')) {
                $this->baseUrl = config('elevenlabs.base_uri', $this->baseUrl);
                $timeout = (int) config('elevenlabs.timeout', 30);
                
                // Initialize retry configuration
                $retryConfig = config('elevenlabs.retry', []);
                $this->retryEnabled = $retryConfig['enabled'] ?? true;
                
                // Initialize logging configuration
                $loggingConfig = config('elevenlabs.logging', []);
                $loggingEnabled = $loggingConfig['enabled'] ?? true;
            } else {
                $timeout = 30;
                $retryConfig = [];
                $loggingConfig = [];
                $loggingEnabled = true;
            }
        } catch (\Throwable $e) {
            // If config is not available (standalone usage), use defaults
            $timeout = 30;
            $retryConfig = [];
            $loggingConfig = [];
            $loggingEnabled = true;
        }
        
        // Initialize logger
        $this->logger = new ElevenLabsLogger(null, $loggingEnabled);
        
        // Initialize retry handler
        $this->retryHandler = new RetryHandler(
            maxAttempts: $retryConfig['max_attempts'] ?? 3,
            baseDelayMs: $retryConfig['base_delay_ms'] ?? 1000,
            maxDelayMs: $retryConfig['max_delay_ms'] ?? 60000,
            respectRetryAfter: $retryConfig['respect_retry_after'] ?? true,
            useJitter: $retryConfig['use_jitter'] ?? true,
            logger: $this->logger->getLogger()
        );
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'xi-api-key' => $this->apiKey,
            ],
            'timeout' => $timeout,
        ]);
    }

    /**
     * Execute HTTP request with centralized error handling and retry logic
     * Returns the PSR-7 ResponseInterface on success, or array with error info on failure
     */
    protected function executeRequest(callable $requestCallable, string $method, string $endpoint, array $options = [])
    {
        $startTime = microtime(true);
        $context = "{$method} {$endpoint}";
        
        // Log request if enabled
        $this->logger->logRequest($method, $endpoint, $this->client->getConfig('headers') ?? [], $options, $startTime);
        
        try {
            if ($this->retryEnabled) {
                // Execute with retry logic
                $response = $this->retryHandler->execute($requestCallable, $context);
            } else {
                // Execute without retry
                $response = $requestCallable();
            }
            
            // Log successful response
            $this->logger->logResponse(
                $method, 
                $endpoint, 
                $response->getStatusCode(), 
                $response->getHeaders(),
                null, // Don't log body for success unless it's small
                $startTime
            );
            
            return $response;
            
        } catch (ElevenLabsException $e) {
            // Log the error
            $this->logger->logResponse(
                $method,
                $endpoint,
                $e->getStatusCode() ?? 0,
                null,
                $e->getResponseBody(),
                $startTime
            );
            
            // Convert back to array format for backward compatibility
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getStatusCode() ?? $e->getCode(),
                'exception' => $e, // Include the exception for advanced error handling
            ];
        } catch (\Exception $e) {
            // Handle any other exceptions
            $this->logger->logError("Unexpected error in {$context}: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }

    /**
     * Make a GET request
     */
    protected function get(string $endpoint): array
    {
        $response = $this->executeRequest(
            fn() => $this->client->get($endpoint),
            'GET',
            $endpoint
        );
        
        // If it's an error array, return it directly
        if (is_array($response) && isset($response['success']) && !$response['success']) {
            return $response;
        }
        
        $data = json_decode($response->getBody()->getContents(), true);
        
        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * Make a POST request
     */
    protected function post(string $endpoint, array $data = []): array
    {
        $response = $this->executeRequest(
            fn() => $this->client->post($endpoint, $data),
            'POST',
            $endpoint,
            $data
        );
        
        // If it's an error array, return it directly
        if (is_array($response) && isset($response['success']) && !$response['success']) {
            return $response;
        }
        
        $responseData = json_decode($response->getBody()->getContents(), true);
        
        return [
            'success' => true,
            'data' => $responseData,
            'headers' => $response->getHeaders(),
        ];
    }

    /**
     * Make a PATCH request
     */
    protected function patch(string $endpoint, array $data = []): array
    {
        $response = $this->executeRequest(
            fn() => $this->client->patch($endpoint, $data),
            'PATCH',
            $endpoint,
            $data
        );
        
        // If it's an error array, return it directly
        if (is_array($response) && isset($response['success']) && !$response['success']) {
            return $response;
        }
        
        return ['success' => true];
    }

    /**
     * Make a DELETE request
     */
    protected function delete(string $endpoint): array
    {
        $response = $this->executeRequest(
            fn() => $this->client->delete($endpoint),
            'DELETE',
            $endpoint
        );
        
        // If it's an error array, return it directly
        if (is_array($response) && isset($response['success']) && !$response['success']) {
            return $response;
        }
        
        return ['success' => true];
    }

    /**
     * Make a POST request and return binary data
     */
    protected function postBinary(string $endpoint, array $data = []): array
    {
        $response = $this->executeRequest(
            fn() => $this->client->post($endpoint, $data),
            'POST',
            $endpoint,
            $data
        );
        
        // If it's an error array, return it directly
        if (is_array($response) && isset($response['success']) && !$response['success']) {
            return $response;
        }
        
        return [
            'success' => true,
            'data' => $response->getBody()->getContents(),
            'content_type' => $response->getHeader('Content-Type')[0] ?? 'audio/mpeg',
        ];
    }

    /**
     * Make a GET request and return binary data
     */
    protected function getBinary(string $endpoint): array
    {
        $response = $this->executeRequest(
            fn() => $this->client->get($endpoint),
            'GET',
            $endpoint
        );
        
        // If it's an error array, return it directly
        if (is_array($response) && isset($response['success']) && !$response['success']) {
            return $response;
        }
        
        return [
            'success' => true,
            'data' => $response->getBody()->getContents(),
            'content_type' => $response->getHeader('Content-Type')[0] ?? 'application/octet-stream',
        ];
    }

    /**
     * Get config value, using Laravel config if available, otherwise return default
     */
    protected function getConfig(string $key, $default = null)
    {
        try {
            if (function_exists('config') && app()->bound('config')) {
                return config($key, $default);
            } else {
                return $default;
            }
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Get the logger instance
     */
    protected function getLogger(): ElevenLabsLogger
    {
        return $this->logger;
    }

    /**
     * Get the retry handler instance
     */
    protected function getRetryHandler(): RetryHandler
    {
        return $this->retryHandler;
    }
}
