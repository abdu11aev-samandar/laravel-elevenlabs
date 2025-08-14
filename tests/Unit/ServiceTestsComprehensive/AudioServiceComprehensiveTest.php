<?php

namespace Samandar\LaravelElevenLabs\Tests\Unit\ServiceTestsComprehensive;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\Audio\AudioService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\UploadedFile;
use Mockery;

/**
 * Comprehensive test coverage for AudioService
 * 
 * @group audio
 * @group comprehensive-coverage
 * @group unit
 */
class AudioServiceComprehensiveTest extends TestCase
{
    protected AudioService $service;
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
            
        $this->service = new AudioService($this->apiKey);
        
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
    // Audio Native Project Tests
    // =====================================

    public function test_createAudioNativeProject_with_file()
    {
        $name = 'Test Project';
        $options = [
            'author' => 'Test Author',
            'title' => 'Test Title',
            'voice_id' => 'voice_123',
            'auto_convert' => true
        ];
        
        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'audio_test');
        file_put_contents($tempFile, 'fake audio content');
        
        $mockResponseData = [
            'project_id' => 'proj_123',
            'name' => $name,
            'status' => 'processing'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('audio-native', Mockery::on(function ($options) use ($name) {
                return isset($options['multipart']) &&
                       isset($options['headers']['xi-api-key']) &&
                       $options['headers']['xi-api-key'] === $this->apiKey;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->createAudioNativeProject($name, $options, $tempFile);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['project']);
        
        // Clean up
        unlink($tempFile);
    }

    public function test_createAudioNativeProject_with_uploaded_file()
    {
        $name = 'Test Project';
        
        // Mock UploadedFile
        $mockUploadedFile = Mockery::mock(UploadedFile::class);
        $mockUploadedFile->shouldReceive('getPathname')->andReturn('/tmp/fake_path');
        $mockUploadedFile->shouldReceive('getClientOriginalName')->andReturn('test.mp3');
        
        $mockResponseData = [
            'project_id' => 'proj_456',
            'name' => $name,
            'status' => 'created'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('audio-native', Mockery::on(function ($options) {
                return isset($options['multipart']) &&
                       isset($options['headers']['xi-api-key']);
            }))
            ->andReturn($mockResponse);

        $result = $this->service->createAudioNativeProject($name, [], $mockUploadedFile);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['project']);
    }

    public function test_createAudioNativeProject_without_file()
    {
        $name = 'Text Only Project';
        $options = ['author' => 'Test Author'];
        
        $mockResponseData = [
            'project_id' => 'proj_789',
            'name' => $name,
            'status' => 'ready'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('audio-native', Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->createAudioNativeProject($name, $options);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['project']);
    }

    public function test_getAudioNativeSettings_success()
    {
        $projectId = 'proj_123';
        $mockResponseData = [
            'project_id' => $projectId,
            'settings' => [
                'background_color' => '#ffffff',
                'text_color' => '#000000',
                'voice_id' => 'voice_456'
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("audio-native/{$projectId}/settings")
            ->andReturn($mockResponse);

        $result = $this->service->getAudioNativeSettings($projectId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['settings']);
    }

    // =====================================
    // Text-to-Speech Tests
    // =====================================

    public function test_textToSpeech_success()
    {
        $text = 'Hello, this is a test message.';
        $voiceId = 'voice_123';
        $voiceSettings = ['stability' => 0.7, 'similarity_boost' => 0.8];
        $audioData = 'fake-binary-audio-data';
        
        $mockResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], $audioData);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("text-to-speech/{$voiceId}", Mockery::on(function ($options) use ($text, $voiceSettings) {
                return isset($options['json']) &&
                       $options['json']['text'] === $text &&
                       isset($options['json']['voice_settings']);
            }))
            ->andReturn($mockResponse);

        $result = $this->service->textToSpeech($text, $voiceId, $voiceSettings);

        $this->assertTrue($result['success']);
        $this->assertEquals($audioData, $result['audio']);
        $this->assertEquals('audio/mpeg', $result['content_type']);
    }

    public function test_textToSpeech_with_defaults()
    {
        $text = 'Test with default settings';
        $audioData = 'fake-audio-data';
        
        $mockResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], $audioData);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with(Mockery::any(), Mockery::on(function ($options) use ($text) {
                return isset($options['json']) &&
                       $options['json']['text'] === $text &&
                       isset($options['json']['voice_settings']) &&
                       isset($options['json']['model_id']);
            }))
            ->andReturn($mockResponse);

        $result = $this->service->textToSpeech($text);

        $this->assertTrue($result['success']);
        $this->assertEquals($audioData, $result['audio']);
    }

    // =====================================
    // Speech-to-Text Tests
    // =====================================

    public function test_speechToText_with_file_path()
    {
        $audioFile = tempnam(sys_get_temp_dir(), 'speech_test');
        file_put_contents($audioFile, 'fake audio content');
        
        $mockResponseData = [
            'text' => 'This is the transcribed text',
            'confidence' => 0.95
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('speech-to-text', Mockery::on(function ($options) {
                return isset($options['multipart']) &&
                       isset($options['headers']['xi-api-key']);
            }))
            ->andReturn($mockResponse);

        $result = $this->service->speechToText($audioFile);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['transcription']);
        
        unlink($audioFile);
    }

    public function test_speechToText_with_uploaded_file()
    {
        $mockUploadedFile = Mockery::mock(UploadedFile::class);
        $mockUploadedFile->shouldReceive('getPathname')->andReturn('/tmp/fake_audio.wav');
        $mockUploadedFile->shouldReceive('getClientOriginalName')->andReturn('test_audio.wav');
        
        $mockResponseData = [
            'text' => 'Transcribed from uploaded file',
            'language' => 'en'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('speech-to-text', Mockery::on(function ($options) {
                return isset($options['multipart']);
            }))
            ->andReturn($mockResponse);

        $result = $this->service->speechToText($mockUploadedFile, 'whisper-2');

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['transcription']);
    }

    // =====================================
    // Speech-to-Speech Tests
    // =====================================

    public function test_speechToSpeech_success()
    {
        $voiceId = 'voice_123';
        $audioFile = tempnam(sys_get_temp_dir(), 'sts_test');
        file_put_contents($audioFile, 'source audio');
        $voiceSettings = ['stability' => 0.6];
        $resultAudioData = 'converted-audio-data';
        
        $mockResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], $resultAudioData);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("speech-to-speech/{$voiceId}", Mockery::on(function ($options) {
                return isset($options['multipart']) &&
                       isset($options['headers']['xi-api-key']);
            }))
            ->andReturn($mockResponse);

