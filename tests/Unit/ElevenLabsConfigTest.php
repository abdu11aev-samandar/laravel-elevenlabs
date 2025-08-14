<?php

namespace Samandar\LaravelElevenLabs\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\ElevenLabsService;

class ElevenLabsConfigTest extends TestCase
{
    public function testConfigDefaultValues()
    {
        // Test that default configuration values are properly set
        $config = include __DIR__ . '/../../config/elevenlabs.php';
        
        $this->assertEquals('https://api.elevenlabs.io/v1/', $config['base_uri']);
        $this->assertEquals(30, $config['timeout']);
        $this->assertEquals(false, $config['log_requests']);
        
        // Test sound generation defaults
        $this->assertArrayHasKey('sound_generation', $config);
        $this->assertEquals(3, $config['sound_generation']['default_duration']);
        $this->assertEquals(22, $config['sound_generation']['max_duration']);
        $this->assertEquals(0.5, $config['sound_generation']['min_duration']);
        
        // Test audio isolation defaults
        $this->assertArrayHasKey('audio_isolation', $config);
        $this->assertTrue($config['audio_isolation']['enabled']);
        $this->assertContains('wav', $config['audio_isolation']['supported_formats']);
        $this->assertContains('mp3', $config['audio_isolation']['supported_formats']);
        
        // Test conversational AI defaults
        $this->assertArrayHasKey('conversational_ai', $config);
        $this->assertEquals(7, $config['conversational_ai']['default_turn_timeout']);
        $this->assertEquals(600, $config['conversational_ai']['max_conversation_duration']);
        $this->assertFalse($config['conversational_ai']['enable_batch_calling']);
        
        // Test voice preview defaults
        $this->assertArrayHasKey('voice_preview', $config);
        $this->assertEquals(500, $config['voice_preview']['max_text_length']);
        $this->assertEquals(3, $config['voice_preview']['default_preview_count']);
    }
    
    public function testDefaultVoiceSettings()
    {
        $config = include __DIR__ . '/../../config/elevenlabs.php';
        
        $voiceSettings = $config['default_voice_settings'];
        
        $this->assertArrayHasKey('stability', $voiceSettings);
        $this->assertArrayHasKey('similarity_boost', $voiceSettings);
        $this->assertArrayHasKey('style', $voiceSettings);
        $this->assertArrayHasKey('use_speaker_boost', $voiceSettings);
        
        // Test that defaults are reasonable
        $this->assertEquals(0.5, $voiceSettings['stability']);
        $this->assertEquals(0.5, $voiceSettings['similarity_boost']);
        $this->assertEquals(0.5, $voiceSettings['style']);
        $this->assertTrue($voiceSettings['use_speaker_boost']);
    }
    
    public function testConfigArrayStructure()
    {
        $config = include __DIR__ . '/../../config/elevenlabs.php';
        
        // Ensure all required top-level keys exist
        $requiredKeys = [
            'api_key', 'base_uri', 'default_voice_settings', 'default_voice_id', 
            'default_model', 'audio_storage_path', 'timeout', 'log_requests',
            'sound_generation', 'audio_isolation', 'conversational_ai', 'voice_preview'
        ];
        
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $config, "Missing required config key: $key");
        }
    }
    
    public function testSoundGenerationLimits()
    {
        $config = include __DIR__ . '/../../config/elevenlabs.php';
        $soundConfig = $config['sound_generation'];
        
        // Test that limits make sense
        $this->assertGreaterThan($soundConfig['min_duration'], $soundConfig['default_duration']);
        $this->assertLessThan($soundConfig['max_duration'], $soundConfig['default_duration']);
        $this->assertEquals(0.5, $soundConfig['min_duration']); // ElevenLabs API minimum
        $this->assertEquals(22, $soundConfig['max_duration']); // ElevenLabs API maximum
    }
    
    public function testAudioIsolationSupportedFormats()
    {
        $config = include __DIR__ . '/../../config/elevenlabs.php';
        $audioConfig = $config['audio_isolation'];
        
        $expectedFormats = ['wav', 'mp3', 'flac', 'ogg'];
        $this->assertEquals($expectedFormats, $audioConfig['supported_formats']);
        
        // Test that all major audio formats are supported
        foreach ($expectedFormats as $format) {
            $this->assertContains($format, $audioConfig['supported_formats']);
        }
    }
}
