<?php

namespace Samandar\LaravelElevenLabs\Services;

use Samandar\LaravelElevenLabs\Services\Audio\AudioService;
use Samandar\LaravelElevenLabs\Services\Voice\VoiceService;
use Samandar\LaravelElevenLabs\Services\AI\AIService;
use Samandar\LaravelElevenLabs\Services\Studio\StudioService;
use Samandar\LaravelElevenLabs\Services\Analytics\AnalyticsService;
use Samandar\LaravelElevenLabs\Services\Core\WorkspaceService;
use Illuminate\Http\UploadedFile;

class ElevenLabsService
{
    protected string $apiKey;
    protected AudioService $audio;
    protected VoiceService $voice;
    protected AIService $ai;
    protected StudioService $studio;
    protected AnalyticsService $analytics;
    protected WorkspaceService $workspace;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->audio = new AudioService($apiKey);
        $this->voice = new VoiceService($apiKey);
        $this->ai = new AIService($apiKey);
        $this->studio = new StudioService($apiKey);
        $this->analytics = new AnalyticsService($apiKey);
        $this->workspace = new WorkspaceService($apiKey);
    }

    // =====================================
    // SERVICE GETTERS (New Modular Approach)
    // =====================================

    /**
     * Get Audio service for TTS, STT, STS operations
     */
    public function audio(): AudioService
    {
        return $this->audio;
    }

    /**
     * Get Voice service for voice management
     */
    public function voice(): VoiceService
    {
        return $this->voice;
    }

    /**
     * Get AI service for conversational AI and knowledge base
     */
    public function ai(): AIService
    {
        return $this->ai;
    }

    /**
     * Get Studio service for projects and dubbing
     */
    public function studio(): StudioService
    {
        return $this->studio;
    }

    /**
     * Get Analytics service for usage and history
     */
    public function analytics(): AnalyticsService
    {
        return $this->analytics;
    }

    /**
     * Get Workspace service for collaboration
     */
    public function workspace(): WorkspaceService
    {
        return $this->workspace;
    }

    // =====================================
    // BACKWARD COMPATIBILITY METHODS
    // =====================================
    // These delegate to the appropriate services for backward compatibility

    /**
     * @deprecated Use audio()->textToSpeech() instead
     */
    public function textToSpeech(
        string $text, 
        string $voiceId = '21m00Tcm4TlvDq8ikWAM', 
        array $voiceSettings = []
    ): array {
        return $this->audio->textToSpeech($text, $voiceId, $voiceSettings);
    }

    /**
     * @deprecated Use audio()->textToSpeechAndSave() instead
     */
    public function textToSpeechAndSave(
        string $text,
        string $filePath,
        string $voiceId = '21m00Tcm4TlvDq8ikWAM',
        array $voiceSettings = []
    ): array {
        return $this->audio->textToSpeechAndSave($text, $filePath, $voiceId, $voiceSettings);
    }

    /**
     * @deprecated Use audio()->speechToText() instead
     */
    public function speechToText(UploadedFile|string $audioFile, string $modelId = 'whisper-1'): array
    {
        return $this->audio->speechToText($audioFile, $modelId);
    }

    /**
     * @deprecated Use audio()->speechToSpeech() instead
     */
    public function speechToSpeech(
        string $voiceId,
        UploadedFile|string $audioFile,
        string $modelId = 'eleven_multilingual_sts_v2',
        array $voiceSettings = []
    ): array {
        return $this->audio->speechToSpeech($voiceId, $audioFile, $modelId, $voiceSettings);
    }

    /**
     * @deprecated Use audio()->streamTextToSpeech() instead
     */
    public function streamTextToSpeech(
        string $text,
        string $voiceId = '21m00Tcm4TlvDq8ikWAM',
        string $modelId = 'eleven_multilingual_v2',
        array $voiceSettings = []
    ): \Generator {
        return $this->audio->streamTextToSpeech($text, $voiceId, $modelId, $voiceSettings);
    }

    /**
     * @deprecated Use audio()->saveAudioToFile() instead
     */
    public function saveAudioToFile(string $audioContent, string $filePath): bool
    {
        return $this->audio->saveAudioToFile($audioContent, $filePath);
    }

    /**
     * @deprecated Use audio()->createForcedAlignment() instead
     */
    public function createForcedAlignment(UploadedFile|string $audioFile, string $text, string $language = 'en'): array
    {
        return $this->audio->createForcedAlignment($audioFile, $text, $language);
    }

    // Voice Service Compatibility Methods
    /**
     * @deprecated Use voice()->getVoices() instead
     */
    public function getVoices(): array
    {
        return $this->voice->getVoices();
    }

    /**
     * @deprecated Use voice()->getVoice() instead
     */
    public function getVoice(string $voiceId): array
    {
        return $this->voice->getVoice($voiceId);
    }

    /**
     * @deprecated Use voice()->addVoice() instead
     */
    public function addVoice(string $name, array $audioFiles, string $description = '', array $labels = []): array
    {
        return $this->voice->addVoice($name, $audioFiles, $description, $labels);
    }

    /**
     * @deprecated Use voice()->editVoiceSettings() instead
     */
    public function editVoiceSettings(string $voiceId, array $voiceSettings): array
    {
        return $this->voice->editVoiceSettings($voiceId, $voiceSettings);
    }

    /**
     * @deprecated Use voice()->deleteVoice() instead
     */
    public function deleteVoice(string $voiceId): array
    {
        return $this->voice->deleteVoice($voiceId);
    }

    /**
     * @deprecated Use voice()->getSimilarLibraryVoices() instead
     */
    public function getSimilarLibraryVoices(UploadedFile|string $audioFile): array
    {
        return $this->voice->getSimilarLibraryVoices($audioFile);
    }

    /**
     * @deprecated Use voice()->getSharedVoices() instead
     */
    public function getSharedVoices(): array
    {
        return $this->voice->getSharedVoices();
    }

    /**
     * @deprecated Use voice()->getPronunciationDictionaries() instead
     */
    public function getPronunciationDictionaries(): array
    {
        return $this->voice->getPronunciationDictionaries();
    }

    /**
     * @deprecated Use voice()->addPronunciationDictionary() instead
     */
    public function addPronunciationDictionary(string $name, array $rules, string $description = ''): array
    {
        return $this->voice->addPronunciationDictionary($name, $rules, $description);
    }

    // AI Service Compatibility Methods
    /**
     * @deprecated Use ai()->getConversationalAISettings() instead
     */
    public function getConversationalAISettings(): array
    {
        return $this->ai->getConversationalAISettings();
    }

    /**
     * @deprecated Use ai()->updateConversationalAISettings() instead
     */
    public function updateConversationalAISettings(array $settings): array
    {
        return $this->ai->updateConversationalAISettings($settings);
    }

    /**
     * @deprecated Use ai()->getWorkspaceSecrets() instead
     */
    public function getWorkspaceSecrets(): array
    {
        return $this->ai->getWorkspaceSecrets();
    }

    /**
     * @deprecated Use ai()->createKnowledgeBaseFromURL() instead
     */
    public function createKnowledgeBaseFromURL(string $url): array
    {
        return $this->ai->createKnowledgeBaseFromURL($url);
    }

    /**
     * @deprecated Use ai()->getKnowledgeBases() instead
     */
    public function getKnowledgeBases(?string $cursor = null, ?int $pageSize = null): array
    {
        return $this->ai->getKnowledgeBases($cursor, $pageSize);
    }

    /**
     * @deprecated Use ai()->deleteKnowledgeBase() instead
     */
    public function deleteKnowledgeBase(string $documentationId): array
    {
        return $this->ai->deleteKnowledgeBase($documentationId);
    }

    // Studio Service Compatibility Methods
    /**
     * @deprecated Use studio()->getStudioProjects() instead
     */
    public function getStudioProjects(): array
    {
        return $this->studio->getStudioProjects();
    }

    /**
     * @deprecated Use studio()->createStudioProject() instead
     */
    public function createStudioProject(UploadedFile|string $sourceFile, string $name = null): array
    {
        return $this->studio->createStudioProject($sourceFile, $name);
    }

    /**
     * @deprecated Use studio()->getStudioProject() instead
     */
    public function getStudioProject(string $projectId): array
    {
        return $this->studio->getStudioProject($projectId);
    }

    /**
     * @deprecated Use studio()->deleteStudioProject() instead
     */
    public function deleteStudioProject(string $projectId): array
    {
        return $this->studio->deleteStudioProject($projectId);
    }

    /**
     * @deprecated Use studio()->convertStudioProject() instead
     */
    public function convertStudioProject(string $projectId): array
    {
        return $this->studio->convertStudioProject($projectId);
    }

    /**
     * @deprecated Use studio()->createPodcastProject() instead
     */
    public function createPodcastProject(array $podcastData): array
    {
        return $this->studio->createPodcastProject($podcastData);
    }

    /**
     * @deprecated Use studio()->createDubbing() instead
     */
    public function createDubbing(
        UploadedFile|string $sourceFile,
        string $targetLanguage,
        ?string $sourceLanguage = null,
        ?int $numSpeakers = null,
        bool $watermark = false
    ): array {
        return $this->studio->createDubbing($sourceFile, $targetLanguage, $sourceLanguage, $numSpeakers, $watermark);
    }

    /**
     * @deprecated Use studio()->getDubbing() instead
     */
    public function getDubbing(string $dubbingId): array
    {
        return $this->studio->getDubbing($dubbingId);
    }

    /**
     * @deprecated Use studio()->getDubbedAudio() instead
     */
    public function getDubbedAudio(string $dubbingId, string $languageCode): array
    {
        return $this->studio->getDubbedAudio($dubbingId, $languageCode);
    }

    // Analytics Service Compatibility Methods
    /**
     * @deprecated Use analytics()->getUserInfo() instead
     */
    public function getUserInfo(): array
    {
        return $this->analytics->getUserInfo();
    }

    /**
     * @deprecated Use analytics()->getModels() instead
     */
    public function getModels(): array
    {
        return $this->analytics->getModels();
    }

    /**
     * @deprecated Use analytics()->getCharacterUsage() instead
     */
    public function getCharacterUsage(): array
    {
        return $this->analytics->getCharacterUsage();
    }

    /**
     * @deprecated Use analytics()->getHistory() instead
     */
    public function getHistory(int $pageSize = 100, ?string $startAfterHistoryItemId = null): array
    {
        return $this->analytics->getHistory($pageSize, $startAfterHistoryItemId);
    }

    /**
     * @deprecated Use analytics()->getHistoryItem() instead
     */
    public function getHistoryItem(string $historyItemId): array
    {
        return $this->analytics->getHistoryItem($historyItemId);
    }

    /**
     * @deprecated Use analytics()->deleteHistoryItem() instead
     */
    public function deleteHistoryItem(string $historyItemId): array
    {
        return $this->analytics->deleteHistoryItem($historyItemId);
    }

    /**
     * @deprecated Use analytics()->downloadHistory() instead
     */
    public function downloadHistory(array $historyItemIds): array
    {
        return $this->analytics->downloadHistory($historyItemIds);
    }

    // Workspace Service Compatibility Methods
    /**
     * @deprecated Use workspace()->shareWorkspaceResource() instead
     */
    public function shareWorkspaceResource(string $resourceId, array $shareData): array
    {
        return $this->workspace->shareWorkspaceResource($resourceId, $shareData);
    }
}
