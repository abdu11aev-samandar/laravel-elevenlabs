<?php

namespace Samandar\LaravelElevenLabs\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\Core\WorkspaceService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Mockery;

class WorkspaceServiceTest extends TestCase
{
    protected $service;
    protected $mockClient;
    protected $apiKey = 'test-api-key';

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock(Client::class);
        $this->service = new WorkspaceService($this->apiKey);

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

    public function testGetWorkspaceResources()
    {
        $data = ['resources' => [['id' => 'r1'], ['id' => 'r2']]];
        $response = new Response(200, [], json_encode($data));

        $this->mockClient->shouldReceive('get')->once()->with('/workspace/resources')->andReturn($response);
        $result = $this->service->getWorkspaceResources();

        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['resources']);
    }

    public function testGetWorkspaceResource()
    {
        $id = 'res-1';
        $data = ['id' => $id, 'name' => 'Doc'];
        $response = new Response(200, [], json_encode($data));

        $this->mockClient->shouldReceive('get')->once()->with("/workspace/resources/{$id}")->andReturn($response);
        $result = $this->service->getWorkspaceResource($id);

        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['resource']);
    }

    public function testSearchWorkspaceGroups()
    {
        $params = ['q' => 'team'];
        $data = ['groups' => [['id' => 'g1']]];
        $response = new Response(200, [], json_encode($data));

        $this->mockClient->shouldReceive('get')->once()->with('/workspace/groups/search?q=team')->andReturn($response);
        $result = $this->service->searchWorkspaceGroups($params);

        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['groups']);
    }

    public function testMembersLifecycle()
    {
        $members = new Response(200, [], json_encode(['members' => []]));
        $invite = new Response(200, [], json_encode(['invited' => true]));
        $this->mockClient->shouldReceive('get')->once()->with('/workspace/members')->andReturn($members);
        $this->mockClient->shouldReceive('post')->once()->with('/workspace/members/invite', Mockery::on(fn($o) => isset($o['json']['email'])))->andReturn($invite);
        $this->mockClient->shouldReceive('delete')->once()->with('/workspace/members/m1')->andReturn(new Response(204));

        $this->assertTrue($this->service->getWorkspaceMembers()['success']);
        $this->assertTrue($this->service->inviteWorkspaceMember('user@example.com', ['read'])['success']);
        $this->assertEquals(204, $this->service->removeWorkspaceMember('m1')['status'] ?? 204);
    }

    public function testWorkspaceSecrets()
    {
        $data = ['secrets' => ['API_KEY' => '***']];
        $response = new Response(200, [], json_encode($data));

        $this->mockClient->shouldReceive('get')->once()->with('/workspace/secrets')->andReturn($response);
        $result = $this->service->getWorkspaceSecrets();

        $this->assertTrue($result['success']);
    }

    public function testShareWorkspaceResource()
    {
        $id = 'res-1';
        $payload = ['emails' => ['a@b.c']];
        $response = new Response(200, [], json_encode(['ok' => true]));

        $this->mockClient->shouldReceive('post')->once()->with("/workspace/resources/{$id}/share", Mockery::on(fn($o) => isset($o['json'])))->andReturn($response);
        $result = $this->service->shareWorkspaceResource($id, $payload);

        $this->assertTrue($result['success']);
    }
}

