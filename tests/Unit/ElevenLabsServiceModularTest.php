<?php

namespace Samandar\LaravelElevenLabs\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\ElevenLabsService;
use Samandar\LaravelElevenLabs\Services\Audio\AudioService;
use Samandar\LaravelElevenLabs\Services\Voice\VoiceService;
use Samandar\LaravelElevenLabs\Services\AI\AIService;
use Samandar\LaravelElevenLabs\Services\Studio\StudioService;
use Samandar\LaravelElevenLabs\Services\Analytics\AnalyticsService;
use Samandar\LaravelElevenLabs\Services\Core\WorkspaceService;
use Mockery;
use Illuminate\Http\UploadedFile;

class ElevenLabsServiceModularTest extends TestCase
{
    protected $service;
    protected $apiKey = 'test-api-key';
    protected $mockAudioService;
    protected $mockVoiceService;
    protected $mockAIService;
    protected $mockStudioService;
    protected $mockAnalyticsService;
    protected $mockWorkspaceService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create the main service
        $this->service = new ElevenLabsService($this->apiKey);
        
        // Create mock services
        $this->mockAudioService = Mockery::mock(AudioService::class);
        $this->mockVoiceService = Mockery::mock(VoiceService::class);
        $this->mockAIService = Mockery::mock(AIService::class);
        $this->mockStudioService = Mockery::mock(StudioService::class);
        $this->mockAnalyticsService = Mockery::mock(AnalyticsService::class);
        $this->mockWorkspaceService = Mockery::mock(WorkspaceService::class);
        
