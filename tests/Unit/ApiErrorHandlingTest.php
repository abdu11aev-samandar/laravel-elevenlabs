<?php

namespace Samandar\LaravelElevenLabs\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\Audio\AudioService;
use Samandar\LaravelElevenLabs\Services\Voice\VoiceService;
use Samandar\LaravelElevenLabs\Services\AI\AIService;
use Samandar\LaravelElevenLabs\Services\Analytics\AnalyticsService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Http\UploadedFile;
use Mockery;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ApiErrorHandlingTest extends TestCase
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
    
    public function testAudioIsolationHandlesNetworkError()
    {
        $service = $this->injectMockClient(new AudioService($this->apiKey));
        
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test audio');
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new ConnectException('Network error', Mockery::mock(RequestInterface::class)));
        
        $result = $service->audioIsolation($tempFile);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Network error', $result['error']);
        
        unlink($tempFile);
    }
    
    public function testSoundGenerationHandles400Error()
    {
        $service = $this->injectMockClient(new AudioService($this->apiKey));
        
        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(400);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new ClientException('Bad Request', $request, $response));
        
        $result = $service->soundGeneration('Invalid sound description');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('code', $result);
    }
    
    public function testVoicePreviewsHandles401Error()
    {
        $service = $this->injectMockClient(new VoiceService($this->apiKey));
        
        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(401);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new ClientException('Unauthorized', $request, $response));
        
        $result = $service->createVoicePreviews('Test text', 'invalid-voice-id');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Unauthorized', $result['error']);
    }
    
    public function testAnalyticsServiceHandles403Error()
    {
        $service = $this->injectMockClient(new AnalyticsService($this->apiKey));
        
        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(403);
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->andThrow(new ClientException('Forbidden', $request, $response));
        
        $result = $service->getUserSubscription();
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(403, $result['code']);
    }
    
    public function testAIServiceHandles404Error()
    {
        $service = $this->injectMockClient(new AIService($this->apiKey));
        
        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(404);
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->andThrow(new ClientException('Not Found', $request, $response));
        
        $result = $service->getConversation('non-existent-id');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(404, $result['code']);
    }
    
    public function testBatchCallingHandles500Error()
    {
        $service = $this->injectMockClient(new AIService($this->apiKey));
        
        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(500);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new ServerException('Internal Server Error', $request, $response));
        
        $result = $service->submitBatchCalling([
            'agent_id' => 'test-agent',
            'csv_data' => base64_encode('name,phone\nTest,+1234567890')
        ]);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(500, $result['code']);
    }
    
    public function testConversationAudioHandlesTimeout()
    {
        $service = $this->injectMockClient(new AIService($this->apiKey));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->andThrow(new RequestException(
                'Connection timeout',
                Mockery::mock(RequestInterface::class)
            ));
        
        $result = $service->getConversationAudio('conv-id');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('timeout', strtolower($result['error']));
    }
    
    public function testMultipleServicesHandleErrors()
    {
        // Test that all services handle errors consistently
        $services = [
            'audio' => new AudioService($this->apiKey),
            'voice' => new VoiceService($this->apiKey),
            'ai' => new AIService($this->apiKey),
            'analytics' => new AnalyticsService($this->apiKey),
        ];
        
        foreach ($services as $serviceName => $service) {
            $mockClient = Mockery::mock(Client::class);
            $mockClient
                ->shouldReceive('get')
                ->andThrow(new RequestException(
                    'Service error', 
                    Mockery::mock(RequestInterface::class)
                ));
            
            $this->injectMockClient($service);
            $reflection = new \ReflectionClass($service);
            $clientProperty = $reflection->getProperty('client');
            $clientProperty->setAccessible(true);
            $clientProperty->setValue($service, $mockClient);
            
            // Test a basic GET operation on each service
            switch ($serviceName) {
                case 'audio':
                    // AudioService doesn't have direct GET methods, skip
                    continue 2;
                case 'voice':
                    $result = $service->getVoices();
                    break;
                case 'ai':
                    $result = $service->getConversationalAISettings();
                    break;
                case 'analytics':
                    $result = $service->getUserInfo();
                    break;
            }
            
            $this->assertFalse($result['success'], "Service $serviceName should handle errors");
            $this->assertArrayHasKey('error', $result, "Service $serviceName should include error message");
        }
    }
    
    public function testErrorResponseStructure()
    {
        $service = $this->injectMockClient(new AnalyticsService($this->apiKey));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->andThrow(new RequestException(
                'Test error message',
                Mockery::mock(RequestInterface::class)
            ));
        
        $result = $service->getUserInfo();
        
        // Verify error response structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertFalse($result['success']);
        $this->assertIsString($result['error']);
        $this->assertNotEmpty($result['error']);
    }
}
