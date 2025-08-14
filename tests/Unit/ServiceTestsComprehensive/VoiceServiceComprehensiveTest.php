<?php

namespace Samandar\LaravelElevenLabs\Tests\Unit\ServiceTestsComprehensive;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\Voice\VoiceService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\UploadedFile;
use Mockery;

/**
 * Comprehensive test coverage for VoiceService
 * 
 * @group voice
 * @group comprehensive-coverage
 * @group unit
 */
class VoiceServiceComprehensiveTest extends TestCase
{
    protected VoiceService $service;
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
            
        $this->service = new VoiceService($this->apiKey);
        
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
    // Get Voices Tests
    // =====================================

    public function test_getVoices_success()
    {
        $mockResponseData = [
            'voices' => [
                [
                    'voice_id' => 'voice_1',
                    'name' => 'Rachel',
                    'category' => 'premade',
                    'preview_url' => 'https://example.com/preview1.mp3',
                    'available_for_tiers' => ['free', 'starter', 'creator', 'pro', 'scale'],
                    'settings' => [
                        'stability' => 0.5,
                        'similarity_boost' => 0.75,
                        'style' => 0.0,
                        'use_speaker_boost' => true
                    ],
                    'labels' => ['american', 'female', 'young'],
                    'description' => 'A friendly American voice',
                    'age' => 'young',
                    'gender' => 'female',
                    'accent' => 'american'
                ],
                [
                    'voice_id' => 'voice_2',
                    'name' => 'Drew',
                    'category' => 'premade',
                    'preview_url' => 'https://example.com/preview2.mp3',
                    'available_for_tiers' => ['starter', 'creator', 'pro', 'scale'],
                    'settings' => [
                        'stability' => 0.7,
                        'similarity_boost' => 0.8,
                        'style' => 0.2,
                        'use_speaker_boost' => false
                    ],
                    'labels' => ['american', 'male', 'middle aged'],
                    'description' => 'A warm male voice',
                    'age' => 'middle aged',
                    'gender' => 'male',
                    'accent' => 'american'
                ]
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('voices')
            ->andReturn($mockResponse);

        $result = $this->service->getVoices();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData['voices'], $result['voices']);
        $this->assertCount(2, $result['voices']);
        $this->assertEquals('Rachel', $result['voices'][0]['name']);
        $this->assertEquals('Drew', $result['voices'][1]['name']);
    }

    public function test_getVoices_empty_response()
    {
        $mockResponseData = ['voices' => []];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('voices')
            ->andReturn($mockResponse);

        $result = $this->service->getVoices();

        $this->assertTrue($result['success']);
        $this->assertEquals([], $result['voices']);
        $this->assertEmpty($result['voices']);
    }

    public function test_getVoices_failure()
    {
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('voices')
            ->andThrow(new RequestException('Service unavailable', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->getVoices();

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Get Voice Tests
    // =====================================

    public function test_getVoice_success()
    {
        $voiceId = 'voice_123';
        $mockResponseData = [
            'voice_id' => $voiceId,
            'name' => 'Custom Voice',
            'category' => 'cloned',
            'description' => 'My custom cloned voice',
            'preview_url' => 'https://example.com/preview_custom.mp3',
            'available_for_tiers' => ['creator', 'pro', 'scale'],
            'settings' => [
                'stability' => 0.6,
                'similarity_boost' => 0.9,
                'style' => 0.1,
                'use_speaker_boost' => true
            ],
            'labels' => ['custom', 'male', 'professional'],
            'samples' => [
                [
                    'sample_id' => 'sample_1',
                    'file_name' => 'sample1.wav',
                    'mime_type' => 'audio/wav',
                    'size_bytes' => 512000,
                    'hash' => 'abc123def456'
                ]
            ],
            'fine_tuning' => [
                'is_allowed_to_fine_tune' => true,
                'finetuning_state' => 'not_started',
                'verification' => [
                    'requires_verification' => false,
                    'is_verified' => true,
                    'verification_failures' => [],
                    'verification_attempts_count' => 0
                ]
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("voices/{$voiceId}")
            ->andReturn($mockResponse);

        $result = $this->service->getVoice($voiceId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['voice']);
        $this->assertEquals($voiceId, $result['voice']['voice_id']);
        $this->assertEquals('Custom Voice', $result['voice']['name']);
        $this->assertArrayHasKey('settings', $result['voice']);
        $this->assertArrayHasKey('samples', $result['voice']);
    }

    public function test_getVoice_not_found()
    {
        $voiceId = 'invalid_voice_id';
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("voices/{$voiceId}")
            ->andThrow(new RequestException('Voice not found', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->getVoice($voiceId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Add Voice Tests (Voice Cloning)
    // =====================================

    public function test_addVoice_with_file_paths()
    {
        $name = 'My New Voice';
        $description = 'A test voice clone';
        $labels = ['test' => 'voice', 'gender' => 'male'];
        
        // Create temporary audio files
        $audioFiles = [];
        for ($i = 0; $i < 3; $i++) {
            $tempFile = tempnam(sys_get_temp_dir(), 'voice_sample_' . $i);
            file_put_contents($tempFile, "fake audio content {$i}");
            $audioFiles[] = $tempFile;
        }
        
        $mockResponseData = [
            'voice_id' => 'voice_new_123',
            'name' => $name,
            'category' => 'cloned',
            'description' => $description,
            'settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.75
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('voices/add', Mockery::on(function ($options) use ($name, $description) {
                return isset($options['multipart']) &&
                       isset($options['headers']['xi-api-key']) &&
                       $options['headers']['xi-api-key'] === $this->apiKey;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->addVoice($name, $audioFiles, $description, $labels);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['voice']);
        $this->assertEquals($name, $result['voice']['name']);
        
        // Clean up
        foreach ($audioFiles as $file) {
            unlink($file);
        }
    }

    public function test_addVoice_with_uploaded_files()
    {
        $name = 'Uploaded Voice';
        $description = 'Voice from uploaded files';
        
        // Mock UploadedFile objects
        $mockUploadedFiles = [];
        for ($i = 0; $i < 2; $i++) {
            $mockFile = Mockery::mock(UploadedFile::class);
            $mockFile->shouldReceive('getPathname')->andReturn("/tmp/upload_{$i}.wav");
            $mockFile->shouldReceive('getClientOriginalName')->andReturn("sample_{$i}.wav");
            $mockUploadedFiles[] = $mockFile;
        }
        
        $mockResponseData = [
            'voice_id' => 'voice_upload_456',
            'name' => $name,
            'category' => 'cloned'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('voices/add', Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->addVoice($name, $mockUploadedFiles, $description);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['voice']);
    }

    public function test_addVoice_validation_error()
    {
        $name = '';  // Invalid empty name
        $audioFiles = [];  // No audio files
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('voices/add', Mockery::any())
            ->andThrow(new RequestException('Validation failed: name required and audio files required', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->addVoice($name, $audioFiles);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Edit Voice Settings Tests
    // =====================================

    public function test_editVoiceSettings_success()
    {
        $voiceId = 'voice_123';
        $voiceSettings = [
            'stability' => 0.8,
            'similarity_boost' => 0.9,
            'style' => 0.3,
            'use_speaker_boost' => false
        ];
        
        $mockResponseData = [
            'success' => true,
            'message' => 'Voice settings updated successfully'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("voices/{$voiceId}/settings/edit", Mockery::on(function ($options) use ($voiceSettings) {
                return isset($options['json']) &&
                       $options['json'] === $voiceSettings;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->editVoiceSettings($voiceId, $voiceSettings);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['data']);
    }

    public function test_editVoiceSettings_invalid_voice()
    {
        $voiceId = 'invalid_voice';
        $voiceSettings = ['stability' => 0.5];
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("voices/{$voiceId}/settings/edit", Mockery::any())
            ->andThrow(new RequestException('Voice not found', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->editVoiceSettings($voiceId, $voiceSettings);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Delete Voice Tests
    // =====================================

    public function test_deleteVoice_success()
    {
        $voiceId = 'voice_to_delete';
        
        $mockResponse = new Response(200, [], json_encode(['message' => 'Voice deleted successfully']));
        
        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("voices/{$voiceId}")
            ->andReturn($mockResponse);

        $result = $this->service->deleteVoice($voiceId);

        $this->assertTrue($result['success']);
    }

    public function test_deleteVoice_not_found()
    {
        $voiceId = 'nonexistent_voice';
        
        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("voices/{$voiceId}")
            ->andThrow(new RequestException('Voice not found', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->deleteVoice($voiceId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_deleteVoice_permission_denied()
    {
        $voiceId = 'premade_voice';  // Can't delete premade voices
        
        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("voices/{$voiceId}")
            ->andThrow(new RequestException('Cannot delete premade voice', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->deleteVoice($voiceId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Similar Library Voices Tests
    // =====================================

    public function test_getSimilarLibraryVoices_with_file_path()
    {
        $audioFile = tempnam(sys_get_temp_dir(), 'similarity_test');
        file_put_contents($audioFile, 'audio content for similarity matching');
        
        $mockResponseData = [
            'similar_voices' => [
                [
                    'voice_id' => 'library_voice_1',
                    'name' => 'Similar Voice 1',
                    'category' => 'professional',
                    'similarity_score' => 0.85,
                    'preview_url' => 'https://example.com/library_preview1.mp3'
                ],
                [
                    'voice_id' => 'library_voice_2',
                    'name' => 'Similar Voice 2',
                    'category' => 'professional',
                    'similarity_score' => 0.78,
                    'preview_url' => 'https://example.com/library_preview2.mp3'
                ]
            ],
            'total_count' => 2
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('similar-voices', Mockery::on(function ($options) {
                return isset($options['multipart']) &&
                       isset($options['headers']['xi-api-key']);
            }))
            ->andReturn($mockResponse);

        $result = $this->service->getSimilarLibraryVoices($audioFile);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['voices']);
        $this->assertCount(2, $result['voices']['similar_voices']);
        
        unlink($audioFile);
    }

    public function test_getSimilarLibraryVoices_with_uploaded_file()
    {
        $mockUploadedFile = Mockery::mock(UploadedFile::class);
        $mockUploadedFile->shouldReceive('getPathname')->andReturn('/tmp/similarity.wav');
        $mockUploadedFile->shouldReceive('getClientOriginalName')->andReturn('similarity.wav');
        
        $mockResponseData = [
            'similar_voices' => [
                [
                    'voice_id' => 'lib_voice_similar',
                    'name' => 'Matching Voice',
                    'similarity_score' => 0.92
                ]
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('similar-voices', Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->getSimilarLibraryVoices($mockUploadedFile);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['voices']);
    }

    public function test_getSimilarLibraryVoices_no_matches()
    {
        $audioFile = tempnam(sys_get_temp_dir(), 'no_matches');
        file_put_contents($audioFile, 'unique audio with no matches');
        
        $mockResponseData = [
            'similar_voices' => [],
            'total_count' => 0
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('similar-voices', Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->getSimilarLibraryVoices($audioFile);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['voices']['similar_voices']);
        
        unlink($audioFile);
    }

    // =====================================
    // Shared Voices Tests
    // =====================================

    public function test_getSharedVoices_success()
    {
        $mockResponseData = [
            'voices' => [
                [
                    'voice_id' => 'shared_1',
                    'name' => 'Community Voice 1',
                    'category' => 'professional',
                    'description' => 'A professional shared voice',
                    'preview_url' => 'https://example.com/shared1.mp3',
                    'owner_id' => 'user_456',
                    'labels' => ['professional', 'clear'],
                    'date_created' => '2023-12-01T10:00:00Z',
                    'usage_count' => 1250
                ],
                [
                    'voice_id' => 'shared_2',
                    'name' => 'Community Voice 2',
                    'category' => 'conversational',
                    'description' => 'A friendly conversational voice',
                    'preview_url' => 'https://example.com/shared2.mp3',
                    'owner_id' => 'user_789',
                    'labels' => ['friendly', 'warm'],
                    'date_created' => '2023-11-28T14:30:00Z',
                    'usage_count' => 890
                ]
            ],
            'total_count' => 2,
            'has_more' => false
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('shared-voices')
            ->andReturn($mockResponse);

        $result = $this->service->getSharedVoices();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['voices']);
        $this->assertCount(2, $result['voices']['voices']);
    }

    public function test_getSharedVoices_empty()
    {
        $mockResponseData = [
            'voices' => [],
            'total_count' => 0,
            'has_more' => false
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('shared-voices')
            ->andReturn($mockResponse);

        $result = $this->service->getSharedVoices();

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['voices']['voices']);
    }

    // =====================================
    // Pronunciation Dictionaries Tests
    // =====================================

    public function test_getPronunciationDictionaries_success()
    {
        $mockResponseData = [
            'pronunciation_dictionaries' => [
                [
                    'dictionary_id' => 'dict_1',
                    'name' => 'Technical Terms',
                    'description' => 'Dictionary for technical pronunciations',
                    'created_date' => '2023-12-01T10:00:00Z',
                    'version_id' => 'v1',
                    'phonemes' => []
                ],
                [
                    'dictionary_id' => 'dict_2',
                    'name' => 'Brand Names',
                    'description' => 'Common brand name pronunciations',
                    'created_date' => '2023-11-15T16:45:00Z',
                    'version_id' => 'v2',
                    'phonemes' => []
                ]
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('pronunciation-dictionaries')
            ->andReturn($mockResponse);

        $result = $this->service->getPronunciationDictionaries();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['dictionaries']);
        $this->assertCount(2, $result['dictionaries']['pronunciation_dictionaries']);
    }

    public function test_getPronunciationDictionaries_empty()
    {
        $mockResponseData = ['pronunciation_dictionaries' => []];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('pronunciation-dictionaries')
            ->andReturn($mockResponse);

        $result = $this->service->getPronunciationDictionaries();

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['dictionaries']['pronunciation_dictionaries']);
    }

    // =====================================
    // Add Pronunciation Dictionary Tests
    // =====================================

    public function test_addPronunciationDictionary_success()
    {
        $name = 'Custom Dictionary';
        $description = 'My custom pronunciation rules';
        $rules = [
            ['spelling' => 'ElevenLabs', 'pronunciation' => 'ɪˈlɛvən læbz'],
            ['spelling' => 'API', 'pronunciation' => 'eɪ piː aɪ']
        ];
        
        $mockResponseData = [
            'dictionary_id' => 'dict_new_123',
            'name' => $name,
            'description' => $description,
            'created_date' => '2023-12-15T12:00:00Z',
            'version_id' => 'v1',
            'phonemes' => $rules
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('pronunciation-dictionaries/add', Mockery::on(function ($options) use ($name, $description, $rules) {
                return isset($options['json']) &&
                       $options['json']['name'] === $name &&
                       $options['json']['description'] === $description &&
                       $options['json']['rules'] === $rules;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->addPronunciationDictionary($name, $rules, $description);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['dictionary']);
        $this->assertEquals($name, $result['dictionary']['name']);
    }

    public function test_addPronunciationDictionary_without_description()
    {
        $name = 'Simple Dictionary';
        $rules = [
            ['spelling' => 'test', 'pronunciation' => 'tɛst']
        ];
        
        $mockResponseData = [
            'dictionary_id' => 'dict_simple',
            'name' => $name,
            'description' => '',
            'phonemes' => $rules
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('pronunciation-dictionaries/add', Mockery::on(function ($options) use ($name, $rules) {
                return isset($options['json']) &&
                       $options['json']['name'] === $name &&
                       $options['json']['description'] === '' &&
                       $options['json']['rules'] === $rules;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->addPronunciationDictionary($name, $rules);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['dictionary']);
    }

    public function test_addPronunciationDictionary_validation_error()
    {
        $name = '';  // Invalid empty name
        $rules = [];  // Empty rules
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('pronunciation-dictionaries/add', Mockery::any())
            ->andThrow(new RequestException('Validation failed: name and rules required', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->addPronunciationDictionary($name, $rules);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Voice Previews Tests
    // =====================================

    public function test_createVoicePreviews_success()
    {
        $text = 'This is a test of voice preview generation';
        $voiceId = 'voice_preview_test';
        
        $mockResponseData = [
            'previews' => [
                [
                    'preview_id' => 'preview_1',
                    'audio_url' => 'https://example.com/preview1.mp3',
                    'generation_id' => 'gen_1',
                    'duration_seconds' => 3.2
                ],
                [
                    'preview_id' => 'preview_2',
                    'audio_url' => 'https://example.com/preview2.mp3',
                    'generation_id' => 'gen_2',
                    'duration_seconds' => 3.1
                ],
                [
                    'preview_id' => 'preview_3',
                    'audio_url' => 'https://example.com/preview3.mp3',
                    'generation_id' => 'gen_3',
                    'duration_seconds' => 3.3
                ]
            ],
            'text' => $text,
            'voice_id' => $voiceId
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('text-to-voice/create-previews', Mockery::on(function ($options) use ($text, $voiceId) {
                return isset($options['json']) &&
                       $options['json']['text'] === $text &&
                       $options['json']['voice_id'] === $voiceId;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->createVoicePreviews($text, $voiceId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['previews']);
        $this->assertCount(3, $result['previews']['previews']);
    }

    public function test_createVoicePreviews_invalid_voice()
    {
        $text = 'Test text';
        $voiceId = 'invalid_voice_id';
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('text-to-voice/create-previews', Mockery::any())
            ->andThrow(new RequestException('Voice not found', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->createVoicePreviews($text, $voiceId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_createVoicePreviews_text_too_long()
    {
        $text = str_repeat('This is a very long text that exceeds the maximum allowed length for voice preview generation. ', 100);
        $voiceId = 'voice_123';
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('text-to-voice/create-previews', Mockery::any())
            ->andThrow(new RequestException('Text too long for preview generation', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->createVoicePreviews($text, $voiceId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Complex Integration Tests
    // =====================================

    public function test_voice_management_workflow()
    {
        // Test a complete workflow: Add voice -> Get voice -> Edit settings -> Delete voice
        $voiceName = 'Workflow Test Voice';
        $voiceId = 'voice_workflow_123';
        
        // Step 1: Add voice (mocked)
        $addResponse = new Response(200, [], json_encode([
            'voice_id' => $voiceId,
            'name' => $voiceName,
            'category' => 'cloned'
        ]));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('voices/add', Mockery::any())
            ->andReturn($addResponse);
        
        // Step 2: Get voice (mocked)
        $getResponse = new Response(200, [], json_encode([
            'voice_id' => $voiceId,
            'name' => $voiceName,
            'settings' => ['stability' => 0.5, 'similarity_boost' => 0.75]
        ]));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("voices/{$voiceId}")
            ->andReturn($getResponse);
        
        // Step 3: Edit settings (mocked)
        $editResponse = new Response(200, [], json_encode(['success' => true]));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("voices/{$voiceId}/settings/edit", Mockery::any())
            ->andReturn($editResponse);
        
        // Step 4: Delete voice (mocked)
        $deleteResponse = new Response(200, [], json_encode(['success' => true]));
        
        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("voices/{$voiceId}")
            ->andReturn($deleteResponse);
        
        // Execute workflow
        $audioFiles = [tempnam(sys_get_temp_dir(), 'workflow_audio')];
        file_put_contents($audioFiles[0], 'test audio');
        
        // Step 1: Add voice
        $addResult = $this->service->addVoice($voiceName, $audioFiles);
        $this->assertTrue($addResult['success']);
        
        // Step 2: Get voice details
        $getResult = $this->service->getVoice($voiceId);
        $this->assertTrue($getResult['success']);
        
        // Step 3: Edit voice settings
        $editResult = $this->service->editVoiceSettings($voiceId, ['stability' => 0.8]);
        $this->assertTrue($editResult['success']);
        
        // Step 4: Delete voice
        $deleteResult = $this->service->deleteVoice($voiceId);
        $this->assertTrue($deleteResult['success']);
        
        // Cleanup
        unlink($audioFiles[0]);
    }

    public function test_mixed_file_types_voice_cloning()
    {
        $name = 'Mixed Files Voice';
        
        // Create different file types (simulated)
        $audioFiles = [];
        $fileTypes = ['wav', 'mp3', 'flac'];
        
        foreach ($fileTypes as $index => $type) {
            $tempFile = tempnam(sys_get_temp_dir(), "mixed_{$type}_");
            file_put_contents($tempFile, "fake {$type} audio content");
            $audioFiles[] = $tempFile;
        }
        
        $mockResponseData = [
            'voice_id' => 'voice_mixed_files',
            'name' => $name,
            'category' => 'cloned',
            'samples' => [
                ['file_name' => 'sample_0.wav', 'mime_type' => 'audio/wav'],
                ['file_name' => 'sample_1.mp3', 'mime_type' => 'audio/mpeg'],
                ['file_name' => 'sample_2.flac', 'mime_type' => 'audio/flac']
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('voices/add', Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->addVoice($name, $audioFiles);

        $this->assertTrue($result['success']);
        $this->assertEquals($name, $result['voice']['name']);
        $this->assertCount(3, $result['voice']['samples']);
        
        // Cleanup
        foreach ($audioFiles as $file) {
            unlink($file);
        }
    }

    public function test_large_label_set()
    {
        $name = 'Heavily Labeled Voice';
        $audioFiles = [tempnam(sys_get_temp_dir(), 'labeled_voice')];
        file_put_contents($audioFiles[0], 'audio for labeled voice');
        
        // Large set of labels
        $labels = [
            'gender' => 'female',
            'age' => 'young',
            'accent' => 'british',
            'style' => 'professional',
            'emotion' => 'calm',
            'speed' => 'medium',
            'clarity' => 'high',
            'pitch' => 'medium',
            'custom_tag_1' => 'value1',
            'custom_tag_2' => 'value2'
        ];
        
        $mockResponseData = [
            'voice_id' => 'voice_labeled',
            'name' => $name,
            'labels' => $labels
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('voices/add', Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->addVoice($name, $audioFiles, '', $labels);

        $this->assertTrue($result['success']);
        $this->assertEquals($labels, $result['voice']['labels']);
        
        unlink($audioFiles[0]);
    }
}
