<?php

namespace Samandar\LaravelElevenLabs\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\AI\AIService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Mockery;

class AIServiceAdditionalEndpointsTest extends TestCase
{
    protected $service;
    protected $mockClient;
    protected $apiKey = 'test-api-key';

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock(Client::class);
        $this->service = new AIService($this->apiKey);

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

    public function testGetSignedUrl()
    {
        $agentId = 'agent-1';
        $data = ['signed_url' => 'wss://example'];
        $response = new Response(200, [], json_encode($data));

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/convai/conversations/get-signed-url?agent_id='.$agentId)
            ->andReturn($response);

        $result = $this->service->getSignedUrl($agentId);
        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['data'] ?? $result['signed_url'] ?? $result);
    }

    public function testGetAgentWidgetConfigWithSignature()
    {
        $agentId = 'agent-1';
        $signature = 'sig-123';
        $data = ['widget' => ['theme' => 'dark']];
        $response = new Response(200, [], json_encode($data));

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/convai/agents/{$agentId}/widget?conversation_signature={$signature}")
            ->andReturn($response);

        $result = $this->service->getAgentWidgetConfig($agentId, $signature);
        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['data'] ?? $result['widget'] ?? $result);
    }

    public function testGetAgentWidgetConfigWithoutSignature()
    {
        $agentId = 'agent-1';
        $data = ['widget' => ['layout' => 'floating']];
        $response = new Response(200, [], json_encode($data));

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/convai/agents/{$agentId}/widget")
            ->andReturn($response);

        $result = $this->service->getAgentWidgetConfig($agentId);
        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['data'] ?? $result['widget'] ?? $result);
    }

    public function testCreateKnowledgeBaseDocumentFromFile()
    {
        $data = ['document_id' => 'doc-1'];
        $response = new Response(200, [], json_encode($data));

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/convai/knowledge-base/documents/create-from-file', Mockery::on(function ($opts) {
                return isset($opts['multipart']) && is_array($opts['multipart']) && isset($opts['headers']['xi-api-key']);
            }))
            ->andReturn($response);

        $multipart = [['name' => 'file', 'contents' => fopen('php://temp', 'r'), 'filename' => 'tmp.txt']];
        $result = $this->service->createKnowledgeBaseDocumentFromFile($multipart);
        $this->assertTrue($result['success']);
    }

    public function testGetKnowledgeBaseDocumentContent()
    {
        $docId = 'doc-1';
        $data = ['content' => 'Hello'];
        $response = new Response(200, [], json_encode($data));

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/convai/knowledge-base/documents/{$docId}/content")
            ->andReturn($response);

        $result = $this->service->getKnowledgeBaseDocumentContent($docId);
        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['data'] ?? $result);
    }

    public function testGetRagIndexOverview()
    {
        $data = ['stats' => ['documents' => 2]];
        $response = new Response(200, [], json_encode($data));

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/convai/knowledge-base/rag-index-overview')
            ->andReturn($response);

        $result = $this->service->getRagIndexOverview();
        $this->assertTrue($result['success']);
    }

    public function testToolsAndMcpServers()
    {
        $tools = new Response(200, [], json_encode(['tools' => []]));
        $tool = new Response(200, [], json_encode(['tool_id' => 't1']));
        $toolCreated = new Response(200, [], json_encode(['tool_id' => 't2']));
        $dependent = new Response(200, [], json_encode(['agents' => []]));

        $this->mockClient->shouldReceive('get')->once()->with('/convai/tools')->andReturn($tools);
        $this->mockClient->shouldReceive('get')->once()->with('/convai/tools/t1')->andReturn($tool);
        $this->mockClient->shouldReceive('post')->once()->with('/convai/tools', Mockery::on(fn($o) => isset($o['json'])))->andReturn($toolCreated);
        $this->mockClient->shouldReceive('get')->once()->with('/convai/tools/t1/dependent-agents')->andReturn($dependent);

        $this->assertTrue($this->service->listTools()['success']);
        $this->assertTrue($this->service->getTool('t1')['success']);
        $this->assertTrue($this->service->createTool(['name' => 'X'])['success']);
        $this->assertTrue($this->service->getDependentAgents('t1')['success']);

        $mcpList = new Response(200, [], json_encode(['servers' => []]));
        $mcpCreated = new Response(200, [], json_encode(['id' => 'm1']));
        $policy = new Response(200, [], json_encode(['policy' => 'allow_all']));
        $this->mockClient->shouldReceive('get')->once()->with('/convai/mcp-servers')->andReturn($mcpList);
        $this->mockClient->shouldReceive('post')->once()->with('/convai/mcp-servers', Mockery::on(fn($o) => isset($o['json'])))->andReturn($mcpCreated);
        $this->mockClient->shouldReceive('post')->once()->with('/convai/mcp-servers/approval-policies', Mockery::on(fn($o) => isset($o['json'])))->andReturn($policy);

        $this->assertTrue($this->service->listMcpServers()['success']);
        $this->assertTrue($this->service->createMcpServer(['name' => 'srv'])['success']);
        $this->assertTrue($this->service->createMcpApprovalPolicy(['policy' => 'allow_all'])['success']);
    }

    public function testGetDashboardSettings()
    {
        $data = ['ui' => ['color' => '#fff']];
        $response = new Response(200, [], json_encode($data));

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/convai/dashboard/settings')
            ->andReturn($response);

        $result = $this->service->getDashboardSettings();
        $this->assertTrue($result['success']);
    }
}

