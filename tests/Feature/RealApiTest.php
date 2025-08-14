<?php

namespace Samandar\LaravelElevenLabs\Tests\Feature;

use PHPUnit\Framework\TestCase;
use Samandar\LaravelElevenLabs\Services\ElevenLabsService;
use Samandar\LaravelElevenLabs\Services\Audio\AudioService;
use Samandar\LaravelElevenLabs\Services\Voice\VoiceService;
use Samandar\LaravelElevenLabs\Services\Analytics\AnalyticsService;
use Samandar\LaravelElevenLabs\Services\AI\AIService;
use Samandar\LaravelElevenLabs\Services\Studio\StudioService;
use Samandar\LaravelElevenLabs\Services\Core\WorkspaceService;

/**
 * @group real-api
 * @group external
 */
class RealApiTest extends TestCase
{
    private ElevenLabsService $service;
    private string $outputDir;
    private array $createdVoiceIds = [];
    private array $createdKnowledgeBaseIds = [];
    private array $createdAgentIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        $apiKey = env('ELEVENLABS_API_KEY');
        
        if (empty($apiKey) || $apiKey === 'test-key') {
            $this->markTestSkipped('Real API key required. Set ELEVENLABS_API_KEY environment variable.');
        }
        
        $this->service = new ElevenLabsService($apiKey);
        $this->outputDir = __DIR__ . '/../../storage/test_output';
        
