# New ElevenLabs API Endpoints Usage Examples

This document provides usage examples for the newly implemented ElevenLabs API endpoints.

## 🎤 Audio Processing

### Audio Isolation
Remove background noise from audio files:

```php
use Samandar\LaravelElevenLabs\Services\Audio\AudioService;

$audioService = new AudioService();

// Isolate audio from uploaded file
$result = $audioService->audioIsolation($uploadedFile);

if ($result['success']) {
    // Save the cleaned audio
    file_put_contents('clean_audio.mp3', $result['audio']);
    echo "Audio isolation successful!";
}
```

### Sound Generation
Generate sound effects from text descriptions:

```php
$audioService = new AudioService();

// Generate sound effect
$result = $audioService->soundGeneration(
    text: "Thunder and rain storm",
    durationSeconds: 5,
    promptInfluence: "dramatic"
);

if ($result['success']) {
    file_put_contents('thunder_storm.mp3', $result['audio']);
    echo "Sound effect generated!";
}
```

## 🤖 Enhanced Conversational AI

### List AI Agents with Pagination
```php
use Samandar\LaravelElevenLabs\Services\AI\AIService;

$aiService = new AIService();

// Get agents with pagination
$result = $aiService->getAgents(
    cursor: null,
    pageSize: 20
);

if ($result['success']) {
    foreach ($result['agents']['agents'] as $agent) {
        echo "Agent: {$agent['name']} (ID: {$agent['agent_id']})\n";
    }
    
    // Get next page if available
    $nextCursor = $result['agents']['next_cursor'] ?? null;
    if ($nextCursor) {
        $nextPage = $aiService->getAgents($nextCursor, 20);
    }
}
```

### Create AI Agent
```php
$aiService = new AIService();

$agentData = [
    'name' => 'Customer Service Bot',
    'prompt' => 'You are a helpful customer service assistant...',
    'voice' => [
        'voice_id' => '21m00Tcm4TlvDq8ikWAM'
    ],
    'language' => 'en',
    'conversation_config' => [
        'agent_starts_conversation' => true,
        'inactivity_message' => "Are you still there?",
        'max_duration_seconds' => 600
    ]
];

$result = $aiService->createAgent($agentData);

if ($result['success']) {
    $agentId = $result['agent']['agent_id'];
    echo "Agent created with ID: {$agentId}";
}
```

### List All Conversations with Filtering
```php
$aiService = new AIService();

// Get conversations from the last 24 hours
$oneDayAgo = time() - 86400;

$result = $aiService->getConversations(
    cursor: null,
    pageSize: 50,
    callStartAfterUnix: $oneDayAgo,
    callStartBeforeUnix: time()
);

if ($result['success']) {
    echo "Found {$result['conversations']['total']} conversations\n";
    foreach ($result['conversations']['conversations'] as $conversation) {
        echo "Conversation {$conversation['conversation_id']} - {$conversation['status']}\n";
    }
}
```

### Get Specific Conversation Details
```php
$aiService = new AIService();

$result = $aiService->getConversation('conversation_id_here');

if ($result['success']) {
    $conversation = $result['conversation'];
    echo "Status: {$conversation['status']}\n";
    echo "Duration: {$conversation['duration_seconds']}s\n";
    echo "Agent: {$conversation['agent_id']}\n";
}
```

### Download Conversation Audio
```php
$aiService = new AIService();

$result = $aiService->getConversationAudio('conversation_id_here');

if ($result['success']) {
    file_put_contents('conversation.mp3', $result['audio']);
    echo "Conversation audio downloaded!";
}
```

### Batch Calling
```php
$aiService = new AIService();

// Submit batch calling job
$batchData = [
    'agent_id' => 'agent_id_here',
    'csv_data' => base64_encode($csvContent), // CSV with phone numbers and data
    'name' => 'Marketing Campaign Q4'
];

$result = $aiService->submitBatchCalling($batchData);

if ($result['success']) {
    $batchId = $result['batch']['batch_id'];
    echo "Batch job submitted with ID: {$batchId}";
    
    // Check batch status
    $status = $aiService->getBatchCalling($batchId);
    echo "Status: {$status['batch']['status']}";
}
```

