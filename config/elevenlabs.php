<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ElevenLabs API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure your ElevenLabs API settings. You can obtain
    | your API key from the ElevenLabs dashboard at https://elevenlabs.io
    |
    */

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Your ElevenLabs API key. You should set this in your .env file:
    | ELEVENLABS_API_KEY=your_api_key_here
    |
    */
    'api_key' => env('ELEVENLABS_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URI
    |--------------------------------------------------------------------------
    |
    | ElevenLabs API base URL. You can override it via env if needed.
    |
    */
    'base_uri' => env('ELEVENLABS_BASE_URI', 'https://api.elevenlabs.io/v1/'),

    /*
    |--------------------------------------------------------------------------
    | Default Voice Settings
    |--------------------------------------------------------------------------
    |
    | Default voice settings that will be used for text-to-speech conversion
    | when no custom settings are provided.
    |
    */
    'default_voice_settings' => [
        'stability' => 0.5,
        'similarity_boost' => 0.5,
        'style' => 0.5,
        'use_speaker_boost' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Voice ID
    |--------------------------------------------------------------------------
    |
    | The default voice ID to use when converting text to speech.
    | This is Rachel's voice ID from ElevenLabs.
    |
    */
    'default_voice_id' => env('ELEVENLABS_DEFAULT_VOICE_ID', '21m00Tcm4TlvDq8ikWAM'),

    /*
    |--------------------------------------------------------------------------
    | Default Model
    |--------------------------------------------------------------------------
    |
    | The default model to use for text-to-speech conversion.
    | Options: eleven_multilingual_v2, eleven_turbo_v2, etc.
    |
    */
    'default_model' => env('ELEVENLABS_DEFAULT_MODEL', 'eleven_multilingual_v2'),

    /*
    |--------------------------------------------------------------------------
    | Audio Storage Path
    |--------------------------------------------------------------------------
    |
    | The default path where generated audio files will be stored.
    | This path is relative to the storage/app directory.
    |
    */
    'audio_storage_path' => env('ELEVENLABS_AUDIO_PATH', 'elevenlabs/audio'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout for API requests in seconds.
    |
    */
    'timeout' => env('ELEVENLABS_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Whether to log API requests and responses for debugging purposes.
    |
    */
    'log_requests' => env('ELEVENLABS_LOG_REQUESTS', false),

    /*
    |--------------------------------------------------------------------------
    | Sound Generation Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for sound effects generation.
    |
    */
    'sound_generation' => [
        'default_duration' => env('ELEVENLABS_SOUND_DURATION', 3), // seconds
        'max_duration' => 22, // ElevenLabs limit
        'min_duration' => 0.5, // ElevenLabs limit
    ],

    /*
    |--------------------------------------------------------------------------
    | Audio Isolation Settings
    |--------------------------------------------------------------------------
    |
    | Settings for audio isolation (noise removal) feature.
    |
    */
    'audio_isolation' => [
        'enabled' => env('ELEVENLABS_AUDIO_ISOLATION_ENABLED', true),
        'supported_formats' => ['wav', 'mp3', 'flac', 'ogg'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversational AI Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for AI agents and conversations.
    |
    */
    'conversational_ai' => [
        'default_turn_timeout' => env('ELEVENLABS_AI_TURN_TIMEOUT', 7), // seconds
        'max_conversation_duration' => env('ELEVENLABS_AI_MAX_DURATION', 600), // seconds
        'enable_batch_calling' => env('ELEVENLABS_BATCH_CALLING_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Voice Preview Settings
    |--------------------------------------------------------------------------
    |
    | Settings for voice preview generation.
    |
    */
    'voice_preview' => [
        'max_text_length' => env('ELEVENLABS_PREVIEW_MAX_TEXT', 500),
        'default_preview_count' => env('ELEVENLABS_PREVIEW_COUNT', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for retry logic and exponential backoff.
    |
    */
    'retry' => [
        'enabled' => env('ELEVENLABS_RETRY_ENABLED', true),
        'max_attempts' => env('ELEVENLABS_RETRY_MAX_ATTEMPTS', 3),
        'base_delay_ms' => env('ELEVENLABS_RETRY_BASE_DELAY_MS', 1000),
        'max_delay_ms' => env('ELEVENLABS_RETRY_MAX_DELAY_MS', 60000),
        'respect_retry_after' => env('ELEVENLABS_RETRY_RESPECT_RETRY_AFTER', true),
        'use_jitter' => env('ELEVENLABS_RETRY_USE_JITTER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for ElevenLabs API logging.
    |
    */
    'logging' => [
        'enabled' => env('ELEVENLABS_LOGGING_ENABLED', true),
        'log_requests' => env('ELEVENLABS_LOG_REQUESTS', false),
        'log_responses' => env('ELEVENLABS_LOG_RESPONSES', true),
        'log_retries' => env('ELEVENLABS_LOG_RETRIES', true),
        'log_rate_limits' => env('ELEVENLABS_LOG_RATE_LIMITS', true),
        'channel' => env('ELEVENLABS_LOG_CHANNEL', null), // null = use default logger
    ],
];
