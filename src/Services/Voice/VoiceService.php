<?php

namespace Samandar\LaravelElevenLabs\Services\Voice;

use Samandar\LaravelElevenLabs\Services\Core\BaseElevenLabsService;
use Illuminate\Http\UploadedFile;

class VoiceService extends BaseElevenLabsService
{
    /**
     * Get available voices
     */
    public function getVoices(): array
    {
        $result = $this->get('/voices');

        if ($result['success']) {
            return [
                'success' => true,
                'voices' => $result['data']['voices'] ?? [],
            ];
        }

        return $result;
    }

    /**
     * Get voice details
     */
    public function getVoice(string $voiceId): array
    {
        $result = $this->get("/voices/{$voiceId}");

        if ($result['success']) {
            return [
                'success' => true,
                'voice' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Add a new voice (Voice cloning)
     */
    public function addVoice(string $name, array $audioFiles, string $description = '', array $labels = []): array
    {
        $multipart = [
            ['name' => 'name', 'contents' => $name],
            ['name' => 'description', 'contents' => $description],
        ];

        foreach ($labels as $key => $value) {
            $multipart[] = ['name' => 'labels', 'contents' => json_encode([$key => $value])];
        }

        foreach ($audioFiles as $index => $file) {
            if ($file instanceof UploadedFile) {
                $multipart[] = [
                    'name' => 'files',
                    'contents' => fopen($file->getPathname(), 'r'),
                    'filename' => $file->getClientOriginalName(),
                ];
            } else {
                $multipart[] = [
                    'name' => 'files',
                    'contents' => fopen($file, 'r'),
                    'filename' => basename($file),
                ];
            }
        }

        $result = $this->post('/voices/add', [
            'multipart' => $multipart,
            'headers' => ['xi-api-key' => $this->apiKey]
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'voice' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Edit voice settings
     */
    public function editVoiceSettings(string $voiceId, array $voiceSettings): array
    {
        return $this->post("/voices/{$voiceId}/settings/edit", [
            'json' => $voiceSettings
        ]);
    }

    /**
     * Delete a voice
     */
    public function deleteVoice(string $voiceId): array
    {
        return $this->delete("/voices/{$voiceId}");
    }

    /**
     * Get similar voices from library
     */
    public function getSimilarLibraryVoices(UploadedFile|string $audioFile): array
    {
        $multipart = [];
        
        if ($audioFile instanceof UploadedFile) {
            $multipart[] = [
                'name' => 'audio_file',
                'contents' => fopen($audioFile->getPathname(), 'r'),
                'filename' => $audioFile->getClientOriginalName(),
            ];
        } else {
            $multipart[] = [
                'name' => 'audio_file',
                'contents' => fopen($audioFile, 'r'),
                'filename' => basename($audioFile),
            ];
        }

        $result = $this->post('/similar-voices', [
            'multipart' => $multipart,
            'headers' => ['xi-api-key' => $this->apiKey]
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'voices' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get shared voices from library
     */
    public function getSharedVoices(): array
    {
        $result = $this->get('/shared-voices');

        if ($result['success']) {
            return [
                'success' => true,
                'voices' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get pronunciation dictionaries
     */
    public function getPronunciationDictionaries(): array
    {
        $result = $this->get('/pronunciation-dictionaries');

        if ($result['success']) {
            return [
                'success' => true,
                'dictionaries' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Add pronunciation dictionary
     */
    public function addPronunciationDictionary(string $name, array $rules, string $description = ''): array
    {
        $result = $this->post('/pronunciation-dictionaries/add', [
            'json' => [
                'name' => $name,
                'rules' => $rules,
                'description' => $description,
            ]
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'dictionary' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Create voice previews from text
     */
    public function createVoicePreviews(string $text, string $voiceId): array
    {
        $result = $this->post('/text-to-voice/create-previews', [
            'json' => [
                'text' => $text,
                'voice_id' => $voiceId,
            ]
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'previews' => $result['data'],
            ];
        }

        return $result;
    }
}
