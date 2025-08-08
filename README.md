# ElevenLabs Laravel Package

<p align="center">
    <a href="https://github.com/abdu11aev-samandar/laravel-elevenlabs/actions">
        <img src="https://img.shields.io/github/actions/workflow/status/abdu11aev-samandar/laravel-elevenlabs/tests.yml?branch=main&style=flat-square&logo=github" alt="Tests">
    </a>
    <a href="https://packagist.org/packages/samandar/laravel-elevenlabs">
        <img src="https://img.shields.io/packagist/v/samandar/laravel-elevenlabs.svg?style=flat-square&logo=packagist" alt="Latest Version">
    </a>
    <a href="https://packagist.org/packages/samandar/laravel-elevenlabs">
        <img src="https://img.shields.io/packagist/dt/samandar/laravel-elevenlabs.svg?style=flat-square&logo=packagist" alt="Total Downloads">
    </a>
    <a href="https://github.com/abdu11aev-samandar/laravel-elevenlabs/blob/main/LICENSE">
        <img src="https://img.shields.io/github/license/abdu11aev-samandar/laravel-elevenlabs?style=flat-square" alt="License">
    </a>
    <a href="https://php.net">
        <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=flat-square&logo=php" alt="PHP Version">
    </a>
    <a href="https://laravel.com">
        <img src="https://img.shields.io/badge/Laravel-9.x%20%7C%2010.x%20%7C%2011.x-FF2D20?style=flat-square&logo=laravel" alt="Laravel Version">
    </a>
</p>

<p align="center">
    <strong>A comprehensive Laravel package for integrating with ElevenLabs Text-to-Speech API</strong><br>
    Featuring modular architecture, backward compatibility, and professional-grade functionality.
</p>

---

## 📊 Package Statistics

- **🏗️ Architecture**: Modular service-based design
- **🧪 Tests**: 46 tests with 80 assertions (100% passing)
- **📦 Services**: 6 specialized service classes
- **🔧 Methods**: 50+ API methods available
- **🚀 Features**: TTS, STT, Voice Cloning, Dubbing, AI Chat, and more
- **📚 Documentation**: Comprehensive guides and examples
- **🔄 Compatibility**: Full backward compatibility maintained

## Features

### 🎯 Core Features
- **Text-to-Speech** conversion with multiple models
- **Speech-to-Text** transcription capabilities
- **Speech-to-Speech** voice conversion
- **Streaming TTS** for real-time audio generation
- 🌍 Support for multiple languages and voices
- ⚙️ Configurable voice settings with fine-tuning

### 🎭 Voice Management
- Voice cloning and custom voice creation
- Voice library access and similarity search
- Voice settings editing and deletion
- Shared voices from community library
- Voice similarity detection

### 🚀 Advanced Features
- **Dubbing** for video/audio translation
- **History Management** for generation tracking
- **Pronunciation Dictionaries** for custom pronunciations
- **Forced Alignment** for precise audio-text synchronization

### 🎬 Studio & Projects
- **Studio Projects** creation and management
- **Podcast Project** generation
- Project conversion and media processing
- File upload and project organization

### 🤖 Conversational AI
- **Knowledge Base** creation from URLs
- **Conversational AI Settings** management
- **Workspace Secrets** handling
- Resource sharing and collaboration tools

### 📊 Analytics & Management
- **Usage Statistics** and character tracking
- Generation history and analytics
- User subscription information
- Comprehensive error handling and logging

### 🔧 Laravel Integration
- Simple and intuitive API
- Laravel service container integration
- Facade support for easy usage
- Automatic file saving capabilities
- Comprehensive test coverage

## Installation

You can install the package via composer:

```bash
composer require samandar/laravel-elevenlabs
```

## Configuration

1. **Publish the configuration file:**

```bash
php artisan vendor:publish --tag=elevenlabs-config
```

2. **Add your ElevenLabs API key to your `.env` file:**

```env
ELEVENLABS_API_KEY=your_api_key_here
ELEVENLABS_DEFAULT_VOICE_ID=21m00Tcm4TlvDq8ikWAM
ELEVENLABS_DEFAULT_MODEL=eleven_multilingual_v2
```

