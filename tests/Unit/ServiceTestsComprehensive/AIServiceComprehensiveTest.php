<?php

namespace Samandar\LaravelElevenLabs\Tests\Unit\ServiceTestsComprehensive;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\AI\AIService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Mockery;

/**
 * Comprehensive test coverage for AIService
 * 
 * @group ai
 * @group comprehensive-coverage
 * @group unit
 */
class AIServiceComprehensiveTest extends TestCase
{
    protected AIService $service;
    protected $mockClient;
    protected string $apiKey = 'test-api-key';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockClient = Mockery::mock(Client::class);
        
        // Add default getConfig expectation for logging
        $this->mockClient->shouldReceive('getConfig')
            ->with('headers')
            ->andReturn(['xi-api-key' => $this->apiKey])
            ->byDefault();
            
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

    // =====================================
    // Signed URL and Widget Tests
    // =====================================

    public function test_getSignedUrl_success()
    {
        $agentId = 'agent_123';
        $mockResponseData = [
            'signed_url' => 'wss://api.elevenlabs.io/v1/convai/conversations/websocket?token=abc123',
            'expires_at' => time() + 300
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $expectedUrl = 'convai/conversations/get-signed-url?' . http_build_query(['agent_id' => $agentId]);
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with($expectedUrl)
            ->andReturn($mockResponse);

        $result = $this->service->getSignedUrl($agentId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['data']);
        $this->assertArrayHasKey('signed_url', $result['data']);
    }

    public function test_getAgentWidgetConfig_without_signature()
    {
        $agentId = 'agent_123';
        $mockResponseData = [
            'widget_config' => [
                'theme' => 'light',
                'position' => 'bottom-right'
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("convai/agents/{$agentId}/widget")
            ->andReturn($mockResponse);

        $result = $this->service->getAgentWidgetConfig($agentId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['data']);
    }

    public function test_getAgentWidgetConfig_with_signature()
    {
        $agentId = 'agent_123';
        $signature = 'conv_sig_456';
        $mockResponseData = ['widget_config' => ['theme' => 'dark']];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $expectedUrl = "convai/agents/{$agentId}/widget?" . http_build_query(['conversation_signature' => $signature]);
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with($expectedUrl)
            ->andReturn($mockResponse);

        $result = $this->service->getAgentWidgetConfig($agentId, $signature);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['data']);
    }

    // =====================================
    // Conversational AI Settings Tests
    // =====================================

    public function test_getConversationalAISettings_success()
    {
        $mockResponseData = [
            'default_voice_id' => 'voice_123',
            'conversation_timeout' => 300,
            'max_tokens' => 1000
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('convai/settings')
            ->andReturn($mockResponse);

        $result = $this->service->getConversationalAISettings();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['settings']);
    }

    public function test_updateConversationalAISettings_success()
    {
        $settings = [
            'default_voice_id' => 'voice_456',
            'conversation_timeout' => 600
        ];
        
        $mockResponseData = ['updated' => true];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('patch')
            ->once()
            ->with('convai/settings', Mockery::on(function ($options) use ($settings) {
                return isset($options['json']) && $options['json'] === $settings;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->updateConversationalAISettings($settings);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['data']);
    }

    // =====================================
    // Workspace Secrets Tests
    // =====================================

    public function test_getWorkspaceSecrets_success()
    {
        $mockResponseData = [
            'secrets' => [
                'API_KEY_1' => '***masked***',
                'WEBHOOK_URL' => '***masked***'
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('convai/secrets')
            ->andReturn($mockResponse);

        $result = $this->service->getWorkspaceSecrets();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['secrets']);
    }

    // =====================================
    // Knowledge Base Tests
    // =====================================

    public function test_createKnowledgeBaseFromURL_success()
    {
        $url = 'https://example.com/docs';
        $mockResponseData = [
            'knowledge_base_id' => 'kb_123',
            'url' => $url,
            'status' => 'processing'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('convai/knowledge-base/url', Mockery::on(function ($options) use ($url) {
                return isset($options['json']['url']) && $options['json']['url'] === $url;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->createKnowledgeBaseFromURL($url);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['knowledge_base']);
    }

    public function test_getKnowledgeBases_with_pagination()
    {
        $cursor = 'cursor_123';
        $pageSize = 20;
        $mockResponseData = [
            'knowledge_bases' => [
                ['id' => 'kb_1', 'name' => 'KB 1'],
                ['id' => 'kb_2', 'name' => 'KB 2']
            ],
            'next_cursor' => 'cursor_456'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $expectedUrl = 'convai/knowledge-base?' . http_build_query(['cursor' => $cursor, 'page_size' => $pageSize]);
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with($expectedUrl)
            ->andReturn($mockResponse);

        $result = $this->service->getKnowledgeBases($cursor, $pageSize);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['knowledge_bases']);
    }

    public function test_getKnowledgeBases_without_pagination()
    {
        $mockResponseData = ['knowledge_bases' => []];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('convai/knowledge-base')
            ->andReturn($mockResponse);

        $result = $this->service->getKnowledgeBases();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['knowledge_bases']);
    }

    public function test_deleteKnowledgeBase_success()
    {
        $documentationId = 'kb_123';
        
        $mockResponse = new Response(204, [], '');
        
        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("convai/knowledge-base/{$documentationId}")
            ->andReturn($mockResponse);

        $result = $this->service->deleteKnowledgeBase($documentationId);

        $this->assertTrue($result['success']);
    }

    public function test_createKnowledgeBaseDocumentFromFile_success()
    {
        $multipart = [
            ['name' => 'file', 'contents' => 'fake-file-contents', 'filename' => 'test.pdf']
        ];
        
        $mockResponseData = ['document_id' => 'doc_123', 'status' => 'uploaded'];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('convai/knowledge-base/documents/create-from-file', Mockery::on(function ($options) use ($multipart) {
                return isset($options['multipart']) && 
                       isset($options['headers']['xi-api-key']) &&
                       $options['headers']['xi-api-key'] === $this->apiKey;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->createKnowledgeBaseDocumentFromFile($multipart);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['data']);
    }

    public function test_getKnowledgeBaseDocumentContent_success()
    {
        $documentId = 'doc_123';
        $mockResponseData = ['content' => 'Document content here...'];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("convai/knowledge-base/documents/{$documentId}/content")
            ->andReturn($mockResponse);

        $result = $this->service->getKnowledgeBaseDocumentContent($documentId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['data']);
    }

    public function test_getRagIndexOverview_success()
    {
        $mockResponseData = [
            'total_documents' => 150,
            'total_chunks' => 5000,
            'index_status' => 'ready'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('convai/knowledge-base/rag-index-overview')
            ->andReturn($mockResponse);

        $result = $this->service->getRagIndexOverview();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['data']);
    }

    // =====================================
    // Agents Tests
    // =====================================

    public function test_getAgents_with_pagination()
    {
        $cursor = 'agent_cursor';
        $pageSize = 25;
        $mockResponseData = [
            'agents' => [
                ['agent_id' => 'agent_1', 'name' => 'Agent 1'],
                ['agent_id' => 'agent_2', 'name' => 'Agent 2']
            ],
            'has_more' => true
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $expectedUrl = 'convai/agents?' . http_build_query(['cursor' => $cursor, 'page_size' => $pageSize]);
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with($expectedUrl)
            ->andReturn($mockResponse);

        $result = $this->service->getAgents($cursor, $pageSize);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['agents']);
    }

    public function test_createAgent_success()
    {
        $agentData = [
            'name' => 'Test Agent',
            'prompt' => 'You are a helpful assistant',
            'voice_id' => 'voice_123'
        ];
        
        $mockResponseData = [
            'agent_id' => 'agent_456',
            'name' => 'Test Agent',
            'status' => 'active'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('convai/agents/create', Mockery::on(function ($options) use ($agentData) {
                return isset($options['json']) && $options['json'] === $agentData;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->createAgent($agentData);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['agent']);
    }

    public function test_getAgent_success()
    {
        $agentId = 'agent_123';
        $mockResponseData = [
            'agent_id' => $agentId,
            'name' => 'My Agent',
            'prompt' => 'Custom prompt'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("convai/agents/{$agentId}")
            ->andReturn($mockResponse);

        $result = $this->service->getAgent($agentId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['agent']);
    }

    public function test_updateAgent_success()
    {
        $agentId = 'agent_123';
        $agentData = ['name' => 'Updated Agent Name'];
        
        $mockResponseData = [
            'agent_id' => $agentId,
            'name' => 'Updated Agent Name',
            'updated_at' => time()
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("convai/agents/{$agentId}", Mockery::on(function ($options) use ($agentData) {
                return isset($options['json']) && $options['json'] === $agentData;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->updateAgent($agentId, $agentData);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['agent']);
    }

    public function test_deleteAgent_success()
    {
        $agentId = 'agent_123';
        
        $mockResponse = new Response(204, [], '');
        
        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("convai/agents/{$agentId}")
            ->andReturn($mockResponse);

        $result = $this->service->deleteAgent($agentId);

        $this->assertTrue($result['success']);
    }

    // =====================================
    // Conversations Tests
    // =====================================

    public function test_getConversations_with_all_filters()
    {
        $cursor = 'conv_cursor';
        $pageSize = 30;
        $callStartAfter = time() - 3600;
        $callStartBefore = time();
        
        $mockResponseData = [
            'conversations' => [
                ['conversation_id' => 'conv_1', 'status' => 'completed'],
                ['conversation_id' => 'conv_2', 'status' => 'active']
            ],
            'total_count' => 150
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $expectedUrl = 'convai/conversations?' . http_build_query([
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
    }

    public function test_getAgentConversations_success()
    {
        $agentId = 'agent_123';
        $mockResponseData = [
            'conversations' => [
                ['conversation_id' => 'conv_1', 'agent_id' => $agentId]
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("convai/agents/{$agentId}/conversations")
            ->andReturn($mockResponse);

        $result = $this->service->getAgentConversations($agentId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['conversations']);
    }

    public function test_createConversation_success()
    {
        $agentId = 'agent_123';
        $mockResponseData = [
            'conversation_id' => 'conv_456',
            'agent_id' => $agentId,
            'status' => 'active'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("convai/agents/{$agentId}/conversations")
            ->andReturn($mockResponse);

        $result = $this->service->createConversation($agentId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['conversation']);
    }

    public function test_getConversation_success()
    {
        $conversationId = 'conv_123';
        $mockResponseData = [
            'conversation_id' => $conversationId,
            'status' => 'completed',
            'duration' => 120
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("convai/conversations/{$conversationId}")
            ->andReturn($mockResponse);

        $result = $this->service->getConversation($conversationId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['conversation']);
    }

    public function test_getConversationAudio_success()
    {
        $conversationId = 'conv_123';
        $audioData = 'fake-binary-audio-data';
        
        $mockResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], $audioData);
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("convai/conversations/{$conversationId}/audio")
            ->andReturn($mockResponse);

        $result = $this->service->getConversationAudio($conversationId);

        $this->assertTrue($result['success']);
        $this->assertEquals($audioData, $result['audio']);
        $this->assertEquals('audio/mpeg', $result['content_type']);
    }

    // =====================================
    // Batch Calling Tests
    // =====================================

    public function test_submitBatchCalling_success()
    {
        $batchData = [
            'agent_id' => 'agent_123',
            'name' => 'Test Campaign',
            'csv_data' => base64_encode('name,phone\nJohn,+1234567890')
        ];
        
        $mockResponseData = [
            'batch_id' => 'batch_456',
            'status' => 'submitted',
            'estimated_completion' => time() + 3600
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('convai/batch-calling/submit', Mockery::on(function ($options) use ($batchData) {
                return isset($options['json']) && $options['json'] === $batchData;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->submitBatchCalling($batchData);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['batch']);
    }

    public function test_getBatchCalling_success()
    {
        $batchId = 'batch_123';
        $mockResponseData = [
            'batch_id' => $batchId,
            'status' => 'completed',
            'completed_calls' => 10,
            'total_calls' => 10
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("convai/batch-calling/{$batchId}")
            ->andReturn($mockResponse);

        $result = $this->service->getBatchCalling($batchId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['batch']);
    }

    // =====================================
    // Tools Tests
    // =====================================

    public function test_listTools_success()
    {
        $mockResponseData = [
            'tools' => [
                ['tool_id' => 'tool_1', 'name' => 'Calculator'],
                ['tool_id' => 'tool_2', 'name' => 'Weather API']
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('convai/tools')
            ->andReturn($mockResponse);

        $result = $this->service->listTools();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['data']);
    }

    public function test_getTool_success()
    {
        $toolId = 'tool_123';
        $mockResponseData = [
            'tool_id' => $toolId,
            'name' => 'Custom Tool',
            'description' => 'A custom tool for testing'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("convai/tools/{$toolId}")
            ->andReturn($mockResponse);

        $result = $this->service->getTool($toolId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['data']);
    }

    public function test_createTool_success()
    {
        $payload = [
            'name' => 'New Tool',
            'description' => 'A new tool for testing',
            'type' => 'webhook'
        ];
        
        $mockResponseData = [
            'tool_id' => 'tool_456',
            'name' => 'New Tool',
            'status' => 'active'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('convai/tools', Mockery::on(function ($options) use ($payload) {
                return isset($options['json']) && $options['json'] === $payload;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->createTool($payload);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['data']);
    }

    public function test_getDependentAgents_success()
    {
        $toolId = 'tool_123';
        $mockResponseData = [
            'dependent_agents' => [
                ['agent_id' => 'agent_1', 'name' => 'Agent 1'],
                ['agent_id' => 'agent_2', 'name' => 'Agent 2']
            ],
            'count' => 2
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("convai/tools/{$toolId}/dependent-agents")
            ->andReturn($mockResponse);

        $result = $this->service->getDependentAgents($toolId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['data']);
    }

    // =====================================
    // MCP Server Tests
    // =====================================

    public function test_listMcpServers_success()
    {
        $mockResponseData = [
            'mcp_servers' => [
                ['server_id' => 'mcp_1', 'name' => 'Server 1'],
                ['server_id' => 'mcp_2', 'name' => 'Server 2']
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('convai/mcp-servers')
            ->andReturn($mockResponse);

        $result = $this->service->listMcpServers();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['data']);
    }

    public function test_createMcpServer_success()
    {
        $payload = [
            'name' => 'New MCP Server',
            'url' => 'https://example.com/mcp',
            'description' => 'Test MCP server'
        ];
        
        $mockResponseData = [
            'server_id' => 'mcp_456',
            'name' => 'New MCP Server',
            'status' => 'active'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('convai/mcp-servers', Mockery::on(function ($options) use ($payload) {
                return isset($options['json']) && $options['json'] === $payload;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->createMcpServer($payload);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['data']);
    }

    public function test_createMcpApprovalPolicy_success()
    {
        $payload = [
            'server_id' => 'mcp_123',
            'policy' => 'auto_approve',
            'rules' => []
        ];
        
        $mockResponseData = [
            'policy_id' => 'policy_456',
            'server_id' => 'mcp_123',
            'status' => 'active'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('convai/mcp-servers/approval-policies', Mockery::on(function ($options) use ($payload) {
                return isset($options['json']) && $options['json'] === $payload;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->createMcpApprovalPolicy($payload);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['data']);
    }

    // =====================================
    // Dashboard Settings Tests
    // =====================================

    public function test_getDashboardSettings_success()
    {
        $mockResponseData = [
            'theme' => 'dark',
            'layout' => 'sidebar',
            'notifications' => true
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('convai/dashboard/settings')
            ->andReturn($mockResponse);

        $result = $this->service->getDashboardSettings();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['data']);
    }

    // =====================================
    // Error Handling Tests
    // =====================================

    public function test_getSignedUrl_failure()
    {
        $agentId = 'invalid_agent';
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->andThrow(new RequestException('Agent not found', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->getSignedUrl($agentId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_createAgent_validation_error()
    {
        $invalidAgentData = []; // Empty data should cause validation error
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Validation error', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->createAgent($invalidAgentData);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_getKnowledgeBases_network_error()
    {
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->andThrow(new RequestException('Network error', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->getKnowledgeBases();

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
}
