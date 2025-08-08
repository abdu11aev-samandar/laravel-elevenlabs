# Laravel ElevenLabs - Yangi Endpointlar uchun Amaliy Misollar

Bu fayl yangi qo'shilgan ElevenLabs API endpointlarini Laravel loyihasida to'g'ri ishlatish uchun praktik misollarni o'z ichiga oladi.

## 📋 **Asosiy konfiguratsiya**

### .env faylida API key sozlash
```bash
ELEVENLABS_API_KEY=your_api_key_here
```

### Service Provider orqali foydalanish
```php
// config/app.php da provider ro'yxatga olingan bo'lishi kerak
'providers' => [
    // ...
    Samandar\LaravelElevenLabs\ElevenLabsServiceProvider::class,
],
```

## 🎤 **Audio Processing - Amaliy Misollar**

### 1. Laravel Controller da Audio Isolation
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Samandar\LaravelElevenLabs\Services\Audio\AudioService;
use Illuminate\Support\Facades\Storage;

class AudioProcessingController extends Controller
{
    public function cleanAudio(Request $request): JsonResponse
    {
        $request->validate([
            'audio_file' => 'required|file|mimes:wav,mp3,ogg|max:10240', // 10MB max
        ]);

        try {
            // API key .env dan olinadi
            $audioService = new AudioService(config('elevenlabs.api_key'));
            
            $audioFile = $request->file('audio_file');
            $result = $audioService->audioIsolation($audioFile);
            
            if ($result['success']) {
                // Tozalangan audio ni storage ga saqlash
                $filename = 'cleaned_' . time() . '.mp3';
                $path = Storage::disk('public')->put(
                    'audio/cleaned/' . $filename, 
                    $result['audio']
                );
                
                return response()->json([
                    'success' => true,
                    'message' => 'Audio successfully cleaned',
                    'file_url' => Storage::url('audio/cleaned/' . $filename),
                    'content_type' => $result['content_type']
                ]);
            }
            
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Audio processing failed'
            ], 400);
            
        } catch (\Exception $e) {
            \Log::error('Audio isolation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Server error occurred'
            ], 500);
        }
    }
}
```

### 2. Sound Effects yaratish uchun Artisan Command
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Samandar\LaravelElevenLabs\Services\Audio\AudioService;
use Illuminate\Support\Facades\Storage;

class GenerateSoundEffect extends Command
{
    protected $signature = 'elevenlabs:generate-sound 
                           {description : Text description of the sound}
                           {--duration=5 : Duration in seconds}
                           {--influence=neutral : Prompt influence}
                           {--output= : Output filename}';
    
    protected $description = 'Generate sound effects using ElevenLabs API';

    public function handle(): int
    {
        $description = $this->argument('description');
        $duration = (int) $this->option('duration');
        $influence = $this->option('influence');
        $outputFile = $this->option('output') ?: 'sound_effect_' . time() . '.mp3';

        $this->info("Generating sound: {$description}");
        
        try {
            $audioService = new AudioService(config('elevenlabs.api_key'));
            
            $result = $audioService->soundGeneration(
                text: $description,
                durationSeconds: $duration,
                promptInfluence: $influence
            );
            
            if ($result['success']) {
                Storage::disk('public')->put(
                    'sounds/' . $outputFile, 
                    $result['audio']
                );
                
                $this->info("✅ Sound effect generated: storage/app/public/sounds/{$outputFile}");
                return 0;
            }
            
            $this->error("❌ Failed to generate sound: " . ($result['error'] ?? 'Unknown error'));
            return 1;
            
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
            return 1;
        }
    }
}
```

## 🤖 **Conversational AI - Laravel da foydalanish**

