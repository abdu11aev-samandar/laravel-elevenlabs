<?php

namespace Samandar\LaravelElevenLabs\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\Analytics\AnalyticsService;
use Samandar\LaravelElevenLabs\Services\Voice\VoiceService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Mockery;

class AnalyticsAndVoiceNewEndpointsTest extends TestCase
{
    protected $analyticsService;
    protected $voiceService;
    protected $mockClient;
    protected $apiKey = 'test-api-key';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockClient = Mockery::mock(Client::class);
        
        $this->analyticsService = new AnalyticsService($this->apiKey);
        $this->voiceService = new VoiceService($this->apiKey);
        
        // Inject mock client using reflection for both services
        $this->injectMockClient($this->analyticsService);
        $this->injectMockClient($this->voiceService);
    }

    protected function injectMockClient($service): void
    {
        $reflection = new \ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($service, $this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // Analytics Service Tests

    public function testGetUserSubscription()
    {
        $mockResponseData = [
            'tier' => 'starter',
            'character_count' => 5000,
            'character_limit' => 10000,
            'next_character_count_reset_unix' => time() + 86400,
            'voice_limit' => 3,
            'professional_voice_limit' => 1,
            'can_extend_character_limit' => true,
            'allowed_to_extend_character_limit' => true,
            'next_invoice' => [
                'amount_due_cents' => 2200,
                'next_payment_attempt_unix' => time() + 2592000
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/user/subscription')
            ->andReturn($mockResponse);

        $result = $this->analyticsService->getUserSubscription();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['subscription']);
        $this->assertEquals('starter', $result['subscription']['tier']);
        $this->assertEquals(5000, $result['subscription']['character_count']);
        $this->assertEquals(10000, $result['subscription']['character_limit']);
    }

    public function testGetUserSubscriptionFailure()
    {
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->andThrow(new RequestException('Unauthorized', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->analyticsService->getUserSubscription();

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testGetUserSubscriptionWithCompleteData()
    {
        $mockResponseData = [
            'tier' => 'pro',
            'character_count' => 25000,
            'character_limit' => 100000,
            'voice_limit' => 10,
            'professional_voice_limit' => 5,
            'can_extend_character_limit' => true,
            'allowed_to_extend_character_limit' => true,
            'next_character_count_reset_unix' => time() + 2592000,
            'voice_add_edit_enabled' => true,
            'voice_changer_access' => true,
            'instant_voice_cloning_enabled' => true,
            'professional_voice_cloning_enabled' => true
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/user/subscription')
            ->andReturn($mockResponse);

        $result = $this->analyticsService->getUserSubscription();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['subscription']);
        $this->assertEquals('pro', $result['subscription']['tier']);
        $this->assertTrue($result['subscription']['voice_changer_access']);
        $this->assertTrue($result['subscription']['professional_voice_cloning_enabled']);
    }

    // Voice Service Tests

    public function testCreateVoicePreviewsBasic()
    {
        $text = "Hello, this is a voice preview test";
        $voiceId = "21m00Tcm4TlvDq8ikWAM";
        
        $mockResponseData = [
            'previews' => [
                [
                    'text' => $text,
                    'audio' => base64_encode('fake-audio-preview-1'),
                    'voice_id' => $voiceId
                ]
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/text-to-voice/create-previews', Mockery::on(function ($options) use ($text, $voiceId) {
                return isset($options['json']) && 
                       $options['json']['text'] === $text &&
                       $options['json']['voice_id'] === $voiceId;
            }))
            ->andReturn($mockResponse);

        $result = $this->voiceService->createVoicePreviews($text, $voiceId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['previews']);
        $this->assertCount(1, $result['previews']['previews']);
        $this->assertEquals($text, $result['previews']['previews'][0]['text']);
    }

    public function testCreateVoicePreviewsWithMultiplePreviews()
    {
        $text = "This is a test for multiple voice previews";
        $voiceId = "AZnzlk1XvdvUeBnXmlld";
        
        $mockResponseData = [
            'previews' => [
                [
                    'text' => $text,
                    'audio' => base64_encode('fake-audio-preview-1'),
                    'voice_id' => $voiceId,
                    'settings' => ['stability' => 0.5, 'similarity_boost' => 0.5]
                ],
                [
                    'text' => $text,
                    'audio' => base64_encode('fake-audio-preview-2'),
                    'voice_id' => $voiceId,
                    'settings' => ['stability' => 0.7, 'similarity_boost' => 0.8]
                ]
            ],
            'metadata' => [
                'voice_id' => $voiceId,
                'total_previews' => 2
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/text-to-voice/create-previews', Mockery::on(function ($options) use ($text, $voiceId) {
                return isset($options['json']) && 
                       $options['json']['text'] === $text &&
                       $options['json']['voice_id'] === $voiceId;
            }))
            ->andReturn($mockResponse);

        $result = $this->voiceService->createVoicePreviews($text, $voiceId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['previews']);
        $this->assertCount(2, $result['previews']['previews']);
        $this->assertEquals(2, $result['previews']['metadata']['total_previews']);
    }

    public function testCreateVoicePreviewsFailure()
    {
        $text = "Test text";
        $voiceId = "invalid-voice-id";
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Voice not found', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->voiceService->createVoicePreviews($text, $voiceId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testCreateVoicePreviewsWithEmptyText()
    {
        $text = "";
        $voiceId = "21m00Tcm4TlvDq8ikWAM";
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Text cannot be empty', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->voiceService->createVoicePreviews($text, $voiceId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testCreateVoicePreviewsWithLongText()
    {
        $text = str_repeat("This is a long text for voice preview testing. ", 20);
        $voiceId = "21m00Tcm4TlvDq8ikWAM";
        
        $mockResponseData = [
            'previews' => [
                [
                    'text' => $text,
                    'audio' => base64_encode('fake-audio-long-preview'),
                    'voice_id' => $voiceId,
                    'duration_seconds' => 45
                ]
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/text-to-voice/create-previews', Mockery::on(function ($options) use ($text, $voiceId) {
                return isset($options['json']) && 
                       $options['json']['text'] === $text &&
                       $options['json']['voice_id'] === $voiceId;
            }))
            ->andReturn($mockResponse);

        $result = $this->voiceService->createVoicePreviews($text, $voiceId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['previews']);
        $this->assertEquals(45, $result['previews']['previews'][0]['duration_seconds']);
    }

    // Integration Test

    public function testBothServicesWorkTogether()
    {
        // First get subscription info
        $subscriptionData = [
            'tier' => 'pro',
            'character_count' => 1000,
            'character_limit' => 100000,
            'voice_limit' => 10
        ];
        
        $subscriptionResponse = new Response(200, [], json_encode($subscriptionData));
        
        // Then create voice previews
        $previewData = [
            'previews' => [
                [
                    'text' => 'Test preview',
                    'audio' => base64_encode('preview-audio'),
                    'voice_id' => '21m00Tcm4TlvDq8ikWAM'
                ]
            ]
        ];
        
        $previewResponse = new Response(200, [], json_encode($previewData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/user/subscription')
            ->andReturn($subscriptionResponse);
            
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/text-to-voice/create-previews', Mockery::any())
            ->andReturn($previewResponse);

        $subscriptionResult = $this->analyticsService->getUserSubscription();
        $this->assertTrue($subscriptionResult['success']);
        $this->assertEquals('pro', $subscriptionResult['subscription']['tier']);

        $previewResult = $this->voiceService->createVoicePreviews('Test preview', '21m00Tcm4TlvDq8ikWAM');
        $this->assertTrue($previewResult['success']);
        $this->assertCount(1, $previewResult['previews']['previews']);
    }
}