3. **Optionally configure other settings:**

```env
ELEVENLABS_AUDIO_PATH=elevenlabs/audio
ELEVENLABS_TIMEOUT=30
ELEVENLABS_LOG_REQUESTS=false
```

## Usage

The package provides two approaches for accessing ElevenLabs functionality:

1. **New Modular Approach** (Recommended) - Organized by feature groups
2. **Legacy Approach** - Backward compatible with previous versions

### 🚀 New Modular Approach

#### Audio Service (TTS, STT, STS, etc.)

```php
use Samandar\LaravelElevenLabs\Facades\ElevenLabs;

// Text-to-Speech
$result = ElevenLabs::audio()->textToSpeech('Hello, world!');

// Speech-to-Text
$uploadedFile = request()->file('audio');
$result = ElevenLabs::audio()->speechToText($uploadedFile);

// Speech-to-Speech conversion
$result = ElevenLabs::audio()->speechToSpeech(
    'voice_id_here',
    $uploadedFile
);

// Streaming TTS
$stream = ElevenLabs::audio()->streamTextToSpeech('Hello streaming!');
foreach ($stream as $chunk) {
    // Process audio chunk
    echo $chunk;
}

// Save audio to file
$saved = ElevenLabs::audio()->saveAudioToFile(
    $audioContent,
    storage_path('app/speech.mp3')
);

// Text-to-Speech with file saving
$result = ElevenLabs::audio()->textToSpeechAndSave(
    'Hello, this will be saved!',
    storage_path('app/test-speech.mp3')
);

// 🆕 NEW: Audio Isolation (experimental)
// Note: This method is experimental and may change as ElevenLabs finalizes the endpoint.
$isolatedAudio = ElevenLabs::audio()->audioIsolation($uploadedFile);

// 🆕 NEW: Sound Generation - Create sound effects from text
$soundEffect = ElevenLabs::audio()->soundGeneration(
    'Thunder and rain sounds',  // Text description
    10,                         // Duration in seconds (optional)
    'strong'                    // Prompt influence or guidance (optional)
);
```

#### Voice Service (Voice Management)

```php
// Get available voices
$voices = ElevenLabs::voice()->getVoices();

// Get specific voice details
$voice = ElevenLabs::voice()->getVoice('voice_id_here');

// Add custom voice
$result = ElevenLabs::voice()->addVoice(
    'My Custom Voice',
    $audioFiles, // Array of UploadedFile objects
    'Description of the voice'
);

// Edit voice settings
$result = ElevenLabs::voice()->editVoiceSettings('voice_id', [
    'stability' => 0.7,
    'similarity_boost' => 0.8
]);

// Delete voice
$result = ElevenLabs::voice()->deleteVoice('voice_id');

// Get shared voices from community
$sharedVoices = ElevenLabs::voice()->getSharedVoices();

// Pronunciation dictionaries
$dictionaries = ElevenLabs::voice()->getPronunciationDictionaries();
$result = ElevenLabs::voice()->addPronunciationDictionary(
    'My Dictionary',
    [['string' => 'word', 'phoneme' => 'pronunciation']]
);

// 🆕 NEW: Create voice previews
$result = ElevenLabs::voice()->createVoicePreviews(
    'Hello, this is a voice preview test',
    'voice_id_here'
);
);
```

#### AI Service (Conversational AI & Knowledge Base)