### 1. AI Agent CRUD Controller
```php
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Samandar\LaravelElevenLabs\Services\AI\AIService;

class AIAgentController extends Controller
{
    private AIService $aiService;
    
    public function __construct()
    {
        $this->aiService = new AIService(config('elevenlabs.api_key'));
    }
    
    /**
     * Agent yaratish
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'prompt' => 'required|string',
            'voice_id' => 'required|string',
            'language' => 'required|string|in:en,es,fr,de,it,pt,pl,hi,ja,zh,ko',
        ]);
        
        try {
            $agentData = [
                'name' => $request->name,
                'prompt' => $request->prompt,
                'voice' => ['voice_id' => $request->voice_id],
                'language' => $request->language,
                'conversation_config' => [
                    'agent_starts_conversation' => $request->boolean('agent_starts_conversation', true),
                    'inactivity_message' => $request->input('inactivity_message', 'Are you still there?'),
                    'max_duration_seconds' => $request->integer('max_duration', 600)
                ]
            ];
            
            $result = $this->aiService->createAgent($agentData);
            
            if ($result['success']) {
                // Database ga saqlash (opsional)
                // Agent::create([
                //     'elevenlabs_agent_id' => $result['agent']['agent_id'],
                //     'name' => $request->name,
                //     'user_id' => auth()->id()
                // ]);
                
                return response()->json([
                    'success' => true,
                    'agent' => $result['agent']
                ], 201);
            }
            
            return response()->json([
                'success' => false,
                'error' => $result['error']
            ], 400);
            
        } catch (\Exception $e) {
            \Log::error('AI Agent creation error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }
    
    /**
     * Agentlar ro'yxati (pagination bilan)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $cursor = $request->input('cursor');
            $pageSize = $request->integer('page_size', 20);
            
            $result = $this->aiService->getAgents($cursor, $pageSize);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['agents'],
                    'pagination' => [
                        'next_cursor' => $result['agents']['next_cursor'] ?? null,
                        'has_more' => isset($result['agents']['next_cursor'])
                    ]
                ]);
            }
            
            return response()->json(['error' => $result['error']], 400);
            
        } catch (\Exception $e) {
            \Log::error('Get agents error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }
    
    /**
     * Agent ma'lumotlarini olish
     */
    public function show(string $agentId): JsonResponse
    {
        try {
            $result = $this->aiService->getAgent($agentId);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'agent' => $result['agent']
                ]);
            }
            
            return response()->json(['error' => $result['error']], 404);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error'], 500);
        }
    }
}
```

### 2. Conversation Management Service
```php
<?php

namespace App\Services;

use Samandar\LaravelElevenLabs\Services\AI\AIService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ConversationManager
{
    private AIService $aiService;
    
    public function __construct()
    {
        $this->aiService = new AIService(config('elevenlabs.api_key'));
    }
    
    /**
     * So'ngi conversationlarni olish
     */
    public function getRecentConversations(int $hours = 24): array
    {
        $startTime = time() - ($hours * 3600);
        
        try {
            $result = $this->aiService->getConversations(
                cursor: null,
                pageSize: 100,
                callStartAfterUnix: $startTime,
                callStartBeforeUnix: time()
            );
            
            if ($result['success']) {
                return $result['conversations'];
            }
            
            Log::warning('Failed to fetch conversations: ' . ($result['error'] ?? 'Unknown error'));
            return ['conversations' => [], 'total' => 0];
            
        } catch (\Exception $e) {
            Log::error('Conversation fetch error: ' . $e->getMessage());
            return ['conversations' => [], 'total' => 0];
        }
    }
    
    /**
     * Conversation audio ni yuklab olish va saqlash
     */
    public function downloadConversationAudio(string $conversationId): ?string
    {
        try {
            $result = $this->aiService->getConversationAudio($conversationId);
            
            if ($result['success']) {
                $filename = "conversation_{$conversationId}_" . date('Y-m-d_H-i-s') . '.mp3';
                $path = "conversations/{$filename}";
                
                Storage::disk('private')->put($path, $result['audio']);
                
                Log::info("Conversation audio saved: {$path}");
                return $path;
            }
            
            Log::error("Failed to download conversation audio: " . ($result['error'] ?? 'Unknown error'));
            return null;
            
        } catch (\Exception $e) {
            Log::error('Audio download error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Batch calling job yaratish
     */
    public function createBatchCalling(string $agentId, string $csvData, string $campaignName): ?string
    {
        try {
            $batchData = [
                'agent_id' => $agentId,
                'csv_data' => base64_encode($csvData),
                'name' => $campaignName
            ];
            
            $result = $this->aiService->submitBatchCalling($batchData);
            
            if ($result['success']) {
                $batchId = $result['batch']['batch_id'];
                Log::info("Batch calling job created: {$batchId}");
                return $batchId;
            }
            
            Log::error('Batch calling submission failed: ' . ($result['error'] ?? 'Unknown error'));
            return null;
            
        } catch (\Exception $e) {
            Log::error('Batch calling error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Batch calling holati
     */
    public function getBatchStatus(string $batchId): ?array
    {
        try {
            $result = $this->aiService->getBatchCalling($batchId);
            
            if ($result['success']) {
                return $result['batch'];
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Batch status check error: ' . $e->getMessage());
            return null;
        }
    }
}
```

## 👤 **User Subscription Management**

### Subscription ma'lumotlarini ko'rsatish
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Samandar\LaravelElevenLabs\Services\Analytics\AnalyticsService;

