<?php

namespace Samandar\LaravelElevenLabs\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\Audio\AudioService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\UploadedFile;
use Mockery;

class AudioServiceNewEndpointsTest extends TestCase
{
    protected $service;
    protected $mockClient;
    protected $apiKey = 'test-api-key';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockClient = Mockery::mock(Client::class);
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

    public function testAudioIsolationWithUploadedFile()
    {
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_audio_');
        file_put_contents($tempFile, 'fake audio content');
        
        // Mock uploaded file
        $uploadedFile = Mockery::mock(UploadedFile::class);
        $uploadedFile->shouldReceive('getPathname')->andReturn($tempFile);
        $uploadedFile->shouldReceive('getClientOriginalName')->andReturn('test_audio.wav');

        // Mock successful response
        $mockResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], 'fake-audio-data');
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('audio-native', Mockery::on(function ($options) {
                return isset($options['multipart']) && 
                       is_array($options['multipart']) &&
                       count($options['multipart']) === 1;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->audioIsolation($uploadedFile);

        $this->assertTrue($result['success']);
        $this->assertEquals('fake-audio-data', $result['audio']);
        $this->assertEquals('audio/mpeg', $result['content_type']);
        
        // Clean up
        unlink($tempFile);
    }

    public function testAudioIsolationWithFilePath()
    {
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_audio_path_');
        file_put_contents($tempFile, 'fake audio content');

        // Mock successful response
        $mockResponse = new Response(200, ['Content-Type' => 'audio/wav'], 'isolated-audio-data');
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('audio-native', Mockery::on(function ($options) {
                return isset($options['multipart']) && 
                       is_array($options['multipart']);
            }))
            ->andReturn($mockResponse);

        $result = $this->service->audioIsolation($tempFile);

        $this->assertTrue($result['success']);
        $this->assertEquals('isolated-audio-data', $result['audio']);
        $this->assertEquals('audio/wav', $result['content_type']);
        
        // Clean up
        unlink($tempFile);
    }

    public function testAudioIsolationFailure()
    {
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_audio_fail_');
        file_put_contents($tempFile, 'fake audio content');
        
        $uploadedFile = Mockery::mock(UploadedFile::class);
        $uploadedFile->shouldReceive('getPathname')->andReturn($tempFile);
        $uploadedFile->shouldReceive('getClientOriginalName')->andReturn('test_audio.wav');

        // Mock error response
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('API Error', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->audioIsolation($uploadedFile);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        
        // Clean up
        unlink($tempFile);
    }

    public function testSoundGenerationBasic()
    {
        $text = "Thunder and rain storm";
        
        // Mock successful response
        $mockResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], 'generated-sound-data');
        
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
        $this->assertEquals('generated-sound-data', $result['audio']);
        $this->assertEquals('audio/mpeg', $result['content_type']);
    }

    public function testSoundGenerationWithAllParameters()
    {
        $text = "Ocean waves crashing";
        $durationSeconds = 10;
        $promptInfluence = "calm and peaceful";
        
        // Mock successful response
        $mockResponse = new Response(200, ['Content-Type' => 'audio/wav'], 'ocean-sound-data');
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('sound-generation', Mockery::on(function ($options) use ($text, $durationSeconds, $promptInfluence) {
                $json = $options['json'];
                return $json['text'] === $text && 
                       $json['duration_seconds'] === $durationSeconds &&
                       $json['prompt_influence'] === $promptInfluence;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->soundGeneration($text, $durationSeconds, $promptInfluence);

        $this->assertTrue($result['success']);
        $this->assertEquals('ocean-sound-data', $result['audio']);
        $this->assertEquals('audio/wav', $result['content_type']);
    }

    public function testSoundGenerationWithPartialParameters()
    {
        $text = "Bird chirping";
        $durationSeconds = 5;
        
        // Mock successful response
        $mockResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], 'bird-sound-data');
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('sound-generation', Mockery::on(function ($options) use ($text, $durationSeconds) {
                $json = $options['json'];
                return $json['text'] === $text && 
                       $json['duration_seconds'] === $durationSeconds &&
                       !isset($json['prompt_influence']);
            }))
            ->andReturn($mockResponse);

        $result = $this->service->soundGeneration($text, $durationSeconds);

        $this->assertTrue($result['success']);
        $this->assertEquals('bird-sound-data', $result['audio']);
    }

    public function testSoundGenerationFailure()
    {
        $text = "Invalid sound description";
        
        // Mock error response
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Invalid parameters', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->soundGeneration($text);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testSoundGenerationWithNullDuration()
    {
        $text = "Wind blowing";
        $promptInfluence = "gentle";
        
        // Mock successful response
        $mockResponse = new Response(200, ['Content-Type' => 'audio/mpeg'], 'wind-sound-data');
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('sound-generation', Mockery::on(function ($options) use ($text, $promptInfluence) {
                $json = $options['json'];
                return $json['text'] === $text && 
                       $json['prompt_influence'] === $promptInfluence &&
                       !isset($json['duration_seconds']);
            }))
            ->andReturn($mockResponse);

        $result = $this->service->soundGeneration($text, null, $promptInfluence);

        $this->assertTrue($result['success']);
        $this->assertEquals('wind-sound-data', $result['audio']);
    }
}
