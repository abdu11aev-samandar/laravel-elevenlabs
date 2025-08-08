<?php

namespace Samandar\LaravelElevenLabs\Services\Audio;

use Samandar\LaravelElevenLabs\Services\Core\BaseElevenLabsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class AudioService extends BaseElevenLabsService
{
    /**
     * Convert text to speech
     */
    public function textToSpeech(
        string $text, 
        string $voiceId = '21m00Tcm4TlvDq8ikWAM', 
        array $voiceSettings = []
    ): array {
        $defaultVoiceSettings = config('elevenlabs.default_voice_settings', [
            'stability' => 0.5,
            'similarity_boost' => 0.5,
            'style' => 0.5,
            'use_speaker_boost' => true,
        ]);

        $voiceSettings = array_merge($defaultVoiceSettings, $voiceSettings);

        $modelId = config('elevenlabs.default_model', 'eleven_multilingual_v2');

        $result = $this->postBinary("/text-to-speech/{$voiceId}", [
            'json' => [
                'text' => $text,
                'model_id' => $modelId,
                'voice_settings' => $voiceSettings,
            ],
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'audio' => $result['data'],
                'content_type' => $result['content_type'],
            ];
        }

        return $result;
    }

    /**
     * Convert speech to text
     */
    public function speechToText(UploadedFile|string $audioFile, string $modelId = 'whisper-1'): array
    {
        $multipart = [];
        
        if ($audioFile instanceof UploadedFile) {
            $multipart[] = [
                'name' => 'audio',
                'contents' => fopen($audioFile->getPathname(), 'r'),
                'filename' => $audioFile->getClientOriginalName(),
            ];
        } else {
            $multipart[] = [
                'name' => 'audio',
                'contents' => fopen($audioFile, 'r'),
                'filename' => basename($audioFile),
            ];
        }
        
        $multipart[] = [
            'name' => 'model_id',
            'contents' => $modelId
        ];

        $result = $this->post('/speech-to-text', [
            'multipart' => $multipart,
            'headers' => [
                'xi-api-key' => $this->apiKey,
            ]
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'transcription' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Convert speech to speech
     */
    public function speechToSpeech(
        string $voiceId,
        UploadedFile|string $audioFile,
        string $modelId = 'eleven_multilingual_sts_v2',
        array $voiceSettings = []
    ): array {
        $multipart = [
            ['name' => 'model_id', 'contents' => $modelId],
        ];

        if (!empty($voiceSettings)) {
            $multipart[] = ['name' => 'voice_settings', 'contents' => json_encode($voiceSettings)];
        }

        if ($audioFile instanceof UploadedFile) {
            $multipart[] = [
                'name' => 'audio',
                'contents' => fopen($audioFile->getPathname(), 'r'),
                'filename' => $audioFile->getClientOriginalName(),
            ];
        } else {
            $multipart[] = [
                'name' => 'audio',
                'contents' => fopen($audioFile, 'r'),
                'filename' => basename($audioFile),
            ];
        }

        $result = $this->postBinary("/speech-to-speech/{$voiceId}", [
            'multipart' => $multipart,
            'headers' => ['xi-api-key' => $this->apiKey]
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'audio' => $result['data'],
                'content_type' => $result['content_type'],
            ];
        }

        return $result;
    }

    /**
     * Stream text-to-speech
     */
    public function streamTextToSpeech(
        string $text,
        string $voiceId = '21m00Tcm4TlvDq8ikWAM',
        string $modelId = null,
        array $voiceSettings = []
    ): \Generator {
        $defaultVoiceSettings = config('elevenlabs.default_voice_settings', [
            'stability' => 0.5,
            'similarity_boost' => 0.5,
            'style' => 0.5,
            'use_speaker_boost' => true,
        ]);

        $voiceSettings = array_merge($defaultVoiceSettings, $voiceSettings);

        $effectiveModelId = $modelId ?? config('elevenlabs.default_model', 'eleven_multilingual_v2');

        try {
            $response = $this->client->post("/text-to-speech/{$voiceId}/stream", [
                'json' => [
                    'text' => $text,
                    'model_id' => $effectiveModelId,
                    'voice_settings' => $voiceSettings,
                ],
                'stream' => true
            ]);

            $body = $response->getBody();
            while (!$body->eof()) {
                yield $body->read(1024);
            }
        } catch (\Exception $e) {
            Log::error('ElevenLabs Stream TTS Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Save audio to file
     */
    public function saveAudioToFile(string $audioContent, string $filePath): bool
    {
        try {
            $directory = dirname($filePath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($filePath, $audioContent);
            return true;
        } catch (\Exception $e) {
            Log::error('ElevenLabs Save Audio Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Convert text to speech and save to file
     */
    public function textToSpeechAndSave(
        string $text,
        string $filePath,
        string $voiceId = '21m00Tcm4TlvDq8ikWAM',
        array $voiceSettings = []
    ): array {
        $result = $this->textToSpeech($text, $voiceId, $voiceSettings);
        
        if ($result['success']) {
            $saved = $this->saveAudioToFile($result['audio'], $filePath);
            $result['saved'] = $saved;
            $result['file_path'] = $saved ? $filePath : null;
        }

        return $result;
    }

    /**
     * Create forced alignment
     */
    public function createForcedAlignment(UploadedFile|string $audioFile, string $text, string $language = 'en'): array
    {
        $multipart = [
            ['name' => 'text', 'contents' => $text],
            ['name' => 'language', 'contents' => $language],
        ];

        if ($audioFile instanceof UploadedFile) {
            $multipart[] = [
                'name' => 'audio',
                'contents' => fopen($audioFile->getPathname(), 'r'),
                'filename' => $audioFile->getClientOriginalName(),
            ];
        } else {
            $multipart[] = [
                'name' => 'audio',
                'contents' => fopen($audioFile, 'r'),
                'filename' => basename($audioFile),
            ];
        }

        $result = $this->post('/forced-alignment', [
            'multipart' => $multipart,
            'headers' => ['xi-api-key' => $this->apiKey]
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'alignment' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Audio isolation - isolates audio to remove background noise
     */
    public function audioIsolation(UploadedFile|string $audioFile): array
    {
        $multipart = [];
        
        if ($audioFile instanceof UploadedFile) {
            $multipart[] = [
                'name' => 'audio',
                'contents' => fopen($audioFile->getPathname(), 'r'),
                'filename' => $audioFile->getClientOriginalName(),
            ];
        } else {
            $multipart[] = [
                'name' => 'audio',
                'contents' => fopen($audioFile, 'r'),
                'filename' => basename($audioFile),
            ];
        }

        $result = $this->postBinary('/audio-native', ['multipart' => $multipart]);

        if ($result['success']) {
            return [
                'success' => true,
                'audio' => $result['data'],
                'content_type' => $result['content_type'] ?? 'audio/mpeg',
            ];
        }

        return $result;
    }

    /**
     * Generate sound effects
     */
    public function soundGeneration(
        string $text,
        ?int $durationSeconds = null,
        ?string $promptInfluence = null
    ): array {
        $data = ['text' => $text];
        
        if ($durationSeconds !== null) {
            $data['duration_seconds'] = $durationSeconds;
        }
        
        if ($promptInfluence !== null) {
            $data['prompt_influence'] = $promptInfluence;
        }

        $result = $this->postBinary('/sound-generation', ['json' => $data]);

        if ($result['success']) {
            return [
                'success' => true,
                'audio' => $result['data'],
                'content_type' => $result['content_type'] ?? 'audio/mpeg',
            ];
        }

        return $result;
    }
}
