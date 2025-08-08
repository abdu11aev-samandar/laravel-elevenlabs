<?php

namespace Samandar\LaravelElevenLabs\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\AI\AIService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Mockery;

class AIServiceNewEndpointsTest extends TestCase
{
    protected $service;
    protected $mockClient;
    protected $apiKey = 'test-api-key';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockClient = Mockery::mock(Client::class);
        $this->service = new AIService($this->apiKey);
        
        // Inject mock client using reflection
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, $this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetAgentsWithPagination()
    {
        $cursor = 'test-cursor';
        $pageSize = 20;
        $mockResponseData = [
            'agents' => [
                ['agent_id' => '1', 'name' => 'Agent 1'],
                ['agent_id' => '2', 'name' => 'Agent 2'],
            ],
            'next_cursor' => 'next-cursor'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/convai/agents?cursor={$cursor}&page_size={$pageSize}")
            ->andReturn($mockResponse);

        $result = $this->service->getAgents($cursor, $pageSize);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['agents']);
        $this->assertCount(2, $result['agents']['agents']);
    }

    public function testGetAgentsWithoutPagination()
    {
        $mockResponseData = [
            'agents' => [
                ['agent_id' => '1', 'name' => 'Agent 1']
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/convai/agents')
            ->andReturn($mockResponse);

        $result = $this->service->getAgents();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['agents']);
    }

    public function testCreateAgentWithCorrectEndpoint()
    {
        $agentData = [
            'name' => 'Test Agent',
            'prompt' => 'You are a helpful assistant',
            'voice' => ['voice_id' => '21m00Tcm4TlvDq8ikWAM']
        ];
        
        $mockResponseData = [
            'agent_id' => 'new-agent-123',
            'name' => 'Test Agent'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/convai/agents/create', Mockery::on(function ($options) use ($agentData) {
                return isset($options['json']) && $options['json'] === $agentData;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->createAgent($agentData);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['agent']);
    }

    public function testGetConversationsWithFiltering()
    {
        $cursor = 'conv-cursor';
        $pageSize = 50;
        $callStartAfter = time() - 86400; // 24 hours ago
        $callStartBefore = time();
        
        $mockResponseData = [
            'conversations' => [
                ['conversation_id' => '1', 'status' => 'completed'],
                ['conversation_id' => '2', 'status' => 'active'],
            ],
            'total' => 2
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $expectedUrl = "/convai/conversations?" . http_build_query([
            'cursor' => $cursor,
            'page_size' => $pageSize,
            'call_start_after_unix' => $callStartAfter,
            'call_start_before_unix' => $callStartBefore
        ]);
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with($expectedUrl)
            ->andReturn($mockResponse);

        $result = $this->service->getConversations($cursor, $pageSize, $callStartAfter, $callStartBefore);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['conversations']);
        $this->assertEquals(2, $result['conversations']['total']);
    }

    public function testGetConversationsWithoutFilters()
    {
        $mockResponseData = [
            'conversations' => [
                ['conversation_id' => '1', 'status' => 'completed']
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/convai/conversations')
            ->andReturn($mockResponse);

        $result = $this->service->getConversations();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['conversations']);
    }

    public function testGetSpecificConversation()
    {
        $conversationId = 'conv-123';
        $mockResponseData = [
            'conversation_id' => $conversationId,
            'status' => 'completed',
            'duration_seconds' => 120,
            'agent_id' => 'agent-456'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/convai/conversations/{$conversationId}")
            ->andReturn($mockResponse);

        $result = $this->service->getConversation($conversationId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['conversation']);
        $this->assertEquals($conversationId, $result['conversation']['conversation_id']);
    }

    public function testGetConversationAudio()
    {
        $conversationId = 'conv-123';
        $audioData = 'fake-audio-binary-data';
        
        $mockResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], $audioData);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/convai/conversations/{$conversationId}/audio", Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->getConversationAudio($conversationId);

        $this->assertTrue($result['success']);
        $this->assertEquals($audioData, $result['audio']);
        $this->assertEquals('audio/mpeg', $result['content_type']);
    }

    public function testSubmitBatchCalling()
    {
        $batchData = [
            'agent_id' => 'agent-123',
            'csv_data' => base64_encode('name,phone\nJohn,+1234567890'),
            'name' => 'Test Campaign'
        ];
        
        $mockResponseData = [
            'batch_id' => 'batch-456',
            'status' => 'submitted',
            'name' => 'Test Campaign'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/convai/batch-calling/submit', Mockery::on(function ($options) use ($batchData) {
                return isset($options['json']) && $options['json'] === $batchData;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->submitBatchCalling($batchData);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['batch']);
        $this->assertEquals('batch-456', $result['batch']['batch_id']);
    }

    public function testGetBatchCallingStatus()
    {
        $batchId = 'batch-123';
        $mockResponseData = [
            'batch_id' => $batchId,
            'status' => 'processing',
            'completed_calls' => 5,
            'total_calls' => 10
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/convai/batch-calling/{$batchId}")
            ->andReturn($mockResponse);

        $result = $this->service->getBatchCalling($batchId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['batch']);
        $this->assertEquals('processing', $result['batch']['status']);
    }

    public function testGetAgentConversationsBackwardCompatibility()
    {
        $agentId = 'agent-123';
        $mockResponseData = [
            'conversations' => [
                ['conversation_id' => '1', 'agent_id' => $agentId],
                ['conversation_id' => '2', 'agent_id' => $agentId],
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/convai/agents/{$agentId}/conversations")
            ->andReturn($mockResponse);

        $result = $this->service->getAgentConversations($agentId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['conversations']);
        $this->assertCount(2, $result['conversations']['conversations']);
    }

    public function testCreateAgentFailure()
    {
        $agentData = ['name' => 'Test Agent'];
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Invalid agent data', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->createAgent($agentData);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testBatchCallingFailure()
    {
        $batchData = ['agent_id' => 'invalid-agent'];
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Invalid batch data', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->submitBatchCalling($batchData);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testGetConversationAudioFailure()
    {
        $conversationId = 'invalid-conv-id';
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Conversation not found', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->getConversationAudio($conversationId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
}
