<?php

namespace Samandar\LaravelElevenLabs\Tests\Unit\ServiceTestsComprehensive;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\Studio\StudioService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\UploadedFile;
use Mockery;

/**
 * Comprehensive test coverage for StudioService
 * 
 * @group studio
 * @group comprehensive-coverage
 * @group unit
 */
class StudioServiceComprehensiveTest extends TestCase
{
    protected StudioService $service;
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
            
        $this->service = new StudioService($this->apiKey);
        
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
    // Chapter Management Tests
    // =====================================

    public function test_getChapter_success()
    {
        $projectId = 'proj_123';
        $chapterId = 'chapter_456';
        
        $mockResponseData = [
            'chapter_id' => $chapterId,
            'project_id' => $projectId,
            'name' => 'Chapter 1: Introduction',
            'text' => 'This is the introduction chapter with sample text content.',
            'voice_id' => 'voice_789',
            'voice_settings' => [
                'stability' => 0.7,
                'similarity_boost' => 0.8,
                'style' => 0.2,
                'use_speaker_boost' => true
            ],
            'status' => 'completed',
            'created_at' => '2023-12-01T10:00:00Z',
            'updated_at' => '2023-12-01T11:30:00Z',
            'duration_seconds' => 125.5,
            'character_count' => 350,
            'audio_url' => 'https://example.com/chapter_audio.mp3'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("studio/projects/{$projectId}/chapters/{$chapterId}")
            ->andReturn($mockResponse);

        $result = $this->service->getChapter($projectId, $chapterId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['chapter']);
        $this->assertEquals($chapterId, $result['chapter']['chapter_id']);
        $this->assertEquals($projectId, $result['chapter']['project_id']);
        $this->assertArrayHasKey('voice_settings', $result['chapter']);
    }

    public function test_getChapter_not_found()
    {
        $projectId = 'invalid_proj';
        $chapterId = 'invalid_chapter';
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("studio/projects/{$projectId}/chapters/{$chapterId}")
            ->andThrow(new RequestException('Chapter not found', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->getChapter($projectId, $chapterId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Chapter Snapshots Tests
    // =====================================

    public function test_listChapterSnapshots_success()
    {
        $projectId = 'proj_123';
        $chapterId = 'chapter_456';
        
        $mockResponseData = [
            'snapshots' => [
                [
                    'snapshot_id' => 'snap_1',
                    'chapter_id' => $chapterId,
                    'name' => 'Initial Version',
                    'created_at' => '2023-12-01T10:00:00Z',
                    'text_preview' => 'This is the introduction chapter with...',
                    'voice_settings' => [
                        'stability' => 0.5,
                        'similarity_boost' => 0.75
                    ],
                    'duration_seconds' => 120.3,
                    'character_count' => 345
                ],
                [
                    'snapshot_id' => 'snap_2',
                    'chapter_id' => $chapterId,
                    'name' => 'Revised Version',
                    'created_at' => '2023-12-01T14:30:00Z',
                    'text_preview' => 'This is the improved introduction...',
                    'voice_settings' => [
                        'stability' => 0.7,
                        'similarity_boost' => 0.8
                    ],
                    'duration_seconds' => 125.5,
                    'character_count' => 350
                ]
            ],
            'total_count' => 2
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("studio/projects/{$projectId}/chapters/{$chapterId}/snapshots")
            ->andReturn($mockResponse);

        $result = $this->service->listChapterSnapshots($projectId, $chapterId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['snapshots']);
        $this->assertCount(2, $result['snapshots']['snapshots']);
    }

    public function test_listChapterSnapshots_empty()
    {
        $projectId = 'proj_123';
        $chapterId = 'chapter_new';
        
        $mockResponseData = [
            'snapshots' => [],
            'total_count' => 0
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("studio/projects/{$projectId}/chapters/{$chapterId}/snapshots")
            ->andReturn($mockResponse);

        $result = $this->service->listChapterSnapshots($projectId, $chapterId);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['snapshots']['snapshots']);
    }

    public function test_getChapterSnapshot_success()
    {
        $projectId = 'proj_123';
        $chapterId = 'chapter_456';
        $snapshotId = 'snap_789';
        
        $mockResponseData = [
            'snapshot_id' => $snapshotId,
            'chapter_id' => $chapterId,
            'project_id' => $projectId,
            'name' => 'Final Version',
            'text' => 'Complete chapter text content goes here with full details.',
            'voice_id' => 'voice_custom',
            'voice_settings' => [
                'stability' => 0.75,
                'similarity_boost' => 0.85,
                'style' => 0.15,
                'use_speaker_boost' => true
            ],
            'created_at' => '2023-12-01T16:45:00Z',
            'duration_seconds' => 128.7,
            'character_count' => 365,
            'audio_url' => 'https://example.com/snapshot_audio.mp3',
            'status' => 'completed'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("studio/projects/{$projectId}/chapters/{$chapterId}/snapshots/{$snapshotId}")
            ->andReturn($mockResponse);

        $result = $this->service->getChapterSnapshot($projectId, $chapterId, $snapshotId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['chapter_snapshot']);
        $this->assertEquals($snapshotId, $result['chapter_snapshot']['snapshot_id']);
        $this->assertArrayHasKey('voice_settings', $result['chapter_snapshot']);
    }

    public function test_getChapterSnapshot_not_found()
    {
        $projectId = 'proj_123';
        $chapterId = 'chapter_456';
        $snapshotId = 'invalid_snap';
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("studio/projects/{$projectId}/chapters/{$chapterId}/snapshots/{$snapshotId}")
            ->andThrow(new RequestException('Snapshot not found', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->getChapterSnapshot($projectId, $chapterId, $snapshotId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Project Snapshot Tests
    // =====================================

    public function test_getProjectSnapshot_success()
    {
        $projectId = 'proj_123';
        $snapshotId = 'proj_snap_456';
        
        $mockResponseData = [
            'snapshot_id' => $snapshotId,
            'project_id' => $projectId,
            'name' => 'Complete Project Snapshot',
            'created_at' => '2023-12-01T18:00:00Z',
            'status' => 'completed',
            'chapters' => [
                [
                    'chapter_id' => 'ch1',
                    'name' => 'Introduction',
                    'duration_seconds' => 125.5
                ],
                [
                    'chapter_id' => 'ch2',
                    'name' => 'Main Content',
                    'duration_seconds' => 245.8
                ]
            ],
            'total_duration_seconds' => 371.3,
            'total_characters' => 1250,
            'download_url' => 'https://example.com/project_snapshot.zip'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("studio/projects/{$projectId}/snapshots/{$snapshotId}")
            ->andReturn($mockResponse);

        $result = $this->service->getProjectSnapshot($projectId, $snapshotId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['project_snapshot']);
        $this->assertEquals($snapshotId, $result['project_snapshot']['snapshot_id']);
        $this->assertCount(2, $result['project_snapshot']['chapters']);
    }

    // =====================================
    // Studio Projects Tests
    // =====================================

    public function test_getStudioProjects_success()
    {
        $mockResponseData = [
            'projects' => [
                [
                    'project_id' => 'proj_1',
                    'name' => 'My First Audiobook',
                    'description' => 'A test audiobook project',
                    'created_at' => '2023-11-15T09:30:00Z',
                    'updated_at' => '2023-12-01T16:45:00Z',
                    'status' => 'in_progress',
                    'chapters_count' => 5,
                    'total_duration_seconds' => 1850.5,
                    'total_characters' => 12500,
                    'voice_id' => 'voice_main',
                    'voice_name' => 'Professional Voice'
                ],
                [
                    'project_id' => 'proj_2',
                    'name' => 'Corporate Training Material',
                    'description' => 'Training content for employees',
                    'created_at' => '2023-11-28T14:20:00Z',
                    'updated_at' => '2023-11-30T10:15:00Z',
                    'status' => 'completed',
                    'chapters_count' => 3,
                    'total_duration_seconds' => 945.2,
                    'total_characters' => 6800,
                    'voice_id' => 'voice_corporate',
                    'voice_name' => 'Business Voice'
                ]
            ],
            'total_count' => 2,
            'has_more' => false
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('studio/projects')
            ->andReturn($mockResponse);

        $result = $this->service->getStudioProjects();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['projects']);
        $this->assertCount(2, $result['projects']['projects']);
    }

    public function test_getStudioProjects_empty()
    {
        $mockResponseData = [
            'projects' => [],
            'total_count' => 0,
            'has_more' => false
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('studio/projects')
            ->andReturn($mockResponse);

        $result = $this->service->getStudioProjects();

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['projects']['projects']);
    }

    // =====================================
    // Create Studio Project Tests
    // =====================================

    public function test_createStudioProject_with_file_path()
    {
        $name = 'New Audio Project';
        $sourceFile = tempnam(sys_get_temp_dir(), 'studio_source');
        file_put_contents($sourceFile, 'Source document content for audio project');
        
        $mockResponseData = [
            'project_id' => 'proj_new_123',
            'name' => $name,
            'status' => 'processing',
            'created_at' => '2023-12-15T10:30:00Z',
            'chapters_count' => 0,
            'processing_status' => 'extracting_text'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('studio/projects', Mockery::on(function ($options) use ($name) {
                return isset($options['multipart']) &&
                       isset($options['headers']['xi-api-key']) &&
                       $options['headers']['xi-api-key'] === $this->apiKey;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->createStudioProject($sourceFile, $name);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['project']);
        $this->assertEquals($name, $result['project']['name']);
        
        unlink($sourceFile);
    }

    public function test_createStudioProject_with_uploaded_file()
    {
        $mockUploadedFile = Mockery::mock(UploadedFile::class);
        $mockUploadedFile->shouldReceive('getPathname')->andReturn('/tmp/uploaded_doc.pdf');
        $mockUploadedFile->shouldReceive('getClientOriginalName')->andReturn('source_document.pdf');
        
        $mockResponseData = [
            'project_id' => 'proj_upload_456',
            'name' => 'Uploaded Project',
            'status' => 'processing'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('studio/projects', Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->createStudioProject($mockUploadedFile);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['project']);
    }

    public function test_createStudioProject_without_name()
    {
        $sourceFile = tempnam(sys_get_temp_dir(), 'unnamed_project');
        file_put_contents($sourceFile, 'Content without project name');
        
        $mockResponseData = [
            'project_id' => 'proj_unnamed_789',
            'name' => 'Untitled Project',
            'status' => 'processing'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('studio/projects', Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->createStudioProject($sourceFile);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['project']);
        
        unlink($sourceFile);
    }

    public function test_createStudioProject_invalid_file()
    {
        $invalidFile = '/nonexistent/file.txt';
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('studio/projects', Mockery::any())
            ->andThrow(new RequestException('Invalid file format', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->createStudioProject($invalidFile);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Get Studio Project Tests
    // =====================================

    public function test_getStudioProject_success()
    {
        $projectId = 'proj_detail_123';
        
        $mockResponseData = [
            'project_id' => $projectId,
            'name' => 'Detailed Project View',
            'description' => 'Complete project with all details',
            'created_at' => '2023-12-01T09:00:00Z',
            'updated_at' => '2023-12-01T15:30:00Z',
            'status' => 'completed',
            'voice_id' => 'voice_narrator',
            'voice_name' => 'Professional Narrator',
            'chapters' => [
                [
                    'chapter_id' => 'ch_1',
                    'name' => 'Prologue',
                    'text_preview' => 'In the beginning...',
                    'duration_seconds' => 85.2,
                    'status' => 'completed'
                ],
                [
                    'chapter_id' => 'ch_2',
                    'name' => 'Chapter 1',
                    'text_preview' => 'The story begins...',
                    'duration_seconds' => 156.7,
                    'status' => 'completed'
                ]
            ],
            'total_chapters' => 2,
            'total_duration_seconds' => 241.9,
            'total_characters' => 1850,
            'download_url' => 'https://example.com/project_download.zip'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("studio/projects/{$projectId}")
            ->andReturn($mockResponse);

        $result = $this->service->getStudioProject($projectId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['project']);
        $this->assertEquals($projectId, $result['project']['project_id']);
        $this->assertCount(2, $result['project']['chapters']);
    }

    public function test_getStudioProject_not_found()
    {
        $projectId = 'nonexistent_project';
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("studio/projects/{$projectId}")
            ->andThrow(new RequestException('Project not found', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->getStudioProject($projectId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Delete Studio Project Tests
    // =====================================

    public function test_deleteStudioProject_success()
    {
        $projectId = 'proj_to_delete';
        
        $mockResponse = new Response(200, [], json_encode(['message' => 'Project deleted successfully']));
        
        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("studio/projects/{$projectId}")
            ->andReturn($mockResponse);

        $result = $this->service->deleteStudioProject($projectId);

        $this->assertTrue($result['success']);
    }

    public function test_deleteStudioProject_not_found()
    {
        $projectId = 'nonexistent_project';
        
        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("studio/projects/{$projectId}")
            ->andThrow(new RequestException('Project not found', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->deleteStudioProject($projectId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Convert Studio Project Tests
    // =====================================

    public function test_convertStudioProject_success()
    {
        $projectId = 'proj_convert_123';
        
        $mockResponseData = [
            'conversion_id' => 'conv_456',
            'project_id' => $projectId,
            'status' => 'started',
            'estimated_completion_time' => '2023-12-15T12:30:00Z',
            'progress_percentage' => 0
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("studio/projects/{$projectId}/convert")
            ->andReturn($mockResponse);

        $result = $this->service->convertStudioProject($projectId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['conversion']);
        $this->assertEquals($projectId, $result['conversion']['project_id']);
    }

    public function test_convertStudioProject_already_converting()
    {
        $projectId = 'proj_converting';
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("studio/projects/{$projectId}/convert")
            ->andThrow(new RequestException('Project is already being converted', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->convertStudioProject($projectId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Create Podcast Project Tests
    // =====================================

    public function test_createPodcastProject_success()
    {
        $podcastData = [
            'name' => 'Tech Talk Podcast',
            'description' => 'Weekly technology discussions',
            'voice_id' => 'voice_host',
            'episodes' => [
                [
                    'title' => 'Episode 1: AI Revolution',
                    'content' => 'Today we discuss the impact of AI...',
                    'guest_voice_id' => 'voice_guest1'
                ],
                [
                    'title' => 'Episode 2: Future of Work',
                    'content' => 'How technology is changing employment...',
                    'guest_voice_id' => 'voice_guest2'
                ]
            ],
            'intro_text' => 'Welcome to Tech Talk!',
            'outro_text' => 'Thanks for listening!'
        ];
        
        $mockResponseData = [
            'podcast_id' => 'podcast_123',
            'name' => 'Tech Talk Podcast',
            'status' => 'processing',
            'episodes_count' => 2,
            'estimated_completion' => '2023-12-15T14:00:00Z'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('studio/podcasts', Mockery::on(function ($options) use ($podcastData) {
                return isset($options['json']) &&
                       $options['json'] === $podcastData;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->createPodcastProject($podcastData);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['podcast']);
        $this->assertEquals('Tech Talk Podcast', $result['podcast']['name']);
    }

    public function test_createPodcastProject_validation_error()
    {
        $invalidPodcastData = []; // Empty data
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('studio/podcasts', Mockery::any())
            ->andThrow(new RequestException('Validation failed: name required', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->createPodcastProject($invalidPodcastData);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Dubbing Tests
    // =====================================

    public function test_createDubbing_with_file_path()
    {
        $sourceFile = tempnam(sys_get_temp_dir(), 'dubbing_source');
        file_put_contents($sourceFile, 'fake video/audio content for dubbing');
        
        $targetLanguage = 'es';
        $sourceLanguage = 'en';
        $numSpeakers = 2;
        $watermark = false;
        
        $mockResponseData = [
            'dubbing_id' => 'dub_123',
            'source_language' => $sourceLanguage,
            'target_language' => $targetLanguage,
            'num_speakers' => $numSpeakers,
            'status' => 'processing',
            'created_at' => '2023-12-15T10:00:00Z',
            'estimated_completion' => '2023-12-15T12:00:00Z'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('dubbing', Mockery::on(function ($options) use ($targetLanguage, $sourceLanguage, $numSpeakers, $watermark) {
                return isset($options['multipart']) &&
                       isset($options['headers']['xi-api-key']);
            }))
            ->andReturn($mockResponse);

        $result = $this->service->createDubbing($sourceFile, $targetLanguage, $sourceLanguage, $numSpeakers, $watermark);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['dubbing']);
        $this->assertEquals($targetLanguage, $result['dubbing']['target_language']);
        
        unlink($sourceFile);
    }

    public function test_createDubbing_with_uploaded_file()
    {
        $mockUploadedFile = Mockery::mock(UploadedFile::class);
        $mockUploadedFile->shouldReceive('getPathname')->andReturn('/tmp/video_to_dub.mp4');
        $mockUploadedFile->shouldReceive('getClientOriginalName')->andReturn('source_video.mp4');
        
        $targetLanguage = 'fr';
        
        $mockResponseData = [
            'dubbing_id' => 'dub_upload_456',
            'target_language' => $targetLanguage,
            'status' => 'analyzing'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('dubbing', Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->createDubbing($mockUploadedFile, $targetLanguage);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['dubbing']);
    }

    public function test_createDubbing_with_watermark()
    {
        $sourceFile = tempnam(sys_get_temp_dir(), 'watermark_dub');
        file_put_contents($sourceFile, 'content for watermarked dubbing');
        
        $targetLanguage = 'de';
        $watermark = true;
        
        $mockResponseData = [
            'dubbing_id' => 'dub_watermark_789',
            'target_language' => $targetLanguage,
            'watermark' => $watermark,
            'status' => 'processing'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('dubbing', Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->createDubbing($sourceFile, $targetLanguage, null, null, $watermark);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['dubbing']);
        $this->assertTrue($result['dubbing']['watermark']);
        
        unlink($sourceFile);
    }

    public function test_createDubbing_unsupported_language()
    {
        $sourceFile = tempnam(sys_get_temp_dir(), 'unsupported_lang');
        file_put_contents($sourceFile, 'test content');
        
        $targetLanguage = 'xyz'; // Unsupported language code
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('dubbing', Mockery::any())
            ->andThrow(new RequestException('Unsupported target language', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->createDubbing($sourceFile, $targetLanguage);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        
        unlink($sourceFile);
    }

    // =====================================
    // Get Dubbing Tests
    // =====================================

    public function test_getDubbing_success()
    {
        $dubbingId = 'dub_status_123';
        
        $mockResponseData = [
            'dubbing_id' => $dubbingId,
            'source_language' => 'en',
            'target_language' => 'es',
            'num_speakers' => 3,
            'status' => 'completed',
            'created_at' => '2023-12-15T10:00:00Z',
            'completed_at' => '2023-12-15T12:15:00Z',
            'duration_seconds' => 1350.5,
            'speakers' => [
                [
                    'speaker_id' => 'sp1',
                    'voice_id' => 'voice_spanish_male',
                    'voice_name' => 'Carlos'
                ],
                [
                    'speaker_id' => 'sp2',
                    'voice_id' => 'voice_spanish_female',
                    'voice_name' => 'Maria'
                ]
            ],
            'available_formats' => ['mp3', 'wav'],
            'download_urls' => [
                'es' => 'https://example.com/dubbed_spanish.mp3'
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("dubbing/{$dubbingId}")
            ->andReturn($mockResponse);

        $result = $this->service->getDubbing($dubbingId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['dubbing']);
        $this->assertEquals($dubbingId, $result['dubbing']['dubbing_id']);
        $this->assertEquals('completed', $result['dubbing']['status']);
    }

    public function test_getDubbing_in_progress()
    {
        $dubbingId = 'dub_progress_456';
        
        $mockResponseData = [
            'dubbing_id' => $dubbingId,
            'status' => 'processing',
            'progress_percentage' => 65,
            'estimated_completion' => '2023-12-15T13:45:00Z'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("dubbing/{$dubbingId}")
            ->andReturn($mockResponse);

        $result = $this->service->getDubbing($dubbingId);

        $this->assertTrue($result['success']);
        $this->assertEquals('processing', $result['dubbing']['status']);
        $this->assertEquals(65, $result['dubbing']['progress_percentage']);
    }

    // =====================================
    // Get Dubbed Audio Tests
    // =====================================

    public function test_getDubbedAudio_success()
    {
        $dubbingId = 'dub_audio_123';
        $languageCode = 'es';
        $audioData = 'fake-dubbed-audio-binary-data';
        
        $mockResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], $audioData);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("dubbing/{$dubbingId}/audio/{$languageCode}")
            ->andReturn($mockResponse);

        $result = $this->service->getDubbedAudio($dubbingId, $languageCode);

        $this->assertTrue($result['success']);
        $this->assertEquals($audioData, $result['audio']);
        $this->assertEquals('audio/mpeg', $result['content_type']);
    }

    public function test_getDubbedAudio_not_ready()
    {
        $dubbingId = 'dub_not_ready';
        $languageCode = 'fr';
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("dubbing/{$dubbingId}/audio/{$languageCode}")
            ->andThrow(new RequestException('Dubbing not completed yet', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->getDubbedAudio($dubbingId, $languageCode);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Get Dubbing Transcript Tests
    // =====================================

    public function test_getDubbingTranscript_srt_format()
    {
        $dubbingId = 'dub_transcript_123';
        $formatType = 'srt';
        
        $mockResponseData = [
            'transcript' => "1\n00:00:00,000 --> 00:00:03,500\nHola, bienvenidos al programa\n\n2\n00:00:04,000 --> 00:00:07,200\nHoy hablaremos sobre tecnología\n",
            'format' => $formatType,
            'language' => 'es'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $expectedUrl = "dubbing/{$dubbingId}/transcript?" . http_build_query(['format_type' => $formatType]);
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with($expectedUrl)
            ->andReturn($mockResponse);

        $result = $this->service->getDubbingTranscript($dubbingId, $formatType);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['transcript']);
        $this->assertEquals($formatType, $result['transcript']['format']);
    }

    public function test_getDubbingTranscript_webvtt_format()
    {
        $dubbingId = 'dub_webvtt_456';
        $formatType = 'webvtt';
        
        $mockResponseData = [
            'transcript' => "WEBVTT\n\n00:00:00.000 --> 00:00:03.500\nHola, bienvenidos al programa\n\n00:00:04.000 --> 00:00:07.200\nHoy hablaremos sobre tecnología\n",
            'format' => $formatType,
            'language' => 'es'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $expectedUrl = "dubbing/{$dubbingId}/transcript?" . http_build_query(['format_type' => $formatType]);
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with($expectedUrl)
            ->andReturn($mockResponse);

        $result = $this->service->getDubbingTranscript($dubbingId, $formatType);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['transcript']);
        $this->assertEquals($formatType, $result['transcript']['format']);
    }

    public function test_getDubbingTranscript_default_format()
    {
        $dubbingId = 'dub_default_format';
        
        $mockResponseData = [
            'transcript' => "Default SRT format transcript...",
            'format' => 'srt'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $expectedUrl = "dubbing/{$dubbingId}/transcript?" . http_build_query(['format_type' => 'srt']);
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with($expectedUrl)
            ->andReturn($mockResponse);

        $result = $this->service->getDubbingTranscript($dubbingId);

        $this->assertTrue($result['success']);
        $this->assertEquals('srt', $result['transcript']['format']);
    }

    // =====================================
    // Complex Integration Tests
    // =====================================

    public function test_full_studio_project_workflow()
    {
        // Test complete workflow: Create -> Get -> Convert -> Delete
        $projectName = 'Workflow Test Project';
        $projectId = 'proj_workflow_123';
        $sourceFile = tempnam(sys_get_temp_dir(), 'workflow_source');
        file_put_contents($sourceFile, 'Source content for workflow test');
        
        // Step 1: Create project (mocked)
        $createResponse = new Response(200, [], json_encode([
            'project_id' => $projectId,
            'name' => $projectName,
            'status' => 'processing'
        ]));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('studio/projects', Mockery::any())
            ->andReturn($createResponse);
        
        // Step 2: Get project (mocked)
        $getResponse = new Response(200, [], json_encode([
            'project_id' => $projectId,
            'name' => $projectName,
            'status' => 'completed',
            'chapters' => [['chapter_id' => 'ch1', 'name' => 'Chapter 1']]
        ]));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("studio/projects/{$projectId}")
            ->andReturn($getResponse);
        
        // Step 3: Convert project (mocked)
        $convertResponse = new Response(200, [], json_encode([
            'conversion_id' => 'conv_123',
            'project_id' => $projectId,
            'status' => 'started'
        ]));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("studio/projects/{$projectId}/convert")
            ->andReturn($convertResponse);
        
        // Step 4: Delete project (mocked)
        $deleteResponse = new Response(200, [], json_encode(['success' => true]));
        
        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("studio/projects/{$projectId}")
            ->andReturn($deleteResponse);
        
        // Execute workflow
        $createResult = $this->service->createStudioProject($sourceFile, $projectName);
        $this->assertTrue($createResult['success']);
        
        $getResult = $this->service->getStudioProject($projectId);
        $this->assertTrue($getResult['success']);
        
        $convertResult = $this->service->convertStudioProject($projectId);
        $this->assertTrue($convertResult['success']);
        
        $deleteResult = $this->service->deleteStudioProject($projectId);
        $this->assertTrue($deleteResult['success']);
        
        unlink($sourceFile);
    }

    public function test_dubbing_workflow_with_transcript()
    {
        // Test: Create dubbing -> Check status -> Get audio -> Get transcript
        $dubbingId = 'dub_workflow_123';
        $sourceFile = tempnam(sys_get_temp_dir(), 'dub_workflow');
        file_put_contents($sourceFile, 'video content for dubbing workflow');
        
        // Step 1: Create dubbing
        $createResponse = new Response(200, [], json_encode([
            'dubbing_id' => $dubbingId,
            'status' => 'processing'
        ]));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('dubbing', Mockery::any())
            ->andReturn($createResponse);
        
        // Step 2: Check status
        $statusResponse = new Response(200, [], json_encode([
            'dubbing_id' => $dubbingId,
            'status' => 'completed'
        ]));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("dubbing/{$dubbingId}")
            ->andReturn($statusResponse);
        
        // Step 3: Get dubbed audio
        $audioResponse = new Response(200, ['Content-Type' => 'audio/mp3'], 'dubbed-audio-data');
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("dubbing/{$dubbingId}/audio/es")
            ->andReturn($audioResponse);
        
        // Step 4: Get transcript
        $transcriptResponse = new Response(200, [], json_encode([
            'transcript' => 'SRT transcript content',
            'format' => 'srt'
        ]));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("dubbing/{$dubbingId}/transcript?format_type=srt")
            ->andReturn($transcriptResponse);
        
        // Execute workflow
        $createResult = $this->service->createDubbing($sourceFile, 'es');
        $this->assertTrue($createResult['success']);
        
        $statusResult = $this->service->getDubbing($dubbingId);
        $this->assertTrue($statusResult['success']);
        $this->assertEquals('completed', $statusResult['dubbing']['status']);
        
        $audioResult = $this->service->getDubbedAudio($dubbingId, 'es');
        $this->assertTrue($audioResult['success']);
        
        $transcriptResult = $this->service->getDubbingTranscript($dubbingId, 'srt');
        $this->assertTrue($transcriptResult['success']);
        
        unlink($sourceFile);
    }
}