```php
// Conversational AI settings
$settings = ElevenLabs::ai()->getConversationalAISettings();
$result = ElevenLabs::ai()->updateConversationalAISettings($newSettings);

// Knowledge base management
$result = ElevenLabs::ai()->createKnowledgeBaseFromURL('https://docs.example.com');
$knowledgeBases = ElevenLabs::ai()->getKnowledgeBases();
$result = ElevenLabs::ai()->deleteKnowledgeBase('kb_id');

// Knowledge base documents (file upload)
$multipart = [
    [
        'name' => 'file',
        'contents' => fopen(storage_path('app/docs.pdf'), 'r'),
        'filename' => 'docs.pdf'
    ]
];
$doc = ElevenLabs::ai()->createKnowledgeBaseDocumentFromFile($multipart);
$docContent = ElevenLabs::ai()->getKnowledgeBaseDocumentContent('document_id');

// RAG Index overview
$rag = ElevenLabs::ai()->getRagIndexOverview();

// Workspace secrets
$secrets = ElevenLabs::ai()->getWorkspaceSecrets();

// 🆕 NEW: Signed URL and Widget
$signed = ElevenLabs::ai()->getSignedUrl('agent_id_here');
$widget = ElevenLabs::ai()->getAgentWidgetConfig('agent_id_here');

// 🆕 NEW: Tools and MCP Servers
$tools = ElevenLabs::ai()->listTools();
$tool = ElevenLabs::ai()->getTool('tool_id');
$createdTool = ElevenLabs::ai()->createTool(['name' => 'Search Tool']);
$dependentAgents = ElevenLabs::ai()->getDependentAgents('tool_id');

$mcpServers = ElevenLabs::ai()->listMcpServers();
$createdMcp = ElevenLabs::ai()->createMcpServer(['name' => 'Internal MCP']);
$approval = ElevenLabs::ai()->createMcpApprovalPolicy(['policy' => 'allow_all']);

// Dashboard settings
$dashboard = ElevenLabs::ai()->getDashboardSettings();

// 🆕 NEW: AI Agents Management
// List all conversational AI agents with pagination
$agents = ElevenLabs::ai()->getAgents('cursor_here', 10);

// Create a new AI agent
$agentData = [
    'name' => 'Customer Support Agent',
    'prompt' => 'You are a helpful customer support assistant.',
    'voice_id' => '21m00Tcm4TlvDq8ikWAM',
    'language' => 'en'
];
$newAgent = ElevenLabs::ai()->createAgent($agentData);

// 🆕 NEW: Conversations Management
// List conversations with pagination and filters
$conversations = ElevenLabs::ai()->getConversations(
    'cursor_here',
    10 // page size
);

// Get specific conversation details
$conversation = ElevenLabs::ai()->getConversation('conversation_id');

// Get conversation audio file
$audio = ElevenLabs::ai()->getConversationAudio('conversation_id');

// 🆕 NEW: Batch Calling
// Submit a batch calling job
$callsData = [
    [
        'phone_number' => '+1234567890',
        'message' => 'Hello, this is a test call.'
    ],
    [
        'phone_number' => '+0987654321', 
        'message' => 'Another test message.'
    ]
];
$batchJob = ElevenLabs::ai()->submitBatchCalling($callsData);

// Get batch calling status
$status = ElevenLabs::ai()->getBatchCalling('batch_id');
```

#### Studio Service (Projects & Dubbing)

```php
// Studio projects
$projects = ElevenLabs::studio()->getStudioProjects();
$project = ElevenLabs::studio()->getStudioProject('project_id');

// Chapters and snapshots
$chapter = ElevenLabs::studio()->getChapter('project_id', 'chapter_id');
$chapterSnaps = ElevenLabs::studio()->listChapterSnapshots('project_id', 'chapter_id');
$chapterSnapshot = ElevenLabs::studio()->getChapterSnapshot('project_id', 'chapter_id', 'snapshot_id');
$projectSnapshot = ElevenLabs::studio()->getProjectSnapshot('project_id', 'project_snapshot_id');

// Create project from file
$result = ElevenLabs::studio()->createStudioProject(
    $uploadedFile,
    'My Project Name'
);

// Convert project
$result = ElevenLabs::studio()->convertStudioProject('project_id');

// Delete project
$result = ElevenLabs::studio()->deleteStudioProject('project_id');

// Dubbing operations
$result = ElevenLabs::studio()->createDubbing(
    $sourceFile,
    'spanish', // target language
    'english', // source language (optional)
    2 // number of speakers (optional)
);

$dubbing = ElevenLabs::studio()->getDubbing('dubbing_id');
$audio = ElevenLabs::studio()->getDubbedAudio('dubbing_id', 'es');

// Podcast projects
$result = ElevenLabs::studio()->createPodcastProject($podcastData);

// Dubbing transcript (SRT/WEBVTT)
$transcript = ElevenLabs::studio()->getDubbingTranscript('dubbing_id', 'srt');
```