class SubscriptionController extends Controller
{
    public function show(): JsonResponse
    {
        try {
            $analyticsService = new AnalyticsService(config('elevenlabs.api_key'));
            $result = $analyticsService->getUserSubscription();
            
            if ($result['success']) {
                $subscription = $result['subscription'];
                
                // Calculated fields qo'shish
                $remainingCharacters = $subscription['character_limit'] - $subscription['character_count'];
                $usagePercentage = round(($subscription['character_count'] / $subscription['character_limit']) * 100, 2);
                $resetDate = date('Y-m-d H:i:s', $subscription['next_character_count_reset_unix']);
                
                return response()->json([
                    'success' => true,
                    'subscription' => array_merge($subscription, [
                        'remaining_characters' => $remainingCharacters,
                        'usage_percentage' => $usagePercentage,
                        'reset_date_formatted' => $resetDate,
                    ])
                ]);
            }
            
            return response()->json(['error' => $result['error']], 400);
            
        } catch (\Exception $e) {
            \Log::error('Subscription check error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }
}
```

## 🎵 **Voice Previews**

### Voice preview yaratish
```php
<?php

namespace App\Services;

use Samandar\LaravelElevenLabs\Services\Voice\VoiceService;
use Illuminate\Support\Facades\Storage;

class VoicePreviewService
{
    private VoiceService $voiceService;
    
    public function __construct()
    {
        $this->voiceService = new VoiceService(config('elevenlabs.api_key'));
    }
    
    /**
     * Voice preview yaratish va saqlash
     */
    public function createAndSavePreviews(string $text, string $voiceId): array
    {
        try {
            $result = $this->voiceService->createVoicePreviews($text, $voiceId);
            
            if ($result['success']) {
                $savedPreviews = [];
                
                foreach ($result['previews']['previews'] as $index => $preview) {
                    $filename = "preview_{$voiceId}_{$index}_" . time() . '.mp3';
                    $path = "voice_previews/{$filename}";
                    
                    // Base64 decode qilib saqlash
                    Storage::disk('public')->put($path, base64_decode($preview['audio']));
                    
                    $savedPreviews[] = [
                        'index' => $index,
                        'filename' => $filename,
                        'url' => Storage::url($path),
                        'voice_id' => $preview['voice_id'],
                        'settings' => $preview['settings'] ?? null
                    ];
                }
                
                return [
                    'success' => true,
                    'previews' => $savedPreviews,
                    'total' => count($savedPreviews)
                ];
            }
            
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Preview creation failed'
            ];
            
        } catch (\Exception $e) {
            \Log::error('Voice preview creation error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Server error occurred'
            ];
        }
    }
}
```

## ⚙️ **Middleware va Validation**

### Rate limiting middleware
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ElevenLabsRateLimit
{
    public function __construct(private RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next, string $maxAttempts = '10', string $decayMinutes = '1'): Response
    {
        $key = 'elevenlabs-api:' . $request->ip();
        
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $seconds = $this->limiter->availableIn($key);
            
            return response()->json([
                'error' => 'Too many API requests. Please try again in ' . $seconds . ' seconds.'
            ], 429);
        }
        
        $this->limiter->hit($key, $decayMinutes * 60);
        
        return $next($request);
    }
}
```

### Custom Request validation
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // yoki auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'prompt' => 'required|string|min:10|max:2000',
            'voice_id' => 'required|string',
            'language' => 'required|string|in:en,es,fr,de,it,pt,pl,hi,ja,zh,ko',
            'agent_starts_conversation' => 'boolean',
            'inactivity_message' => 'nullable|string|max:500',
            'max_duration' => 'integer|min:30|max:3600'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Agent nomi majburiy.',
            'prompt.required' => 'Agent prompt matn majburiy.',
            'prompt.min' => 'Prompt kamida 10 ta belgi bo\'lishi kerak.',
            'voice_id.required' => 'Voice ID majburiy.',
            'language.in' => 'Noto\'g\'ri til kodi.'
        ];
    }
}
```

## 🔧 **Configuration fayli**

### config/elevenlabs.php
```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ElevenLabs API Key
    |--------------------------------------------------------------------------
    */
    'api_key' => env('ELEVENLABS_API_KEY'),
    
    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'voice_id' => env('ELEVENLABS_DEFAULT_VOICE_ID', '21m00Tcm4TlvDq8ikWAM'),
        'model_id' => env('ELEVENLABS_DEFAULT_MODEL_ID', 'eleven_multilingual_v2'),
        'language' => env('ELEVENLABS_DEFAULT_LANGUAGE', 'en'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'max_attempts' => env('ELEVENLABS_RATE_LIMIT_ATTEMPTS', 10),
        'decay_minutes' => env('ELEVENLABS_RATE_LIMIT_DECAY', 1),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | File Storage
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'audio_disk' => env('ELEVENLABS_AUDIO_DISK', 'public'),
        'conversation_disk' => env('ELEVENLABS_CONVERSATION_DISK', 'private'),
    ],
];
```

## 🧪 **Test misollari**

### Feature test
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AudioProcessingTest extends TestCase
{
    public function test_can_clean_audio_file(): void
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->create('test_audio.wav', 1000, 'audio/wav');
        
        $response = $this->postJson('/api/audio/clean', [
            'audio_file' => $file
        ]);
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'file_url',
                    'content_type'
                ]);
    }
}
```

Bu misollar real Laravel loyihasida to'g'ri ishlaydi va production muhitida foydalanish uchun tayyor.
