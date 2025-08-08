<?php

namespace Samandar\LaravelElevenLabs\Services\Studio;

use Samandar\LaravelElevenLabs\Services\Core\BaseElevenLabsService;
use Illuminate\Http\UploadedFile;

class StudioService extends BaseElevenLabsService
{
    /**
     * Get studio projects
     */
    public function getStudioProjects(): array
    {
        $result = $this->get('/studio/projects');

        if ($result['success']) {
            return [
                'success' => true,
                'projects' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Create studio project
     */
    public function createStudioProject(UploadedFile|string $sourceFile, string $name = null): array
    {
        $multipart = [];
        
        if ($name) {
            $multipart[] = ['name' => 'name', 'contents' => $name];
        }

        if ($sourceFile instanceof UploadedFile) {
            $multipart[] = [
                'name' => 'file',
                'contents' => fopen($sourceFile->getPathname(), 'r'),
                'filename' => $sourceFile->getClientOriginalName(),
            ];
        } else {
            $multipart[] = [
                'name' => 'file',
                'contents' => fopen($sourceFile, 'r'),
                'filename' => basename($sourceFile),
            ];
        }

        $result = $this->post('/studio/projects', [
            'multipart' => $multipart,
            'headers' => ['xi-api-key' => $this->apiKey]
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'project' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get studio project
     */
    public function getStudioProject(string $projectId): array
    {
        $result = $this->get("/studio/projects/{$projectId}");

        if ($result['success']) {
            return [
                'success' => true,
                'project' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Delete studio project
     */
    public function deleteStudioProject(string $projectId): array
    {
        return $this->delete("/studio/projects/{$projectId}");
    }

    /**
     * Convert studio project
     */
    public function convertStudioProject(string $projectId): array
    {
        $result = $this->post("/studio/projects/{$projectId}/convert");

        if ($result['success']) {
            return [
                'success' => true,
                'conversion' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Create podcast project
     */
    public function createPodcastProject(array $podcastData): array
    {
        $result = $this->post('/studio/podcasts', [
            'json' => $podcastData
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'podcast' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Create a dubbing project
     */
    public function createDubbing(
        UploadedFile|string $sourceFile,
        string $targetLanguage,
        ?string $sourceLanguage = null,
        ?int $numSpeakers = null,
        bool $watermark = false
    ): array {
        $multipart = [
            ['name' => 'target_lang', 'contents' => $targetLanguage],
            ['name' => 'watermark', 'contents' => $watermark ? 'true' : 'false'],
        ];

        if ($sourceLanguage) {
            $multipart[] = ['name' => 'source_lang', 'contents' => $sourceLanguage];
        }

        if ($numSpeakers) {
            $multipart[] = ['name' => 'num_speakers', 'contents' => (string) $numSpeakers];
        }

        if ($sourceFile instanceof UploadedFile) {
            $multipart[] = [
                'name' => 'file',
                'contents' => fopen($sourceFile->getPathname(), 'r'),
                'filename' => $sourceFile->getClientOriginalName(),
            ];
        } else {
            $multipart[] = [
                'name' => 'file',
                'contents' => fopen($sourceFile, 'r'),
                'filename' => basename($sourceFile),
            ];
        }

        $result = $this->post('/dubbing', [
            'multipart' => $multipart,
            'headers' => ['xi-api-key' => $this->apiKey]
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'dubbing' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get dubbing project details
     */
    public function getDubbing(string $dubbingId): array
    {
        $result = $this->get("/dubbing/{$dubbingId}");

        if ($result['success']) {
            return [
                'success' => true,
                'dubbing' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get dubbed audio
     */
    public function getDubbedAudio(string $dubbingId, string $languageCode): array
    {
        $result = $this->postBinary("/dubbing/{$dubbingId}/audio/{$languageCode}");

        if ($result['success']) {
            return [
                'success' => true,
                'audio' => $result['data'],
                'content_type' => $result['content_type'],
            ];
        }

        return $result;
    }
}