#### Analytics Service (Usage & History)

```php
// User information and usage
$userInfo = ElevenLabs::analytics()->getUserInfo();
$usage = ElevenLabs::analytics()->getCharacterUsage();
$models = ElevenLabs::analytics()->getModels();

// Generation history
$history = ElevenLabs::analytics()->getHistory();
$historyItem = ElevenLabs::analytics()->getHistoryItem('history_id');
$result = ElevenLabs::analytics()->deleteHistoryItem('history_id');

// Download multiple history items
$result = ElevenLabs::analytics()->downloadHistory(['id1', 'id2']);

// 🆕 NEW: Get user subscription info
$subscription = ElevenLabs::analytics()->getUserSubscription();
```

#### Workspace Service (Collaboration)

```php
// Share workspace resources
$result = ElevenLabs::workspace()->shareWorkspaceResource(
    'resource_id',
    $shareData
);

// Get resources and a specific resource
$resources = ElevenLabs::workspace()->getWorkspaceResources();
$resource = ElevenLabs::workspace()->getWorkspaceResource('resource_id');

// Search groups
$groups = ElevenLabs::workspace()->searchWorkspaceGroups(['q' => 'team']);

// Members
$members = ElevenLabs::workspace()->getWorkspaceMembers();
$invitation = ElevenLabs::workspace()->inviteWorkspaceMember('user@example.com', ['read']);
$removed = ElevenLabs::workspace()->removeWorkspaceMember('member_id');

// Workspace-level secrets (optional)
$workspaceSecrets = ElevenLabs::workspace()->getWorkspaceSecrets();
```

### 🔄 Legacy Approach (Backward Compatible)

All existing code continues to work unchanged:

```php
use Samandar\LaravelElevenLabs\Facades\ElevenLabs;

// Basic text-to-speech conversion
$result = ElevenLabs::textToSpeech('Hello, world!');

if ($result['success']) {
    // Save audio to file
    $saved = ElevenLabs::saveAudioToFile(
        $result['audio'], 
        storage_path('app/speech.mp3')
    );
}

// Convert text to speech and save in one step
$result = ElevenLabs::textToSpeechAndSave(
    'Hello, this is a test message!',
    storage_path('app/test-speech.mp3')
);

// Get available voices
$voices = ElevenLabs::getVoices();
if ($voices['success']) {
    foreach ($voices['voices'] as $voice) {
        echo $voice['name'] . ' - ' . $voice['voice_id'] . "\n";
    }
}

// Use custom voice settings
$result = ElevenLabs::textToSpeech(
    'Custom voice settings example',
    '21m00Tcm4TlvDq8ikWAM', // Voice ID
    [
        'stability' => 0.7,
        'similarity_boost' => 0.8,
        'style' => 0.6,
        'use_speaker_boost' => true
    ]
);
```

### Using Dependency Injection

```php
use Samandar\LaravelElevenLabs\Services\ElevenLabsService;

class SpeechController extends Controller
{
    public function generateSpeech(ElevenLabsService $elevenLabs)
    {
        $result = $elevenLabs->textToSpeech('Hello from controller!');
        
        if ($result['success']) {
            return response($result['audio'], 200)
                ->header('Content-Type', $result['content_type']);
        }
        
        return response()->json(['error' => $result['error']], 500);
    }
}
```

### Controller Example

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Samandar\LaravelElevenLabs\Facades\ElevenLabs;

