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
];