        $result = $this->service->speechToSpeech($voiceId, $audioFile, 'eleven_multilingual_sts_v2', $voiceSettings);

        $this->assertTrue($result['success']);
        $this->assertEquals($resultAudioData, $result['audio']);
        $this->assertEquals('audio/mpeg', $result['content_type']);
        
        unlink($audioFile);
    }

    public function test_speechToSpeech_with_uploaded_file()
    {
        $voiceId = 'voice_456';
        
        $mockUploadedFile = Mockery::mock(UploadedFile::class);
        $mockUploadedFile->shouldReceive('getPathname')->andReturn('/tmp/input.wav');
        $mockUploadedFile->shouldReceive('getClientOriginalName')->andReturn('input.wav');
        
        $resultAudioData = 'converted-audio-from-upload';
        
        $mockResponse = new Response(200, ['Content-Type' => 'audio/wav'], $resultAudioData);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("speech-to-speech/{$voiceId}", Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->speechToSpeech($voiceId, $mockUploadedFile);

        $this->assertTrue($result['success']);
        $this->assertEquals($resultAudioData, $result['audio']);
        $this->assertEquals('audio/wav', $result['content_type']);
    }

    // =====================================
    // Stream Text-to-Speech Tests
    // =====================================

    public function test_streamTextToSpeech_success()
    {
        $text = 'Streaming test message';
        $voiceId = 'voice_stream';
        
        // Mock a stream response
        $streamContent = 'chunk1chunk2chunk3';
        $mockBody = Mockery::mock(\Psr\Http\Message\StreamInterface::class);
        $mockBody->shouldReceive('eof')->andReturn(false, false, false, true);
        $mockBody->shouldReceive('read')->with(1024)->andReturn('chunk1', 'chunk2', 'chunk3');
        
        $mockResponse = Mockery::mock(\Psr\Http\Message\ResponseInterface::class);
        $mockResponse->shouldReceive('getBody')->andReturn($mockBody);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("text-to-speech/{$voiceId}/stream", Mockery::on(function ($options) use ($text) {
                return isset($options['json']) &&
                       $options['json']['text'] === $text &&
                       isset($options['stream']) &&
                       $options['stream'] === true;
            }))
            ->andReturn($mockResponse);

        $generator = $this->service->streamTextToSpeech($text, $voiceId);
        
        $chunks = [];
        foreach ($generator as $chunk) {
            $chunks[] = $chunk;
        }
        
        $this->assertEquals(['chunk1', 'chunk2', 'chunk3'], $chunks);
    }

    public function test_streamTextToSpeech_exception()
    {
        $text = 'This will fail';
        $voiceId = 'invalid_voice';
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new \Exception('Stream failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stream failed');
        
        $generator = $this->service->streamTextToSpeech($text, $voiceId);
        $generator->current(); // This should trigger the exception
    }

    // =====================================
    // File Operations Tests
    // =====================================

    public function test_saveAudioToFile_success()
    {
        $audioContent = 'binary audio data';
        $filePath = tempnam(sys_get_temp_dir(), 'save_test') . '.mp3';
        
        $result = $this->service->saveAudioToFile($audioContent, $filePath);
        
        $this->assertTrue($result);
        $this->assertFileExists($filePath);
        $this->assertEquals($audioContent, file_get_contents($filePath));
        
        unlink($filePath);
    }

    public function test_saveAudioToFile_create_directory()
    {
        $audioContent = 'test audio content';
        $directory = sys_get_temp_dir() . '/test_audio_dir_' . uniqid();
        $filePath = $directory . '/test.mp3';
        
        $result = $this->service->saveAudioToFile($audioContent, $filePath);
        
        $this->assertTrue($result);
        $this->assertDirectoryExists($directory);
        $this->assertFileExists($filePath);
        
        // Clean up
        unlink($filePath);
        rmdir($directory);
    }

    public function test_saveAudioToFile_failure()
    {
        $audioContent = 'test content';
        $invalidPath = '/invalid/path/that/does/not/exist/file.mp3';
        
        $result = $this->service->saveAudioToFile($audioContent, $invalidPath);
        
        $this->assertFalse($result);
    }

    public function test_textToSpeechAndSave_success()
    {
        $text = 'Test text to save';
        $voiceId = 'voice_123';
        $filePath = tempnam(sys_get_temp_dir(), 'tts_save') . '.mp3';
        $audioData = 'test audio binary data';
        
        $mockResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], $audioData);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("text-to-speech/{$voiceId}", Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->textToSpeechAndSave($text, $filePath, $voiceId);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['saved']);
        $this->assertEquals($filePath, $result['file_path']);
        $this->assertFileExists($filePath);
        
        unlink($filePath);
    }

    public function test_textToSpeechAndSave_tts_failure()
    {
        $text = 'This will fail';
        $filePath = '/tmp/test.mp3';
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('TTS failed', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->textToSpeechAndSave($text, $filePath);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Forced Alignment Tests
    // =====================================

    public function test_createForcedAlignment_success()
    {
        $audioFile = tempnam(sys_get_temp_dir(), 'alignment_test');
        file_put_contents($audioFile, 'fake audio');
        $text = 'Hello world';
        $language = 'en';
        
        $mockResponseData = [
            'alignment' => [
                ['word' => 'Hello', 'start' => 0.0, 'end' => 0.5],
                ['word' => 'world', 'start' => 0.6, 'end' => 1.0]
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('forced-alignment', Mockery::on(function ($options) use ($text, $language) {
                return isset($options['multipart']) &&
                       isset($options['headers']['xi-api-key']);
            }))
            ->andReturn($mockResponse);

        $result = $this->service->createForcedAlignment($audioFile, $text, $language);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['alignment']);
        
        unlink($audioFile);
    }

    public function test_createForcedAlignment_with_uploaded_file()
    {
        $mockUploadedFile = Mockery::mock(UploadedFile::class);
        $mockUploadedFile->shouldReceive('getPathname')->andReturn('/tmp/alignment.wav');
        $mockUploadedFile->shouldReceive('getClientOriginalName')->andReturn('alignment.wav');
        
        $text = 'Test alignment text';
        
        $mockResponseData = [
            'alignment' => [
                ['word' => 'Test', 'start' => 0.0, 'end' => 0.4],
                ['word' => 'alignment', 'start' => 0.5, 'end' => 1.2],
                ['word' => 'text', 'start' => 1.3, 'end' => 1.6]
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('forced-alignment', Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->createForcedAlignment($mockUploadedFile, $text);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['alignment']);
    }

    // =====================================
    // Audio Isolation Tests
    // =====================================

    public function test_audioIsolation_success()
    {
        $audioFile = tempnam(sys_get_temp_dir(), 'isolation_test');
        file_put_contents($audioFile, 'noisy audio content');
        $cleanAudioData = 'clean audio data';
        
        $mockResponse = new Response(200, ['Content-Type' => 'audio/wav'], $cleanAudioData);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('audio-native', Mockery::on(function ($options) {
                return isset($options['multipart']);
            }))
            ->andReturn($mockResponse);

        $result = $this->service->audioIsolation($audioFile);

        $this->assertTrue($result['success']);
        $this->assertEquals($cleanAudioData, $result['audio']);
        $this->assertEquals('audio/wav', $result['content_type']);
        
        unlink($audioFile);
    }

    public function test_audioIsolation_with_uploaded_file()
    {
        $mockUploadedFile = Mockery::mock(UploadedFile::class);
        $mockUploadedFile->shouldReceive('getPathname')->andReturn('/tmp/noisy.mp3');
        $mockUploadedFile->shouldReceive('getClientOriginalName')->andReturn('noisy.mp3');
        
        $cleanAudioData = 'isolated audio content';
        
        $mockResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], $cleanAudioData);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('audio-native', Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->audioIsolation($mockUploadedFile);

        $this->assertTrue($result['success']);
        $this->assertEquals($cleanAudioData, $result['audio']);
        $this->assertEquals('audio/mpeg', $result['content_type']);
    }

    // =====================================
    // Sound Generation Tests
    // =====================================

    public function test_soundGeneration_basic()
    {
        $text = 'Rain on leaves';
        $soundData = 'generated sound effect data';
        
        $mockResponse = new Response(200, ['Content-Type' => 'audio/wav'], $soundData);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('sound-generation', Mockery::on(function ($options) use ($text) {
                return isset($options['json']) &&
                       $options['json']['text'] === $text;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->soundGeneration($text);

        $this->assertTrue($result['success']);
        $this->assertEquals($soundData, $result['audio']);
        $this->assertEquals('audio/wav', $result['content_type']);
    }

    public function test_soundGeneration_with_duration_and_influence()
    {
        $text = 'Ocean waves';
        $duration = 5;
        $influence = 'calm';
        $soundData = 'ocean wave sound data';
        
        $mockResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], $soundData);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('sound-generation', Mockery::on(function ($options) use ($text, $duration, $influence) {
                return isset($options['json']) &&
                       $options['json']['text'] === $text &&
                       $options['json']['duration_seconds'] === $duration &&
                       $options['json']['prompt_influence'] === $influence;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->soundGeneration($text, $duration, $influence);

        $this->assertTrue($result['success']);
        $this->assertEquals($soundData, $result['audio']);
        $this->assertEquals('audio/mpeg', $result['content_type']);
    }

    public function test_soundGeneration_with_duration_only()
    {
        $text = 'Thunder sound';
        $duration = 3;
        $soundData = 'thunder sound data';
        
        $mockResponse = new Response(200, ['Content-Type' => 'audio/wav'], $soundData);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('sound-generation', Mockery::on(function ($options) use ($text, $duration) {
                return isset($options['json']) &&
                       $options['json']['text'] === $text &&
                       $options['json']['duration_seconds'] === $duration &&
                       !isset($options['json']['prompt_influence']);
            }))
            ->andReturn($mockResponse);

        $result = $this->service->soundGeneration($text, $duration);

        $this->assertTrue($result['success']);
        $this->assertEquals($soundData, $result['audio']);
    }

    // =====================================
    // Error Handling Tests
    // =====================================

    public function test_textToSpeech_api_error()
    {
        $text = 'Error test';
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('API limit exceeded', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->textToSpeech($text);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_speechToText_file_not_found()
    {
        $invalidFile = '/non/existent/file.wav';
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('File error', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->speechToText($invalidFile);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_createAudioNativeProject_validation_error()
    {
        $name = '';  // Invalid empty name
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Validation failed', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->createAudioNativeProject($name);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Edge Cases and Complex Scenarios
    // =====================================

    public function test_textToSpeechAndSave_save_failure()
    {
        $text = 'Test content';
        $invalidPath = '/invalid/readonly/path/file.mp3';
        $audioData = 'audio content';
        
        $mockResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], $audioData);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->textToSpeechAndSave($text, $invalidPath);

        $this->assertTrue($result['success']); // TTS succeeded
        $this->assertFalse($result['saved']); // But save failed
        $this->assertNull($result['file_path']);
    }

    public function test_createAudioNativeProject_with_boolean_options()
    {
        $name = 'Boolean Test Project';
        $options = [
            'small' => true,
            'auto_convert' => false,
            'sessionization' => true
        ];
        
        $mockResponseData = [
            'project_id' => 'proj_bool',
            'name' => $name
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('audio-native', Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->createAudioNativeProject($name, $options);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['project']);
    }

    public function test_multiple_operations_success()
    {
        // Test a complex workflow: TTS -> Save -> Isolation
        $text = 'Complex workflow test';
        $voiceId = 'voice_workflow';
        $originalAudio = 'original audio data';
        $cleanAudio = 'clean isolated audio';
        
        // Mock TTS
        $ttsResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], $originalAudio);
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("text-to-speech/{$voiceId}", Mockery::any())
            ->andReturn($ttsResponse);
        
        // Mock audio isolation
        $isolationResponse = new Response(200, ['Content-Type' => 'audio/wav'], $cleanAudio);
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('audio-native', Mockery::any())
            ->andReturn($isolationResponse);
        
        // Step 1: Generate TTS
        $ttsResult = $this->service->textToSpeech($text, $voiceId);
        $this->assertTrue($ttsResult['success']);
        
        // Step 2: Save audio
        $filePath = tempnam(sys_get_temp_dir(), 'workflow') . '.mp3';
        $saveResult = $this->service->saveAudioToFile($ttsResult['audio'], $filePath);
        $this->assertTrue($saveResult);
        
        // Step 3: Isolate audio
        $isolationResult = $this->service->audioIsolation($filePath);
        $this->assertTrue($isolationResult['success']);
        $this->assertEquals($cleanAudio, $isolationResult['audio']);
        
        // Cleanup
        unlink($filePath);
    }
}