class TextToSpeechController extends Controller
{
    public function convert(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:2500',
            'voice_id' => 'nullable|string',
        ]);

        $result = ElevenLabs::textToSpeech(
            $request->text,
            $request->voice_id ?? config('elevenlabs.default_voice_id')
        );

        if ($result['success']) {
            return response($result['audio'], 200)
                ->header('Content-Type', $result['content_type'])
                ->header('Content-Disposition', 'attachment; filename="speech.mp3"');
        }

        return response()->json([
            'error' => 'Failed to generate speech',
            'message' => $result['error']
        ], 500);
    }

    public function getVoices()
    {
        $result = ElevenLabs::getVoices();
        
        if ($result['success']) {
            return response()->json($result['voices']);
        }

        return response()->json([
            'error' => 'Failed to fetch voices',
            'message' => $result['error']
        ], 500);
    }
}
```

## Available Methods

### Text-to-Speech Methods
- `textToSpeech(string $text, string $voiceId, array $voiceSettings): array`
- `textToSpeechAndSave(string $text, string $filePath, string $voiceId, array $voiceSettings): array`
- `streamTextToSpeech(string $text, string $voiceId, string $modelId, array $voiceSettings): Generator`

### Speech-to-Text Methods
- `speechToText(UploadedFile|string $audioFile, string $modelId): array`

### Voice Management Methods
- `getVoices(): array`
- `getVoice(string $voiceId): array`
- `addVoice(string $name, array $audioFiles, string $description, array $labels): array`
- `editVoiceSettings(string $voiceId, array $voiceSettings): array`
- `deleteVoice(string $voiceId): array`
- `getSimilarLibraryVoices(UploadedFile|string $audioFile): array`

### History Methods
- `getHistory(int $pageSize, string $startAfterHistoryItemId): array`
- `getHistoryItem(string $historyItemId): array`
- `deleteHistoryItem(string $historyItemId): array`
- `downloadHistory(array $historyItemIds): array`

### Dubbing Methods
- `createDubbing(UploadedFile|string $sourceFile, string $targetLanguage, string $sourceLanguage, int $numSpeakers, bool $watermark): array`
- `getDubbing(string $dubbingId): array`
- `getDubbedAudio(string $dubbingId, string $languageCode): array`

### Speech-to-Speech Methods
- `speechToSpeech(string $voiceId, UploadedFile|string $audioFile, string $modelId, array $voiceSettings): array`

### Pronunciation Dictionary Methods
- `getPronunciationDictionaries(): array`
- `addPronunciationDictionary(string $name, array $rules, string $description): array`

### Conversational AI Methods
- `getConversationalAISettings(): array`
- `updateConversationalAISettings(array $settings): array`
- `getWorkspaceSecrets(): array`

### Knowledge Base Methods
- `createKnowledgeBaseFromURL(string $url): array`
- `getKnowledgeBases(?string $cursor, ?int $pageSize): array`
- `deleteKnowledgeBase(string $documentationId): array`

### Studio Projects Methods
- `getStudioProjects(): array`
- `createStudioProject(UploadedFile|string $sourceFile, ?string $name): array`
- `getStudioProject(string $projectId): array`
- `deleteStudioProject(string $projectId): array`
- `convertStudioProject(string $projectId): array`
- `createPodcastProject(array $podcastData): array`

### Forced Alignment Methods
- `createForcedAlignment(UploadedFile|string $audioFile, string $text, string $language): array`

### Voice Library Methods
- `getSharedVoices(): array`

### Workspace Methods
- `shareWorkspaceResource(string $resourceId, array $shareData): array`

### Utility Methods
- `getUserInfo(): array`
- `getModels(): array`
- `getCharacterUsage(): array`
- `saveAudioToFile(string $audioContent, string $filePath): bool`

### Response Format

All methods return an array with the following structure:

**Success Response:**
```php
[
    'success' => true,
    'audio' => '...', // Binary audio data (for TTS methods)
    'content_type' => 'audio/mpeg',
    // ... other relevant data
]
```

**Error Response:**
```php
[
    'success' => false,
    'error' => 'Error message',
    'code' => 400
]
```

## Voice Settings

You can customize voice settings for more control over the generated speech:

```php
$voiceSettings = [
    'stability' => 0.5,        // 0.0 to 1.0
    'similarity_boost' => 0.5, // 0.0 to 1.0
    'style' => 0.5,           // 0.0 to 1.0
    'use_speaker_boost' => true
];