## 👤 User & Subscription Management

### Get Detailed Subscription Info
```php
use Samandar\LaravelElevenLabs\Services\Analytics\AnalyticsService;

$analyticsService = new AnalyticsService();

$result = $analyticsService->getUserSubscription();

if ($result['success']) {
    $subscription = $result['subscription'];
    echo "Plan: {$subscription['tier']}\n";
    echo "Characters remaining: {$subscription['character_limit'] - $subscription['character_count']}\n";
    echo "Reset date: {$subscription['next_character_count_reset_unix']}\n";
}
```

## 🎵 Voice Previews

### Create Voice Previews
```php
use Samandar\LaravelElevenLabs\Services\Voice\VoiceService;

$voiceService = new VoiceService();

$result = $voiceService->createVoicePreviews(
    text: "Hello, this is a voice preview test",
    voiceId: "21m00Tcm4TlvDq8ikWAM"
);

if ($result['success']) {
    foreach ($result['previews'] as $index => $preview) {
        file_put_contents("preview_{$index}.mp3", base64_decode($preview['audio']));
    }
    echo "Voice previews created!";
}
```

## 🔄 Combined Workflow Examples

### Complete Conversation Workflow
```php
$aiService = new AIService();

// 1. Create an agent
$agent = $aiService->createAgent([
    'name' => 'Sales Assistant',
    'prompt' => 'You are a helpful sales assistant...',
    'voice' => ['voice_id' => '21m00Tcm4TlvDq8ikWAM']
]);

if ($agent['success']) {
    $agentId = $agent['agent']['agent_id'];
    
    // 2. Get conversations for this agent
    $conversations = $aiService->getAgentConversations($agentId);
    
    // 3. Process each conversation
    foreach ($conversations['conversations'] as $conversation) {
        $convId = $conversation['conversation_id'];
        
        // Get detailed conversation info
        $details = $aiService->getConversation($convId);
        
        // Download audio if conversation is complete
        if ($details['success'] && $details['conversation']['status'] === 'completed') {
            $audio = $aiService->getConversationAudio($convId);
            if ($audio['success']) {
                file_put_contents("conversation_{$convId}.mp3", $audio['audio']);
            }
        }
    }
}
```

### Audio Enhancement Pipeline
```php
$audioService = new AudioService();

// 1. Start with noisy audio
$noisyAudio = 'path/to/noisy_audio.wav';

// 2. Clean the audio using isolation
$cleanResult = $audioService->audioIsolation($noisyAudio);

if ($cleanResult['success']) {
    // 3. Save the cleaned audio
    $cleanPath = 'cleaned_audio.wav';
    file_put_contents($cleanPath, $cleanResult['audio']);
    
    // 4. Convert clean audio to text
    $transcription = $audioService->speechToText($cleanPath);
    
    if ($transcription['success']) {
        echo "Transcription: " . $transcription['transcription']['text'];
        
        // 5. Generate new speech with a different voice
        $newSpeech = $audioService->textToSpeech(
            $transcription['transcription']['text'],
            'different_voice_id'
        );
        
        if ($newSpeech['success']) {
            file_put_contents('enhanced_audio.mp3', $newSpeech['audio']);
        }
    }
}
```

## ⚙️ Configuration Tips

### Error Handling
```php
try {
    $result = $aiService->createAgent($agentData);
    
    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Unknown error');
    }
    
    // Process successful result
    $agent = $result['agent'];
    
} catch (Exception $e) {
    Log::error('ElevenLabs API Error: ' . $e->getMessage());
    // Handle error appropriately
}
```

### Rate Limiting
```php
// For batch operations, add delays to respect rate limits
foreach ($conversations as $conversation) {
    $audio = $aiService->getConversationAudio($conversation['id']);
    
    // Add delay between requests
    usleep(100000); // 100ms delay
}
```

These examples demonstrate the powerful new capabilities added to our ElevenLabs Laravel package, providing comprehensive coverage of the latest API features.
