<?php

namespace Samandar\LaravelElevenLabs\Tests\Integration;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\ElevenLabsService;
use Samandar\LaravelElevenLabs\Services\Audio\AudioService;
use Samandar\LaravelElevenLabs\Services\Voice\VoiceService;
use Samandar\LaravelElevenLabs\Services\AI\AIService;
use Samandar\LaravelElevenLabs\Services\Analytics\AnalyticsService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\UploadedFile;
use Mockery;

class NewEndpointsIntegrationTest extends TestCase
{
    protected $service;
    protected $mockClient;
    protected $apiKey = 'test-api-key';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockClient = Mockery::mock(Client::class);
        $this->service = new ElevenLabsService($this->apiKey);
        
        // Inject mock client to all services
        $this->injectMockClientToService($this->service->audio());
        $this->injectMockClientToService($this->service->voice());
        $this->injectMockClientToService($this->service->ai());
        $this->injectMockClientToService($this->service->analytics());
    }

    protected function injectMockClientToService($service): void
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

    public function testNewEndpointsAccessibilityThroughMainService()
    {
        // Test that new methods are accessible through the main service
        $this->assertTrue(method_exists($this->service->audio(), 'audioIsolation'));
        $this->assertTrue(method_exists($this->service->audio(), 'soundGeneration'));
        $this->assertTrue(method_exists($this->service->voice(), 'createVoicePreviews'));
        $this->assertTrue(method_exists($this->service->analytics(), 'getUserSubscription'));
        $this->assertTrue(method_exists($this->service->ai(), 'getConversation'));
        $this->assertTrue(method_exists($this->service->ai(), 'getConversationAudio'));
        $this->assertTrue(method_exists($this->service->ai(), 'submitBatchCalling'));
        $this->assertTrue(method_exists($this->service->ai(), 'getBatchCalling'));
        $this->assertTrue(method_exists($this->service->ai(), 'getAgentConversations'));
    }

    public function testCompleteAudioProcessingWorkflow()
    {
        // Step 1: Audio Isolation
        // Create temporary file to satisfy fopen
        $tempPath = tempnam(sys_get_temp_dir(), 'noisy_');
        file_put_contents($tempPath, 'fake-wav-data');
        $mockFile = Mockery::mock(UploadedFile::class);
        $mockFile->shouldReceive('getPathname')->andReturn($tempPath);
        $mockFile->shouldReceive('getClientOriginalName')->andReturn('noisy_audio.wav');

        $isolationResponse = new Response(200, ['Content-Type' => 'audio/wav'], 'clean-audio-data');
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('audio-native', Mockery::any())
            ->andReturn($isolationResponse);

        // Step 2: Sound Generation
        $soundGenResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], 'generated-sound-data');
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('sound-generation', Mockery::any())
            ->andReturn($soundGenResponse);

        // Execute workflow
        $isolationResult = $this->service->audio()->audioIsolation($mockFile);
        $this->assertTrue($isolationResult['success']);
        $this->assertEquals('clean-audio-data', $isolationResult['audio']);

        $soundResult = $this->service->audio()->soundGeneration('Background music', 30, 'calm');
        $this->assertTrue($soundResult['success']);
        $this->assertEquals('generated-sound-data', $soundResult['audio']);
    }

    public function testConversationalAICompleteWorkflow()
    {
        // Step 1: Get user subscription to check limits
        $subscriptionData = [
            'tier' => 'pro',
            'voice_limit' => 10,
            'character_limit' => 100000
        ];
        
        $subscriptionResponse = new Response(200, [], json_encode($subscriptionData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('user/subscription')
            ->andReturn($subscriptionResponse);

        // Step 2: Create an AI agent
        $agentData = [
            'agent_id' => 'agent-123',
            'name' => 'Customer Service Bot'
        ];
        
        $agentResponse = new Response(200, [], json_encode($agentData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('convai/agents/create', Mockery::any())
            ->andReturn($agentResponse);

        // Step 3: Get conversations with filtering
        $conversationsData = [
            'conversations' => [
                ['conversation_id' => 'conv-1', 'status' => 'completed'],
                ['conversation_id' => 'conv-2', 'status' => 'active']
            ],
            'total' => 2
        ];
        
        $conversationsResponse = new Response(200, [], json_encode($conversationsData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with(Mockery::pattern('/convai\/conversations\?/'))
            ->andReturn($conversationsResponse);

        // Step 4: Get specific conversation details
        $conversationData = [
            'conversation_id' => 'conv-1',
            'status' => 'completed',
            'duration_seconds' => 120
        ];
        
        $conversationResponse = new Response(200, [], json_encode($conversationData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('convai/conversations/conv-1')
            ->andReturn($conversationResponse);

        // Step 5: Download conversation audio
        $audioResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], 'conversation-audio-data');
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('convai/conversations/conv-1/audio')
            ->andReturn($audioResponse);

        // Execute complete workflow
        $subscription = $this->service->analytics()->getUserSubscription();
        $this->assertTrue($subscription['success']);
        $this->assertEquals('pro', $subscription['subscription']['tier']);

        $agent = $this->service->ai()->createAgent(['name' => 'Customer Service Bot']);
        $this->assertTrue($agent['success']);
        $this->assertEquals('agent-123', $agent['agent']['agent_id']);

        $conversations = $this->service->ai()->getConversations(null, 50);
        $this->assertTrue($conversations['success']);
        $this->assertCount(2, $conversations['conversations']['conversations']);

        $conversation = $this->service->ai()->getConversation('conv-1');
        $this->assertTrue($conversation['success']);
        $this->assertEquals('completed', $conversation['conversation']['status']);

        $audio = $this->service->ai()->getConversationAudio('conv-1');
        $this->assertTrue($audio['success']);
        $this->assertEquals('conversation-audio-data', $audio['audio']);
    }

    public function testBatchCallingWorkflow()
    {
        // Step 1: Submit batch calling job
        $batchData = [
            'batch_id' => 'batch-456',
            'status' => 'submitted',
            'total_calls' => 100
        ];
        
        $batchSubmitResponse = new Response(200, [], json_encode($batchData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('convai/batch-calling/submit', Mockery::any())
            ->andReturn($batchSubmitResponse);

        // Step 2: Check batch status
        $statusData = [
            'batch_id' => 'batch-456',
            'status' => 'processing',
            'completed_calls' => 25,
            'total_calls' => 100
        ];
        
        $statusResponse = new Response(200, [], json_encode($statusData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('convai/batch-calling/batch-456')
            ->andReturn($statusResponse);

        // Execute batch workflow
        $submitResult = $this->service->ai()->submitBatchCalling([
            'agent_id' => 'agent-123',
            'csv_data' => base64_encode('name,phone\nJohn,+1234567890'),
            'name' => 'Test Campaign'
        ]);
        
        $this->assertTrue($submitResult['success']);
        $this->assertEquals('batch-456', $submitResult['batch']['batch_id']);

        $statusResult = $this->service->ai()->getBatchCalling('batch-456');
        $this->assertTrue($statusResult['success']);
        $this->assertEquals('processing', $statusResult['batch']['status']);
        $this->assertEquals(25, $statusResult['batch']['completed_calls']);
    }

    public function testVoicePreviewsWorkflow()
    {
        // Step 1: Get user subscription to check voice limits
        $subscriptionData = [
            'tier' => 'pro',
            'voice_limit' => 10,
            'instant_voice_cloning_enabled' => true
        ];
        
        $subscriptionResponse = new Response(200, [], json_encode($subscriptionData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('user/subscription')
            ->andReturn($subscriptionResponse);

        // Step 2: Create voice previews
        $previewData = [
            'previews' => [
                [
                    'text' => 'Hello, this is a voice preview',
                    'audio' => base64_encode('preview-audio-1'),
                    'voice_id' => '21m00Tcm4TlvDq8ikWAM'
                ],
                [
                    'text' => 'Hello, this is a voice preview',
                    'audio' => base64_encode('preview-audio-2'),
                    'voice_id' => '21m00Tcm4TlvDq8ikWAM'
                ]
            ]
        ];
        
        $previewResponse = new Response(200, [], json_encode($previewData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('text-to-voice/create-previews', Mockery::any())
            ->andReturn($previewResponse);

        // Execute voice preview workflow
        $subscription = $this->service->analytics()->getUserSubscription();
        $this->assertTrue($subscription['success']);
        $this->assertTrue($subscription['subscription']['instant_voice_cloning_enabled']);

        $previews = $this->service->voice()->createVoicePreviews(
            'Hello, this is a voice preview', 
            '21m00Tcm4TlvDq8ikWAM'
        );
        
        $this->assertTrue($previews['success']);
        $this->assertCount(2, $previews['previews']['previews']);
        $this->assertEquals('Hello, this is a voice preview', $previews['previews']['previews'][0]['text']);
    }

    public function testErrorHandlingAcrossNewEndpoints()
    {
        // Test audio isolation error
        $tempErrPath = tempnam(sys_get_temp_dir(), 'aud_');
        file_put_contents($tempErrPath, 'fake-audio');
        $mockFile = Mockery::mock(UploadedFile::class);
        $mockFile->shouldReceive('getPathname')->andReturn($tempErrPath);
        $mockFile->shouldReceive('getClientOriginalName')->andReturn('test.wav');

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('audio-native', Mockery::any())
            ->andThrow(new \GuzzleHttp\Exception\RequestException(
                'Audio processing failed', 
                Mockery::mock('Psr\Http\Message\RequestInterface')
            ));

        $result = $this->service->audio()->audioIsolation($mockFile);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        // Test voice preview error
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('text-to-voice/create-previews', Mockery::any())
            ->andThrow(new \GuzzleHttp\Exception\RequestException(
                'Voice not found', 
                Mockery::mock('Psr\Http\Message\RequestInterface')
            ));

        $result = $this->service->voice()->createVoicePreviews('Test', 'invalid-voice');
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        // Test AI conversation error
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('convai/conversations/invalid-id')
            ->andThrow(new \GuzzleHttp\Exception\RequestException(
                'Conversation not found', 
                Mockery::mock('Psr\Http\Message\RequestInterface')
            ));

        $result = $this->service->ai()->getConversation('invalid-id');
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testBackwardCompatibilityWithNewFeatures()
    {
        // Test that new enhanced methods don't break existing functionality
        $agentData = ['name' => 'Test Agent'];
        $agentResponse = new Response(200, [], json_encode([
            'agent_id' => 'agent-123',
            'name' => 'Test Agent'
        ]));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('convai/agents/create', Mockery::any())
            ->andReturn($agentResponse);

        // Test enhanced agent creation
        $result = $this->service->ai()->createAgent($agentData);
        $this->assertTrue($result['success']);
        $this->assertEquals('agent-123', $result['agent']['agent_id']);

        // Test backward compatibility conversation method
        $conversationsData = ['conversations' => []];
        $conversationsResponse = new Response(200, [], json_encode($conversationsData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('convai/agents/agent-123/conversations')
            ->andReturn($conversationsResponse);

        $conversations = $this->service->ai()->getAgentConversations('agent-123');
        $this->assertTrue($conversations['success']);
        $this->assertArrayHasKey('conversations', $conversations);
    }
}
