<?php

/**
 * ElevenLabs Laravel Package - Modular Usage Example
 * 
 * This example demonstrates how to use the new modular architecture
 * which organizes functionality by feature groups for better maintainability.
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Samandar\LaravelElevenLabs\Facades\ElevenLabs;

class ElevenLabsModularExampleController extends Controller
{
    /**
     * Example using Audio Service for Text-to-Speech operations
     */
    public function audioServiceExample(Request $request)
    {
        // 🎵 AUDIO SERVICE - Text-to-Speech, Speech-to-Text, etc.
        
        // Text-to-Speech with the new modular approach
        $result = ElevenLabs::audio()->textToSpeech(
            'Hello from the modular Audio service!',
            '21m00Tcm4TlvDq8ikWAM', // Voice ID
            [
                'stability' => 0.7,
                'similarity_boost' => 0.8,
                'style' => 0.6
            ]
        );

        if ($result['success']) {
            // Save audio to file using the audio service
            $filePath = storage_path('app/audio/example_audio.mp3');
            $saved = ElevenLabs::audio()->saveAudioToFile($result['audio'], $filePath);
            
            if ($saved) {
                return response()->json([
                    'message' => 'Audio generated and saved successfully',
                    'file_path' => $filePath
                ]);
            }
        }

        // Speech-to-Text example
        if ($request->hasFile('audio_file')) {
            $transcription = ElevenLabs::audio()->speechToText(
                $request->file('audio_file')
            );
            
            return response()->json($transcription);
        }

        // Streaming TTS example
        $stream = ElevenLabs::audio()->streamTextToSpeech('Streaming audio example');
        
        return response()->stream(function() use ($stream) {
            foreach ($stream as $chunk) {
                echo $chunk;
                ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'audio/mpeg',
            'Cache-Control' => 'no-cache'
        ]);
    }

    /**
     * Example using Voice Service for voice management
     */
    public function voiceServiceExample()
    {
        // 🎭 VOICE SERVICE - Voice management, pronunciation, etc.
        
        // Get all available voices
        $voices = ElevenLabs::voice()->getVoices();
        
        if ($voices['success']) {
            foreach ($voices['voices'] as $voice) {
                echo "Voice: {$voice['name']} (ID: {$voice['voice_id']})\n";
            }
        }

        // Get shared voices from community
        $sharedVoices = ElevenLabs::voice()->getSharedVoices();
        
        // Get pronunciation dictionaries
        $dictionaries = ElevenLabs::voice()->getPronunciationDictionaries();

        return response()->json([
            'voices' => $voices,
            'shared_voices' => $sharedVoices,
            'dictionaries' => $dictionaries
        ]);
    }

    /**
     * Example using AI Service for conversational AI and knowledge base
     */
    public function aiServiceExample()
    {
        // 🤖 AI SERVICE - Conversational AI, Knowledge Base, etc.
        
        // Get conversational AI settings
        $settings = ElevenLabs::ai()->getConversationalAISettings();
        
        // Create knowledge base from URL
        $knowledgeBase = ElevenLabs::ai()->createKnowledgeBaseFromURL(
            'https://docs.example.com'
        );

        // Get all knowledge bases
        $allKnowledgeBases = ElevenLabs::ai()->getKnowledgeBases();

        // Get workspace secrets
        $secrets = ElevenLabs::ai()->getWorkspaceSecrets();

        return response()->json([
            'ai_settings' => $settings,
            'new_knowledge_base' => $knowledgeBase,
            'all_knowledge_bases' => $allKnowledgeBases,
            'workspace_secrets' => $secrets
        ]);
    }

    /**
     * Example using Studio Service for projects and dubbing
     */
    public function studioServiceExample(Request $request)
    {
        // 🎬 STUDIO SERVICE - Projects, Dubbing, Podcasts, etc.
        
        // Get all studio projects
        $projects = ElevenLabs::studio()->getStudioProjects();

        // Create a new studio project if file is uploaded
        if ($request->hasFile('source_file')) {
            $newProject = ElevenLabs::studio()->createStudioProject(
                $request->file('source_file'),
                'My New Studio Project'
            );
            
            if ($newProject['success']) {
                // Convert the project
                $conversion = ElevenLabs::studio()->convertStudioProject(
                    $newProject['project']['id']
                );
            }
        }

        // Create dubbing if audio/video file is provided
        if ($request->hasFile('dubbing_file')) {
            $dubbing = ElevenLabs::studio()->createDubbing(
                $request->file('dubbing_file'),
                'spanish', // target language
                'english', // source language
                2 // number of speakers
            );
            
            if ($dubbing['success']) {
                // Get dubbing status
                $dubbingStatus = ElevenLabs::studio()->getDubbing($dubbing['id']);
                
                return response()->json([
                    'dubbing_created' => $dubbing,
                    'dubbing_status' => $dubbingStatus
                ]);
            }
        }

        return response()->json(['projects' => $projects]);
    }

    /**
     * Example using Analytics Service for usage statistics and history
     */
    public function analyticsServiceExample()
    {
        // 📊 ANALYTICS SERVICE - Usage, History, Models, etc.
        
        // Get user information and subscription details
        $userInfo = ElevenLabs::analytics()->getUserInfo();
        
        // Get character usage statistics
        $usage = ElevenLabs::analytics()->getCharacterUsage();
        
        // Get available models
        $models = ElevenLabs::analytics()->getModels();
        
        // Get generation history
        $history = ElevenLabs::analytics()->getHistory(20); // Last 20 generations
        
        // Download multiple history items
        if (!empty($history['history'])) {
            $historyIds = array_slice(
                array_column($history['history'], 'history_item_id'),
                0,
                3
            );
            
            $downloadResult = ElevenLabs::analytics()->downloadHistory($historyIds);
        }

        return response()->json([
            'user_info' => $userInfo,
            'usage_stats' => $usage,
            'models' => $models,
            'history' => $history,
            'history_download' => $downloadResult ?? null
        ]);
    }

    /**
     * Example using Workspace Service for collaboration
     */
    public function workspaceServiceExample()
    {
        // 🤝 WORKSPACE SERVICE - Collaboration, Sharing, etc.
        
        $shareResult = ElevenLabs::workspace()->shareWorkspaceResource(
            'resource_123',
            [
                'permissions' => ['read', 'write'],
                'users' => ['user1@example.com', 'user2@example.com']
            ]
        );

        return response()->json(['share_result' => $shareResult]);
    }

    /**
     * Example showing backward compatibility - all old code still works!
     */
    public function backwardCompatibilityExample()
    {
        // 🔄 BACKWARD COMPATIBILITY - Old methods still work unchanged
        
        // This is the old way (still works):
        $result = ElevenLabs::textToSpeech('Hello from legacy method!');
        $voices = ElevenLabs::getVoices();
        $userInfo = ElevenLabs::getUserInfo();
        
        return response()->json([
            'message' => 'All legacy methods work perfectly!',
            'tts_result' => $result,
            'voices_count' => count($voices['voices'] ?? []),
            'user_info' => $userInfo
        ]);
    }

    /**
     * Comprehensive example showing mixed usage of all services
     */
    public function comprehensiveExample()
    {
        $results = [];

        // Use Audio service
        $ttsResult = ElevenLabs::audio()->textToSpeech('Hello from comprehensive example!');
        $results['audio'] = ['tts_success' => $ttsResult['success']];

        // Use Voice service
        $voices = ElevenLabs::voice()->getVoices();
        $results['voice'] = ['voices_count' => count($voices['voices'] ?? [])];

        // Use Analytics service
        $userInfo = ElevenLabs::analytics()->getUserInfo();
        $usage = ElevenLabs::analytics()->getCharacterUsage();
        $results['analytics'] = [
            'user_success' => $userInfo['success'],
            'usage_success' => $usage['success'],
            'characters_used' => $usage['usage']['character_count'] ?? 0
        ];

        // Use AI service
        $aiSettings = ElevenLabs::ai()->getConversationalAISettings();
        $results['ai'] = ['settings_success' => $aiSettings['success']];

        // Use Studio service
        $projects = ElevenLabs::studio()->getStudioProjects();
        $results['studio'] = ['projects_success' => $projects['success']];

        return response()->json([
            'message' => 'All services working perfectly in modular architecture!',
            'service_results' => $results
        ]);
    }
}