$result = ElevenLabs::textToSpeech('Hello!', 'voice_id', $voiceSettings);
```

## Popular Voice IDs

Here are some popular voice IDs you can use:

- `21m00Tcm4TlvDq8ikWAM` - Rachel (Female, American)
- `AZnzlk1XvdvUeBnXmlld` - Domi (Female, American)
- `EXAVITQu4vr4xnSDxMaL` - Bella (Female, American)
- `ErXwobaYiN019PkySvjV` - Antoni (Male, American)
- `MF3mGyEYCl7XYWbV9V6O` - Elli (Female, American)
- `TxGEqnHWrfWFTfGW9XjX` - Josh (Male, American)
- `VR6AewLTigWG4xSOukaG` - Arnold (Male, American)
- `pNInz6obpgDQGcFmaJgB` - Adam (Male, American)
- `yoZ06aMxZJJ28mfd3POQ` - Sam (Male, American)

Use `ElevenLabs::getVoices()` to get the complete list of available voices.

## Configuration Options

The configuration file `config/elevenlabs.php` contains the following options:

```php
return [
    'api_key' => env('ELEVENLABS_API_KEY'),
    'default_voice_id' => env('ELEVENLABS_DEFAULT_VOICE_ID', '21m00Tcm4TlvDq8ikWAM'),
    'default_model' => env('ELEVENLABS_DEFAULT_MODEL', 'eleven_multilingual_v2'),
    'audio_storage_path' => env('ELEVENLABS_AUDIO_PATH', 'elevenlabs/audio'),
    'timeout' => env('ELEVENLABS_TIMEOUT', 30),
    'log_requests' => env('ELEVENLABS_LOG_REQUESTS', false),
    'default_voice_settings' => [
        'stability' => 0.5,
        'similarity_boost' => 0.5,
        'style' => 0.5,
        'use_speaker_boost' => true,
    ],
];
```

## Testing

To run the tests:

```bash
composer test
```

Or run PHPUnit directly:

```bash
vendor/bin/phpunit
```

## Error Handling

The package includes comprehensive error handling. All methods return success/error status:

```php
$result = ElevenLabs::textToSpeech('Hello!');

if (!$result['success']) {
    Log::error('ElevenLabs error: ' . $result['error']);
    // Handle error appropriately
}
```

## Requirements

- PHP 8.1 or higher
- Laravel 9.x, 10.x, or 11.x
- GuzzleHTTP 7.x
- Valid ElevenLabs API key

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Support & Contributing

If you find this package helpful and would like to support its development, consider buying me a coffee! ☕

<p align="center">
    <a href="https://www.buymeacoffee.com/xkas20012" target="_blank">
        <img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me A Coffee" style="height: 60px !important;width: 217px !important;" >
    </a>
</p>

### 🤝 How to Contribute

We welcome contributions! Here's how you can help:

- **🐛 Report bugs** - Open an issue on GitHub
- **💡 Suggest features** - We'd love to hear your ideas
- **📝 Improve documentation** - Help make the docs even better
- **🔧 Submit pull requests** - Code contributions are welcome
- **⭐ Star the repository** - It helps others discover this package

### 🛡️ Security

If you discover any security related issues, please email **xkas2001@gmail.com** instead of using the issue tracker.

### 📋 Support Channels

- **GitHub Issues**: [Report bugs and feature requests](https://github.com/abdu11aev-samandar/laravel-elevenlabs/issues)
- **Email**: xkas2001@gmail.com for security issues
- **Documentation**: Comprehensive guides available in this README

## Credits

- **[Abdullaev Samandar](https://github.com/abdu11aev-samandar)** - Package author and maintainer
- **[ElevenLabs](https://elevenlabs.io)** - For providing the amazing TTS API
- **Laravel Community** - For the inspiration and support
- **All Contributors** - Thank you for your valuable contributions!

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

---

<p align="center">
    <strong>🎉 Thank you for using ElevenLabs Laravel Package! 🎉</strong><br><br>
    If this package has been helpful, please consider:
</p>

<p align="center">
    ⭐ <strong>Starring the repository</strong><br>
    🐦 <strong>Sharing with your network</strong><br>
    ☕ <strong>Supporting the development</strong><br>
    🤝 <strong>Contributing to the project</strong>
</p>

<p align="center">
<em>Made with ❤️ by <a href="https://github.com/abdu11aev-samandar">Abdullaev Samandar</a></em>
</p>
