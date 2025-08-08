<?php

namespace Samandar\LaravelElevenLabs\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\Studio\StudioService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery;

class StudioChaptersSnapshotsTest extends TestCase
{
    protected $service;
    protected $mockClient;
    protected $apiKey = 'test-api-key';

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock(Client::class);
        $this->service = new StudioService($this->apiKey);

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

    public function testGetChapter()
    {
        $projectId = 'proj-1';
        $chapterId = 'chap-1';
        $data = ['chapter_id' => $chapterId, 'name' => 'Intro'];
        $response = new Response(200, [], json_encode($data));

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/studio/projects/{$projectId}/chapters/{$chapterId}")
            ->andReturn($response);

        $result = $this->service->getChapter($projectId, $chapterId);
        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['chapter']);
    }

    public function testListChapterSnapshots()
    {
        $projectId = 'proj-1';
        $chapterId = 'chap-1';
        $data = ['snapshots' => [['id' => 'snap-1'], ['id' => 'snap-2']]];
        $response = new Response(200, [], json_encode($data));

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/studio/projects/{$projectId}/chapters/{$chapterId}/snapshots")
            ->andReturn($response);

        $result = $this->service->listChapterSnapshots($projectId, $chapterId);
        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['snapshots']);
        $this->assertCount(2, $result['snapshots']['snapshots']);
    }

    public function testGetChapterSnapshot()
    {
        $projectId = 'proj-1';
        $chapterId = 'chap-1';
        $snapshotId = 'snap-1';
        $data = ['id' => $snapshotId, 'created_at' => '2025-08-08T00:00:00Z'];
        $response = new Response(200, [], json_encode($data));

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/studio/projects/{$projectId}/chapters/{$chapterId}/snapshots/{$snapshotId}")
            ->andReturn($response);

        $result = $this->service->getChapterSnapshot($projectId, $chapterId, $snapshotId);
        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['chapter_snapshot']);
    }

    public function testGetProjectSnapshot()
    {
        $projectId = 'proj-1';
        $projectSnapshotId = 'psnap-1';
        $data = ['id' => $projectSnapshotId, 'notes' => 'checkpoint'];
        $response = new Response(200, [], json_encode($data));

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/studio/projects/{$projectId}/snapshots/{$projectSnapshotId}")
            ->andReturn($response);

        $result = $this->service->getProjectSnapshot($projectId, $projectSnapshotId);
        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['project_snapshot']);
    }
}