        // Inject mock services using reflection
        $this->injectMockService('audio', $this->mockAudioService);
        $this->injectMockService('voice', $this->mockVoiceService);
        $this->injectMockService('ai', $this->mockAIService);
        $this->injectMockService('studio', $this->mockStudioService);
        $this->injectMockService('analytics', $this->mockAnalyticsService);
        $this->injectMockService('workspace', $this->mockWorkspaceService);
    }
    
    protected function injectMockService(string $property, $mockService): void
    {
        $reflection = new \ReflectionClass($this->service);
        $serviceProperty = $reflection->getProperty($property);
        $serviceProperty->setAccessible(true);
        $serviceProperty->setValue($this->service, $mockService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // Test service getters
    public function testAudioServiceGetter()
    {
        $this->assertInstanceOf(AudioService::class, $this->service->audio());
    }

    public function testVoiceServiceGetter()
    {
        $this->assertInstanceOf(VoiceService::class, $this->service->voice());
    }

    public function testAIServiceGetter()
    {
        $this->assertInstanceOf(AIService::class, $this->service->ai());
    }

    public function testStudioServiceGetter()
    {
        $this->assertInstanceOf(StudioService::class, $this->service->studio());
    }

    public function testAnalyticsServiceGetter()
    {
        $this->assertInstanceOf(AnalyticsService::class, $this->service->analytics());
    }

    public function testWorkspaceServiceGetter()
    {
        $this->assertInstanceOf(WorkspaceService::class, $this->service->workspace());
    }

    // Test backward compatibility methods delegation
    public function testTextToSpeechDelegation()
    {
        $expectedResult = [
            'success' => true,
            'audio' => 'fake-audio-data',
            'content_type' => 'audio/mpeg'
        ];
        
        $this->mockAudioService
            ->shouldReceive('textToSpeech')
            ->once()
            ->with('Hello World', '21m00Tcm4TlvDq8ikWAM', [])
            ->andReturn($expectedResult);

        $result = $this->service->textToSpeech('Hello World');

        $this->assertTrue($result['success']);
        $this->assertEquals('fake-audio-data', $result['audio']);
        $this->assertEquals('audio/mpeg', $result['content_type']);
    }

    public function testTextToSpeechAndSaveDelegation()
    {
        $expectedResult = [
            'success' => true,
            'saved' => true,
            'file_path' => '/path/to/file.mp3'
        ];
        
        $this->mockAudioService
            ->shouldReceive('textToSpeechAndSave')
            ->once()
            ->with('Hello World', '/path/to/file.mp3', '21m00Tcm4TlvDq8ikWAM', [])
            ->andReturn($expectedResult);

        $result = $this->service->textToSpeechAndSave('Hello World', '/path/to/file.mp3');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['saved']);
    }

    public function testSpeechToTextDelegation()
    {
        $mockFile = Mockery::mock(UploadedFile::class);
        $expectedResult = [
            'success' => true,
            'transcription' => ['text' => 'Hello world', 'language' => 'en']
        ];
        
        $this->mockAudioService
            ->shouldReceive('speechToText')
            ->once()
            ->with($mockFile, 'whisper-1')
            ->andReturn($expectedResult);

        $result = $this->service->speechToText($mockFile);

        $this->assertTrue($result['success']);
        $this->assertEquals(['text' => 'Hello world', 'language' => 'en'], $result['transcription']);
    }

    public function testSaveAudioToFileDelegation()
    {
        $this->mockAudioService
            ->shouldReceive('saveAudioToFile')
            ->once()
            ->with('audio-content', '/path/to/file.mp3')
            ->andReturn(true);

        $result = $this->service->saveAudioToFile('audio-content', '/path/to/file.mp3');

        $this->assertTrue($result);
    }

    public function testGetVoicesDelegation()
    {
        $expectedResult = [
            'success' => true,
            'voices' => [
                ['voice_id' => '123', 'name' => 'Test Voice 1'],
                ['voice_id' => '456', 'name' => 'Test Voice 2'],
            ]
        ];
        
        $this->mockVoiceService
            ->shouldReceive('getVoices')
            ->once()
            ->andReturn($expectedResult);

        $result = $this->service->getVoices();

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['voices']);
    }

    public function testGetVoiceDelegation()
    {
        $voiceId = 'test-voice-id';
        $expectedResult = [
            'success' => true,
            'voice' => ['voice_id' => $voiceId, 'name' => 'Test Voice']
        ];
        
        $this->mockVoiceService
            ->shouldReceive('getVoice')
            ->once()
            ->with($voiceId)
            ->andReturn($expectedResult);

        $result = $this->service->getVoice($voiceId);

        $this->assertTrue($result['success']);
        $this->assertEquals($voiceId, $result['voice']['voice_id']);
    }

    public function testEditVoiceSettingsDelegation()
    {
        $voiceId = 'test-voice-id';
        $voiceSettings = ['stability' => 0.7, 'similarity_boost' => 0.8];
        $expectedResult = ['success' => true];
        
        $this->mockVoiceService
            ->shouldReceive('editVoiceSettings')
            ->once()
            ->with($voiceId, $voiceSettings)
            ->andReturn($expectedResult);

        $result = $this->service->editVoiceSettings($voiceId, $voiceSettings);

        $this->assertTrue($result['success']);
    }

    public function testDeleteVoiceDelegation()
    {
        $voiceId = 'test-voice-id';
        $expectedResult = ['success' => true];
        
        $this->mockVoiceService
            ->shouldReceive('deleteVoice')
            ->once()
            ->with($voiceId)
            ->andReturn($expectedResult);

        $result = $this->service->deleteVoice($voiceId);

        $this->assertTrue($result['success']);
    }

    public function testGetSharedVoicesDelegation()
    {
        $expectedResult = [
            'success' => true,
            'voices' => [
                ['voice_id' => '1', 'name' => 'Shared Voice 1'],
                ['voice_id' => '2', 'name' => 'Shared Voice 2'],
            ]
        ];
        
        $this->mockVoiceService
            ->shouldReceive('getSharedVoices')
            ->once()
            ->andReturn($expectedResult);

        $result = $this->service->getSharedVoices();

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['voices']);
    }

    public function testGetPronunciationDictionariesDelegation()
    {
        $expectedResult = [
            'success' => true,
            'dictionaries' => [
                ['id' => '1', 'name' => 'Custom Dictionary 1'],
                ['id' => '2', 'name' => 'Custom Dictionary 2'],
            ]
        ];
        
        $this->mockVoiceService
            ->shouldReceive('getPronunciationDictionaries')
            ->once()
            ->andReturn($expectedResult);

        $result = $this->service->getPronunciationDictionaries();

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['dictionaries']);
    }

    public function testAddPronunciationDictionaryDelegation()
    {
        $name = 'Test Dictionary';
        $rules = [['string' => 'test', 'phoneme' => 'tɛst']];
        $description = 'Test description';
        $expectedResult = [
            'success' => true,
            'dictionary' => ['id' => '123', 'name' => $name]
        ];
        
        $this->mockVoiceService
            ->shouldReceive('addPronunciationDictionary')
            ->once()
            ->with($name, $rules, $description)
            ->andReturn($expectedResult);

        $result = $this->service->addPronunciationDictionary($name, $rules, $description);

        $this->assertTrue($result['success']);
        $this->assertEquals($name, $result['dictionary']['name']);
    }

    public function testGetUserInfoDelegation()
    {
        $expectedResult = [
            'success' => true,
            'user' => [
                'subscription' => ['character_count' => 10000],
                'character_limit' => 10000
            ]
        ];
        
        $this->mockAnalyticsService
            ->shouldReceive('getUserInfo')
            ->once()
            ->andReturn($expectedResult);

        $result = $this->service->getUserInfo();

        $this->assertTrue($result['success']);
        $this->assertEquals(10000, $result['user']['character_limit']);
    }

    public function testGetModelsDelegation()
    {
        $expectedResult = [
            'success' => true,
            'models' => [
                ['model_id' => 'eleven_multilingual_v2', 'name' => 'Multilingual v2'],
                ['model_id' => 'eleven_monolingual_v1', 'name' => 'Monolingual v1'],
            ]
        ];
        
        $this->mockAnalyticsService
            ->shouldReceive('getModels')
            ->once()
            ->andReturn($expectedResult);

        $result = $this->service->getModels();

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['models']);
    }

    public function testGetCharacterUsageDelegation()
    {
        $expectedResult = [
            'success' => true,
            'usage' => ['character_count' => 5000, 'character_limit' => 10000]
        ];
        
        $this->mockAnalyticsService
            ->shouldReceive('getCharacterUsage')
            ->once()
            ->andReturn($expectedResult);

        $result = $this->service->getCharacterUsage();

        $this->assertTrue($result['success']);
        $this->assertEquals(5000, $result['usage']['character_count']);
    }

    public function testGetHistoryDelegation()
    {
        $expectedResult = [
            'success' => true,
            'history' => [
                ['history_item_id' => '1', 'text' => 'Test 1'],
                ['history_item_id' => '2', 'text' => 'Test 2'],
            ]
        ];
        
        $this->mockAnalyticsService
            ->shouldReceive('getHistory')
            ->once()
            ->with(100, null)
            ->andReturn($expectedResult);

        $result = $this->service->getHistory();

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['history']);
    }

    public function testGetHistoryItemDelegation()
    {
        $historyItemId = 'test-history-id';
        $expectedResult = [
            'success' => true,
            'item' => ['history_item_id' => $historyItemId, 'text' => 'Test']
        ];
        
        $this->mockAnalyticsService
            ->shouldReceive('getHistoryItem')
            ->once()
            ->with($historyItemId)
            ->andReturn($expectedResult);

        $result = $this->service->getHistoryItem($historyItemId);

        $this->assertTrue($result['success']);
        $this->assertEquals($historyItemId, $result['item']['history_item_id']);
    }

    public function testDeleteHistoryItemDelegation()
    {
        $historyItemId = 'test-history-id';
        $expectedResult = ['success' => true];
        
        $this->mockAnalyticsService
            ->shouldReceive('deleteHistoryItem')
            ->once()
            ->with($historyItemId)
            ->andReturn($expectedResult);

        $result = $this->service->deleteHistoryItem($historyItemId);

        $this->assertTrue($result['success']);
    }

    public function testDownloadHistoryDelegation()
    {
        $historyItemIds = ['id1', 'id2'];
        $expectedResult = [
            'success' => true,
            'audio' => 'fake-audio-data',
            'content_type' => 'audio/mpeg'
        ];
        
        $this->mockAnalyticsService
            ->shouldReceive('downloadHistory')
            ->once()
            ->with($historyItemIds)
            ->andReturn($expectedResult);

        $result = $this->service->downloadHistory($historyItemIds);

        $this->assertTrue($result['success']);
        $this->assertEquals('fake-audio-data', $result['audio']);
    }

    public function testGetConversationalAISettingsDelegation()
    {
        $expectedResult = [
            'success' => true,
            'settings' => ['setting1' => 'value1', 'setting2' => 'value2']
        ];
        
        $this->mockAIService
            ->shouldReceive('getConversationalAISettings')
            ->once()
            ->andReturn($expectedResult);

        $result = $this->service->getConversationalAISettings();

        $this->assertTrue($result['success']);
        $this->assertEquals('value1', $result['settings']['setting1']);
    }

    public function testUpdateConversationalAISettingsDelegation()
    {
        $settings = ['setting1' => 'new_value1'];
        $expectedResult = ['success' => true];
        
        $this->mockAIService
            ->shouldReceive('updateConversationalAISettings')
            ->once()
            ->with($settings)
            ->andReturn($expectedResult);

        $result = $this->service->updateConversationalAISettings($settings);

        $this->assertTrue($result['success']);
    }

    public function testGetWorkspaceSecretsDelegation()
    {
        $expectedResult = [
            'success' => true,
            'secrets' => ['secret1', 'secret2']
        ];
        
        $this->mockAIService
            ->shouldReceive('getWorkspaceSecrets')
            ->once()
            ->andReturn($expectedResult);

        $result = $this->service->getWorkspaceSecrets();

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['secrets']);
    }

    public function testCreateKnowledgeBaseFromURLDelegation()
    {
        $url = 'https://example.com/docs';
        $expectedResult = [
            'success' => true,
            'knowledge_base' => ['id' => '123', 'url' => $url]
        ];
        
        $this->mockAIService
            ->shouldReceive('createKnowledgeBaseFromURL')
            ->once()
            ->with($url)
            ->andReturn($expectedResult);

        $result = $this->service->createKnowledgeBaseFromURL($url);

        $this->assertTrue($result['success']);
        $this->assertEquals($url, $result['knowledge_base']['url']);
    }

    public function testGetKnowledgeBasesDelegation()
    {
        $expectedResult = [
            'success' => true,
            'knowledge_bases' => [
                ['id' => '1', 'name' => 'KB1'],
                ['id' => '2', 'name' => 'KB2'],
            ]
        ];
        
        $this->mockAIService
            ->shouldReceive('getKnowledgeBases')
            ->once()
            ->with(null, null)
            ->andReturn($expectedResult);

        $result = $this->service->getKnowledgeBases();

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['knowledge_bases']);
    }

    public function testDeleteKnowledgeBaseDelegation()
    {
        $documentationId = 'test-kb-id';
        $expectedResult = ['success' => true];
        
        $this->mockAIService
            ->shouldReceive('deleteKnowledgeBase')
            ->once()
            ->with($documentationId)
            ->andReturn($expectedResult);

        $result = $this->service->deleteKnowledgeBase($documentationId);

        $this->assertTrue($result['success']);
    }

    public function testGetStudioProjectsDelegation()
    {
        $expectedResult = [
            'success' => true,
            'projects' => [
                ['id' => '1', 'name' => 'Project 1'],
                ['id' => '2', 'name' => 'Project 2'],
            ]
        ];
        
        $this->mockStudioService
            ->shouldReceive('getStudioProjects')
            ->once()
            ->andReturn($expectedResult);

        $result = $this->service->getStudioProjects();

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['projects']);
    }

    public function testGetStudioProjectDelegation()
    {
        $projectId = 'test-project-id';
        $expectedResult = [
            'success' => true,
            'project' => ['id' => $projectId, 'name' => 'Test Project']
        ];
        
        $this->mockStudioService
            ->shouldReceive('getStudioProject')
            ->once()
            ->with($projectId)
            ->andReturn($expectedResult);

        $result = $this->service->getStudioProject($projectId);

        $this->assertTrue($result['success']);
        $this->assertEquals($projectId, $result['project']['id']);
    }

    public function testDeleteStudioProjectDelegation()
    {
        $projectId = 'test-project-id';
        $expectedResult = ['success' => true];
        
        $this->mockStudioService
            ->shouldReceive('deleteStudioProject')
            ->once()
            ->with($projectId)
            ->andReturn($expectedResult);

        $result = $this->service->deleteStudioProject($projectId);

        $this->assertTrue($result['success']);
    }

    public function testConvertStudioProjectDelegation()
    {
        $projectId = 'test-project-id';
        $expectedResult = [
            'success' => true,
            'conversion' => ['id' => '123', 'status' => 'converting']
        ];
        
        $this->mockStudioService
            ->shouldReceive('convertStudioProject')
            ->once()
            ->with($projectId)
            ->andReturn($expectedResult);

        $result = $this->service->convertStudioProject($projectId);

        $this->assertTrue($result['success']);
        $this->assertEquals('converting', $result['conversion']['status']);
    }
}