        // Create output directory if it doesn't exist
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Cleanup created resources to avoid API pollution
        $this->cleanupCreatedResources();
        parent::tearDown();
    }

    /**
     * @group analytics
     */
    public function test_can_get_user_info(): void
    {
        $result = $this->service->analytics()->getUserInfo();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success'] ?? false, $result['error'] ?? 'Unknown error');
        
        if ($result['success']) {
            $this->assertArrayHasKey('user', $result);
            $this->assertArrayHasKey('user_id', $result['user']);
            $this->assertArrayHasKey('xi_api_key', $result['user']);
        }
    }

    /**
     * @group analytics
     */
    public function test_can_get_user_subscription(): void
    {
        $result = $this->service->analytics()->getUserSubscription();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success'] ?? false, $result['error'] ?? 'Unknown error');
        
        if ($result['success']) {
            $this->assertArrayHasKey('subscription', $result);
            $subscription = $result['subscription'];
            $this->assertArrayHasKey('tier', $subscription);
            $this->assertArrayHasKey('character_count', $subscription);
            $this->assertArrayHasKey('character_limit', $subscription);
        }
    }

    /**
     * @group analytics
     */
    public function test_can_get_models(): void
    {
        $result = $this->service->analytics()->getModels();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success'] ?? false, $result['error'] ?? 'Unknown error');
        $this->assertIsArray($result['models'] ?? []);
        $this->assertNotEmpty($result['models']);
    }

    /**
     * @group analytics
     */
    public function test_can_get_character_usage(): void
    {
        $result = $this->service->analytics()->getCharacterUsage();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success'] ?? false, $result['error'] ?? 'Unknown error');
        $this->assertArrayHasKey('history', $result);
    }

    /**
     * @group analytics
     */
    public function test_can_get_history(): void
    {
        $result = $this->service->analytics()->getHistory(5);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success'] ?? false, $result['error'] ?? 'Unknown error');
        $this->assertArrayHasKey('history', $result);
        $this->assertIsArray($result['history']);
    }

    /**
     * @group voice
     */
    public function test_can_get_voices(): void
    {
        $result = $this->service->voice()->getVoices();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success'] ?? false, $result['error'] ?? 'Unknown error');
        $this->assertArrayHasKey('voices', $result);
        $this->assertIsArray($result['voices']);
        $this->assertNotEmpty($result['voices'], 'Should have at least some default voices');
    }

    /**
     * @group voice
     */
    public function test_can_get_specific_voice(): void
    {
        // First get available voices
        $voicesResult = $this->service->voice()->getVoices();
        $this->assertTrue($voicesResult['success'] ?? false);
        $this->assertNotEmpty($voicesResult['voices']);
        
        $voiceId = $voicesResult['voices'][0]['voice_id'];
        
        $result = $this->service->voice()->getVoice($voiceId);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success'] ?? false, $result['error'] ?? 'Unknown error');
        $this->assertEquals($voiceId, $result['voice_id'] ?? '');
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('settings', $result);
    }

    /**
     * @group voice
     */
    public function test_can_get_shared_voices(): void
    {
        $result = $this->service->voice()->getSharedVoices();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success'] ?? false, $result['error'] ?? 'Unknown error');
        $this->assertArrayHasKey('voices', $result);
        $this->assertIsArray($result['voices']);
    }

    /**
     * @group voice
     */
    public function test_can_get_pronunciation_dictionaries(): void
    {
        $result = $this->service->voice()->getPronunciationDictionaries();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success'] ?? false, $result['error'] ?? 'Unknown error');
        $this->assertArrayHasKey('pronunciation_dictionaries', $result);
        $this->assertIsArray($result['pronunciation_dictionaries']);
    }

    /**
     * @group voice
     * @group safe
     */
    public function test_can_create_voice_previews(): void
    {
        // Get a voice to test with
        $voicesResult = $this->service->voice()->getVoices();
        $this->assertTrue($voicesResult['success'] ?? false);
        $this->assertNotEmpty($voicesResult['voices']);
        
        $voiceId = $voicesResult['voices'][0]['voice_id'];
        
        $result = $this->service->voice()->createVoicePreviews(
            'This is a test message for voice preview generation.',
            $voiceId
        );
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success'] ?? false, $result['error'] ?? 'Unknown error');
        $this->assertArrayHasKey('previews', $result);
        $this->assertIsArray($result['previews']);
    }

    /**
     * @group voice
     * @group destructive
     */
    public function test_voice_cloning_workflow(): void
    {
        $this->markTestSkipped(
            'Voice cloning is destructive and requires real audio files. ' .
            'Enable manually with proper audio samples in a disposable environment.'
        );

        // This test would create a voice, which should be cleaned up
        // Uncomment and modify for actual testing with proper audio files:
        /*
        $sampleFiles = [
            // '/path/to/sample1.wav',
            // '/path/to/sample2.wav',
        ];
        
        $result = $this->service->voice()->addVoice(
            'Test Voice ' . time(),
            $sampleFiles,
            'Test voice for API testing',
            ['test' => 'true']
        );
        
        $this->assertTrue($result['success'] ?? false);
        $this->assertArrayHasKey('voice_id', $result);
        
        // Store for cleanup
        $this->createdVoiceIds[] = $result['voice_id'];
        */
    }

    /**
     * @group audio
     */
    public function test_can_generate_text_to_speech(): void
    {
        // Get a voice to test with
        $voicesResult = $this->service->voice()->getVoices();
        $this->assertTrue($voicesResult['success'] ?? false);
        
        $voiceId = $voicesResult['voices'][0]['voice_id'];
        
        $result = $this->service->audio()->textToSpeech(
            'Hello, this is a test of the ElevenLabs text-to-speech API.',
            $voiceId
        );
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success'] ?? false, $result['error'] ?? 'Unknown error');
        $this->assertArrayHasKey('audio', $result);
        $this->assertNotEmpty($result['audio']);
    }

    /**
     * @group audio
     */
    public function test_can_save_tts_to_file(): void
    {
        $voicesResult = $this->service->voice()->getVoices();
        $this->assertTrue($voicesResult['success'] ?? false);
        
        $voiceId = $voicesResult['voices'][0]['voice_id'];
        $outputPath = $this->outputDir . '/test_tts_' . time() . '.mp3';
        
        $result = $this->service->audio()->textToSpeechAndSave(
            'This test verifies file saving functionality.',
            $outputPath,
            $voiceId
        );
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success'] ?? false, $result['error'] ?? 'Unknown error');
        $this->assertFileExists($outputPath);
        $this->assertGreaterThan(0, filesize($outputPath));
        
        // Cleanup
        if (file_exists($outputPath)) {
            unlink($outputPath);
        }
    }

    /**
     * @group audio
     * @group experimental
     */
    public function test_can_generate_sound_effects(): void
    {
        $result = $this->service->audio()->soundGeneration(
            'Gentle rain on leaves',
            3.0, // 3 seconds
            'ambient'
        );
        
        // Note: Sound generation might not be available on all tiers
        if (isset($result['error']) && str_contains($result['error'], 'not available')) {
            $this->markTestSkipped('Sound generation not available on current subscription tier');
        }
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success'] ?? false, $result['error'] ?? 'Unknown error');
    }

    /**
     * @group ai
     */
    public function test_can_get_conversational_ai_settings(): void
    {
        $result = $this->service->ai()->getConversationalAISettings();
        
        $this->assertIsArray($result);
        // Note: This might return success=false if conversational AI is not enabled
        if (!($result['success'] ?? false)) {
            $this->markTestSkipped('Conversational AI not available on current subscription');
        }
    }

    /**
     * @group ai
     */
    public function test_can_get_knowledge_bases(): void
    {
        $result = $this->service->ai()->getKnowledgeBases();
        
        $this->assertIsArray($result);
        if (!($result['success'] ?? false) && isset($result['error'])) {
            $this->markTestSkipped('Knowledge bases not available: ' . $result['error']);
        }
        
        $this->assertTrue($result['success'] ?? false);
        $this->assertArrayHasKey('knowledge_bases', $result);
    }

    /**
     * @group ai
     */
    public function test_can_get_agents(): void
    {
        $result = $this->service->ai()->getAgents();
        
        $this->assertIsArray($result);
        if (!($result['success'] ?? false) && isset($result['error'])) {
            $this->markTestSkipped('Agents not available: ' . $result['error']);
        }
        
        $this->assertTrue($result['success'] ?? false);
        $this->assertArrayHasKey('agents', $result);
    }

    /**
     * @group ai
     */
    public function test_can_get_conversations(): void
    {
        $result = $this->service->ai()->getConversations();
        
        $this->assertIsArray($result);
        if (!($result['success'] ?? false) && isset($result['error'])) {
            $this->markTestSkipped('Conversations not available: ' . $result['error']);
        }
        
        $this->assertTrue($result['success'] ?? false);
        $this->assertArrayHasKey('conversations', $result);
    }

    /**
     * @group studio
     */
    public function test_can_get_studio_projects(): void
    {
        $result = $this->service->studio()->getStudioProjects();
        
        $this->assertIsArray($result);
        if (!($result['success'] ?? false) && isset($result['error'])) {
            $this->markTestSkipped('Studio not available: ' . $result['error']);
        }
        
        $this->assertTrue($result['success'] ?? false);
        $this->assertArrayHasKey('projects', $result);
    }

    /**
     * @group workspace
     */
    public function test_can_get_workspace_members(): void
    {
        $result = $this->service->workspace()->getWorkspaceMembers();
        
        $this->assertIsArray($result);
        if (!($result['success'] ?? false) && isset($result['error'])) {
            $this->markTestSkipped('Workspace members not available: ' . $result['error']);
        }
        
        $this->assertTrue($result['success'] ?? false);
        $this->assertArrayHasKey('members', $result);
    }

    /**
     * @group workspace
     */
    public function test_can_get_workspace_resources(): void
    {
        $result = $this->service->workspace()->getWorkspaceResources();
        
        $this->assertIsArray($result);
        if (!($result['success'] ?? false) && isset($result['error'])) {
            $this->markTestSkipped('Workspace resources not available: ' . $result['error']);
        }
        
        $this->assertTrue($result['success'] ?? false);
    }

    /**
     * @group integration
     */
    public function test_full_tts_workflow(): void
    {
        // 1. Get available voices
        $voicesResult = $this->service->voice()->getVoices();
        $this->assertTrue($voicesResult['success'] ?? false);
        $this->assertNotEmpty($voicesResult['voices']);
        
        // 2. Select a voice
        $voice = $voicesResult['voices'][0];
        $voiceId = $voice['voice_id'];
        
        // 3. Generate speech
        $text = 'This is a comprehensive test of the ElevenLabs API integration workflow.';
        $ttsResult = $this->service->audio()->textToSpeech($text, $voiceId);
        
        $this->assertTrue($ttsResult['success'] ?? false);
        $this->assertArrayHasKey('audio', $ttsResult);
        
        // 4. Save to file
        $outputPath = $this->outputDir . '/workflow_test_' . time() . '.mp3';
        $saved = $this->service->audio()->saveAudioToFile($ttsResult['audio'], $outputPath);
        
        $this->assertTrue($saved);
        $this->assertFileExists($outputPath);
        
        // 5. Verify file properties
        $this->assertGreaterThan(1000, filesize($outputPath)); // Should be reasonable size
        
        // Cleanup
        if (file_exists($outputPath)) {
            unlink($outputPath);
        }
    }

    /**
     * Clean up any resources created during testing
     */
    private function cleanupCreatedResources(): void
    {
        // Clean up created voices
        foreach ($this->createdVoiceIds as $voiceId) {
            try {
                $this->service->voice()->deleteVoice($voiceId);
            } catch (\Exception $e) {
                // Log but don't fail the test
                error_log("Failed to cleanup voice {$voiceId}: " . $e->getMessage());
            }
        }

        // Clean up created knowledge bases
        foreach ($this->createdKnowledgeBaseIds as $kbId) {
            try {
                $this->service->ai()->deleteKnowledgeBase($kbId);
            } catch (\Exception $e) {
                error_log("Failed to cleanup knowledge base {$kbId}: " . $e->getMessage());
            }
        }

        // Clean up created agents
        foreach ($this->createdAgentIds as $agentId) {
            try {
                $this->service->ai()->deleteAgent($agentId);
            } catch (\Exception $e) {
                error_log("Failed to cleanup agent {$agentId}: " . $e->getMessage());
            }
        }
    }

    /**
     * Verify API endpoints align with current ElevenLabs documentation
     * 
     * @group endpoint-verification
     */
    public function test_api_endpoints_are_current(): void
    {
        // This test verifies that key API endpoints return expected structure
        // by making actual calls and checking response format
        
        $endpointTests = [
            'user' => fn() => $this->service->analytics()->getUserInfo(),
            'voices' => fn() => $this->service->voice()->getVoices(),
            'models' => fn() => $this->service->analytics()->getModels(),
            'user/subscription' => fn() => $this->service->analytics()->getUserSubscription(),
        ];

        foreach ($endpointTests as $endpoint => $testFunction) {
            $result = $testFunction();
            
            // Verify the response has expected structure
            $this->assertIsArray($result, "Endpoint {$endpoint} should return an array");
            $this->assertArrayHasKey('success', $result, "Endpoint {$endpoint} should have success key");
            
            if ($result['success']) {
                // Check for expected data structure based on endpoint
                switch ($endpoint) {
                    case 'user':
                        $this->assertArrayHasKey('user', $result, 'User endpoint should contain user data');
                        break;
                    case 'voices':
                        $this->assertArrayHasKey('voices', $result, 'Voices endpoint should contain voices array');
                        break;
                    case 'models':
                        $this->assertArrayHasKey('models', $result, 'Models endpoint should contain models array');
                        break;
                    case 'user/subscription':
                        $this->assertArrayHasKey('subscription', $result, 'Subscription endpoint should contain subscription data');
                        break;
                }
            } else {
                // Even failures should have expected error structure
                $this->assertArrayHasKey('error', $result, "Failed endpoint {$endpoint} should have error key");
            }
        }
        
        // Verify base URL structure (through reflection to check service configuration)
        $reflection = new \ReflectionClass($this->service->analytics());
        $baseUrlProperty = $reflection->getParentClass()->getProperty('baseUrl');
        $baseUrlProperty->setAccessible(true);
        $baseUrl = $baseUrlProperty->getValue($this->service->analytics());
        
        $this->assertEquals('https://api.elevenlabs.io/v1/', $baseUrl, 'Base URL should match expected ElevenLabs API URL');
    }
}
