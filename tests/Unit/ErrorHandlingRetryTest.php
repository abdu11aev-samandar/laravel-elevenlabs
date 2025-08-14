<?php

namespace Samandar\LaravelElevenLabs\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\Audio\AudioService;
use Samandar\LaravelElevenLabs\Http\RetryHandler;
use Samandar\LaravelElevenLabs\Logging\ElevenLabsLogger;
use Samandar\LaravelElevenLabs\Exceptions\ElevenLabsException;
use Samandar\LaravelElevenLabs\Exceptions\RateLimitException;
use Samandar\LaravelElevenLabs\Exceptions\ServerErrorException;
use Samandar\LaravelElevenLabs\Exceptions\ClientErrorException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use Mockery;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ErrorHandlingRetryTest extends TestCase
{
    protected $mockClient;
    protected $apiKey = 'test-api-key';
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock(Client::class);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    protected function injectMockClient($service)
    {
        $reflection = new \ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($service, $this->mockClient);
        return $service;
    }

    public function testRetryHandlerWithRateLimitException()
    {
        $attempts = 0;
        $retryHandler = new RetryHandler(
            maxAttempts: 3,
            baseDelayMs: 100, // Shorter for testing
            maxDelayMs: 1000,
            respectRetryAfter: true,
            useJitter: false // Disable jitter for predictable testing
        );

        $callable = function() use (&$attempts) {
            $attempts++;
            
            if ($attempts < 3) {
                // Mock a 429 response with Retry-After header
                $request = new Request('GET', '/test');
                $response = new Response(429, ['Retry-After' => '1'], 'Rate limited');
                throw new ClientException('Rate limited', $request, $response);
            }
            
            // Success on third attempt
            return new Response(200, [], '{"success": true}');
        };

        $result = $retryHandler->execute($callable, 'Test request');
        
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals(3, $attempts);
    }

    public function testRetryHandlerExhaustsMaxAttempts()
    {
        $attempts = 0;
        $retryHandler = new RetryHandler(
            maxAttempts: 2,
            baseDelayMs: 1, // Very short for testing
            maxDelayMs: 10,
            respectRetryAfter: false,
            useJitter: false
        );

        $callable = function() use (&$attempts) {
            $attempts++;
            $request = new Request('GET', '/test');
            $response = new Response(500, [], 'Internal Server Error');
            throw new ServerException('Server error', $request, $response);
        };

        $this->expectException(ServerErrorException::class);
        
        try {
            $retryHandler->execute($callable, 'Test request');
        } catch (ServerErrorException $e) {
            $this->assertEquals(2, $attempts);
            throw $e;
        }
    }

    public function testRetryHandlerDoesNotRetryClientErrors()
    {
        $attempts = 0;
        $retryHandler = new RetryHandler(maxAttempts: 3);

        $callable = function() use (&$attempts) {
            $attempts++;
            $request = new Request('GET', '/test');
            $response = new Response(400, [], 'Bad Request');
            throw new ClientException('Bad request', $request, $response);
        };

        $this->expectException(ElevenLabsException::class);
        
        try {
            $retryHandler->execute($callable, 'Test request');
        } catch (ElevenLabsException $e) {
            $this->assertEquals(1, $attempts); // Should not retry
            throw $e;
        }
    }

    public function testExponentialBackoffCalculation()
    {
        $retryHandler = new RetryHandler(
            maxAttempts: 4,
            baseDelayMs: 1000,
            maxDelayMs: 10000,
            respectRetryAfter: false,
            useJitter: false
        );

        $reflection = new \ReflectionClass($retryHandler);
        $calculateDelayMethod = $reflection->getMethod('calculateDelay');
        $calculateDelayMethod->setAccessible(true);

        // Create mock exception
        $request = new Request('GET', '/test');
        $response = new Response(500, [], 'Server error');
        $guzzleException = new ServerException('Server error', $request, $response);
        
        $convertExceptionMethod = $reflection->getMethod('convertException');
        $convertExceptionMethod->setAccessible(true);
        $exception = $convertExceptionMethod->invoke($retryHandler, $guzzleException);

        // Test exponential backoff: 1000, 2000, 4000
        $delay1 = $calculateDelayMethod->invoke($retryHandler, $exception, 1);
        $delay2 = $calculateDelayMethod->invoke($retryHandler, $exception, 2);
        $delay3 = $calculateDelayMethod->invoke($retryHandler, $exception, 3);

        $this->assertEquals(1000, $delay1);
        $this->assertEquals(2000, $delay2);
        $this->assertEquals(4000, $delay3);
    }

    public function testRetryAfterHeaderRespected()
    {
        $retryHandler = new RetryHandler(
            maxAttempts: 3,
            baseDelayMs: 1000,
            maxDelayMs: 30000,
            respectRetryAfter: true,
            useJitter: false
        );

        $reflection = new \ReflectionClass($retryHandler);
        $calculateDelayMethod = $reflection->getMethod('calculateDelay');
        $calculateDelayMethod->setAccessible(true);

        // Create mock 429 exception with Retry-After header
        $request = new Request('GET', '/test');
        $response = new Response(429, ['Retry-After' => '5'], 'Rate limited');
        $guzzleException = new ClientException('Rate limited', $request, $response);
        
        $convertExceptionMethod = $reflection->getMethod('convertException');
        $convertExceptionMethod->setAccessible(true);
        $exception = $convertExceptionMethod->invoke($retryHandler, $guzzleException);

        $delay = $calculateDelayMethod->invoke($retryHandler, $exception, 1);

        // Should use Retry-After value (5 seconds = 5000ms)
        $this->assertEquals(5000, $delay);
    }

    public function testElevenLabsLoggerSanitizesSensitiveData()
    {
        $logger = new ElevenLabsLogger(null, true);
        
        $reflection = new \ReflectionClass($logger);
        $sanitizeMethod = $reflection->getMethod('sanitizeData');
        $sanitizeMethod->setAccessible(true);

        $testData = [
            'xi-api-key' => 'sk-1234567890abcdef',
            'normal_field' => 'normal_value',
            'Authorization' => 'Bearer token123456',
            'nested' => [
                'secret' => 'hidden_value',
                'public' => 'visible_value'
            ]
        ];

        $sanitized = $sanitizeMethod->invoke($logger, $testData);

        $this->assertStringContainsString('***', $sanitized['xi-api-key']);
        $this->assertEquals('normal_value', $sanitized['normal_field']);
        $this->assertStringContainsString('***', $sanitized['Authorization']);
        $this->assertStringContainsString('***', $sanitized['nested']['secret']);
        $this->assertEquals('visible_value', $sanitized['nested']['public']);
    }

    public function testElevenLabsLoggerHandlesBinaryData()
    {
        $logger = new ElevenLabsLogger(null, true);
        
        $reflection = new \ReflectionClass($logger);
        $sanitizeBodyMethod = $reflection->getMethod('sanitizeResponseBody');
        $sanitizeBodyMethod->setAccessible(true);

        // Test binary data
        $binaryData = "\x00\x01\x02\x03\x04";
        $sanitized = $sanitizeBodyMethod->invoke($logger, $binaryData);
        
        $this->assertStringContainsString('[Binary data', $sanitized);
        $this->assertStringContainsString('5 bytes]', $sanitized);

        // Test long text
        $longText = str_repeat('a', 6000);
        $sanitized = $sanitizeBodyMethod->invoke($logger, $longText);
        
        $this->assertStringContainsString('[truncated]', $sanitized);
        $this->assertTrue(strlen($sanitized) < strlen($longText));
    }

    public function testBaseServiceWithRetryDisabled()
    {
        // Test with retry disabled
        $service = $this->injectMockClient(new AudioService($this->apiKey));
        
        // Disable retry in the service
        $reflection = new \ReflectionClass($service);
        $retryEnabledProperty = $reflection->getProperty('retryEnabled');
        $retryEnabledProperty->setAccessible(true);
        $retryEnabledProperty->setValue($service, false);

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/voices')
            ->andThrow(new RequestException(
                'Network error', 
                Mockery::mock(RequestInterface::class)
            ));

        $result = $service->getVoices();
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testExceptionConversion()
    {
        $retryHandler = new RetryHandler();
        $reflection = new \ReflectionClass($retryHandler);
        $convertMethod = $reflection->getMethod('convertException');
        $convertMethod->setAccessible(true);

        // Test 429 -> RateLimitException
        $request = new Request('GET', '/test');
        $response = new Response(429, [], 'Rate limited');
        $guzzleException = new ClientException('Rate limited', $request, $response);
        
        $converted = $convertMethod->invoke($retryHandler, $guzzleException);
        $this->assertInstanceOf(RateLimitException::class, $converted);
        $this->assertEquals(429, $converted->getStatusCode());

        // Test 500 -> ServerErrorException  
        $response = new Response(500, [], 'Internal server error');
        $guzzleException = new ServerException('Server error', $request, $response);
        
        $converted = $convertMethod->invoke($retryHandler, $guzzleException);
        $this->assertInstanceOf(ServerErrorException::class, $converted);
        $this->assertEquals(500, $converted->getStatusCode());

        // Test generic -> ElevenLabsException
        $guzzleException = new RequestException('Generic error', $request);
        
        $converted = $convertMethod->invoke($retryHandler, $guzzleException);
        $this->assertInstanceOf(ElevenLabsException::class, $converted);
    }

    public function testExceptionMethods()
    {
        $request = new Request('GET', '/test');
        $response = new Response(429, ['Retry-After' => '10'], '{"error": "rate_limit_exceeded"}');
        $guzzleException = new ClientException('Rate limited', $request, $response);
        
        $exception = new RateLimitException(
            'Rate limited',
            429,
            $guzzleException,
            $response,
            $guzzleException,
            ['error' => 'rate_limit_exceeded']
        );

        $this->assertTrue($exception->isRateLimited());
        $this->assertFalse($exception->isServerError());
        $this->assertTrue($exception->isClientError());
        $this->assertTrue($exception->isRetryable());
        $this->assertEquals(10, $exception->getRetryAfterSeconds());
        $this->assertEquals(429, $exception->getStatusCode());
        $this->assertStringContainsString('rate_limit_exceeded', $exception->getResponseBody());
    }

    public function testIntegrationWithActualService()
    {
        // Test that a real service call works with the new error handling
        $service = $this->injectMockClient(new AudioService($this->apiKey));
        
        // Mock successful response
        $successResponse = new Response(200, [], '{"voices": []}');
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/voices')
            ->andReturn($successResponse);

        $result = $service->getVoices();
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }
}
