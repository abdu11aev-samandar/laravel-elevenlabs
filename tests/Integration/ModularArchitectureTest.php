<?php

namespace Samandar\LaravelElevenLabs\Tests\Integration;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\ElevenLabsService;
use Samandar\LaravelElevenLabs\Services\Audio\AudioService;
use Samandar\LaravelElevenLabs\Services\Voice\VoiceService;
use Samandar\LaravelElevenLabs\Services\AI\AIService;
use Samandar\LaravelElevenLabs\Services\Studio\StudioService;
use Samandar\LaravelElevenLabs\Services\Analytics\AnalyticsService;
use Samandar\LaravelElevenLabs\Services\Core\WorkspaceService;

class ModularArchitectureTest extends TestCase
{
    protected $service;
    protected $apiKey = 'test-api-key';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ElevenLabsService($this->apiKey);
    }

    public function testServiceInstantiation()
    {
        $this->assertInstanceOf(ElevenLabsService::class, $this->service);
    }

    public function testAudioServiceInstantiation()
    {
        $audioService = $this->service->audio();
        $this->assertInstanceOf(AudioService::class, $audioService);
    }

    public function testVoiceServiceInstantiation()
    {
        $voiceService = $this->service->voice();
        $this->assertInstanceOf(VoiceService::class, $voiceService);
    }

    public function testAIServiceInstantiation()
    {
        $aiService = $this->service->ai();
        $this->assertInstanceOf(AIService::class, $aiService);
    }

    public function testStudioServiceInstantiation()
    {
        $studioService = $this->service->studio();
        $this->assertInstanceOf(StudioService::class, $studioService);
    }

    public function testAnalyticsServiceInstantiation()
    {
        $analyticsService = $this->service->analytics();
        $this->assertInstanceOf(AnalyticsService::class, $analyticsService);
    }

    public function testWorkspaceServiceInstantiation()
    {
        $workspaceService = $this->service->workspace();
        $this->assertInstanceOf(WorkspaceService::class, $workspaceService);
    }

    public function testAllServicesHaveApiKey()
    {
        $reflection = new \ReflectionClass($this->service->audio());
        $apiKeyProperty = $reflection->getProperty('apiKey');
        $apiKeyProperty->setAccessible(true);
        $this->assertEquals($this->apiKey, $apiKeyProperty->getValue($this->service->audio()));
    }

    public function testConsistentServiceInstances()
    {
        // Test that calling the getter multiple times returns the same instance
        $audioService1 = $this->service->audio();
        $audioService2 = $this->service->audio();
        
        $this->assertSame($audioService1, $audioService2);
    }

    public function testNewServiceInstanceGetsFreshInstances()
    {
        $newService = new ElevenLabsService($this->apiKey);
        
        $this->assertNotSame(
            $this->service->audio(),
            $newService->audio()
        );
    }

    public function testBackwardCompatibilityMethodsExist()
    {
        // Test that all backward compatibility methods exist
        $this->assertTrue(method_exists($this->service, 'textToSpeech'));
        $this->assertTrue(method_exists($this->service, 'speechToText'));
        $this->assertTrue(method_exists($this->service, 'getVoices'));
        $this->assertTrue(method_exists($this->service, 'getUserInfo'));
        $this->assertTrue(method_exists($this->service, 'getModels'));
        $this->assertTrue(method_exists($this->service, 'getConversationalAISettings'));
        $this->assertTrue(method_exists($this->service, 'getStudioProjects'));
        $this->assertTrue(method_exists($this->service, 'getHistory'));
    }

    public function testModularMethodsAreAccessible()
    {
        // Test that new modular approach methods are accessible
        $this->assertTrue(method_exists($this->service->audio(), 'textToSpeech'));
        $this->assertTrue(method_exists($this->service->voice(), 'getVoices'));
        $this->assertTrue(method_exists($this->service->ai(), 'getConversationalAISettings'));
        $this->assertTrue(method_exists($this->service->studio(), 'getStudioProjects'));
        $this->assertTrue(method_exists($this->service->analytics(), 'getUserInfo'));
        $this->assertTrue(method_exists($this->service->workspace(), 'shareWorkspaceResource'));
    }
}
