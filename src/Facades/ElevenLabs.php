<?php

namespace Samandar\LaravelElevenLabs\Facades;

use Illuminate\Support\Facades\Facade;
use Samandar\LaravelElevenLabs\Services\ElevenLabsService;
use Illuminate\Http\UploadedFile;

/**
 * Text-to-Speech Methods
 * @method static array textToSpeech(string $text, string $voiceId = '21m00Tcm4TlvDq8ikWAM', array $voiceSettings = [])
 * @method static array textToSpeechAndSave(string $text, string $filePath, string $voiceId = '21m00Tcm4TlvDq8ikWAM', array $voiceSettings = [])
 * @method static \Generator streamTextToSpeech(string $text, string $voiceId = '21m00Tcm4TlvDq8ikWAM', ?string $modelId = null, array $voiceSettings = [])
 *
 * Speech-to-Text Methods
 * @method static array speechToText(UploadedFile|string $audioFile, string $modelId = 'whisper-1')
 * @method static array createForcedAlignment(UploadedFile|string $audioFile, string $text, string $language = 'en')
 *
 * Audio Processing Methods
 * @method static array audioIsolation(UploadedFile|string $audioFile)
 * @method static array soundGeneration(string $text, ?int $durationSeconds = null, ?string $promptInfluence = null)
 *
 * Voice Management Methods
 * @method static array getVoices()
 * @method static array getVoice(string $voiceId)
 * @method static array addVoice(string $name, array $audioFiles, string $description = '', array $labels = [])
 * @method static array editVoiceSettings(string $voiceId, array $voiceSettings)
 * @method static array deleteVoice(string $voiceId)
 * @method static array getSimilarLibraryVoices(UploadedFile|string $audioFile)
 * @method static array getSharedVoices()
 * @method static array getPronunciationDictionaries()
 * @method static array addPronunciationDictionary(string $name, array $rules, string $description = '')
 * @method static array createVoicePreviews(string $text, string $voiceId)
 *
 * History / Analytics Methods
 * @method static array getUserInfo()
 * @method static array getUserSubscription()
 * @method static array getModels()
 * @method static array getCharacterUsage()
 * @method static array getHistory(int $pageSize = 100, ?string $startAfterHistoryItemId = null)
 * @method static array getHistoryItem(string $historyItemId)
 * @method static array deleteHistoryItem(string $historyItemId)
 * @method static array downloadHistory(array $historyItemIds)
 * @method static array getUsageSummary()
 *
 * Dubbing / Studio Methods
 * @method static array createDubbing(UploadedFile|string $sourceFile, string $targetLanguage, ?string $sourceLanguage = null, ?int $numSpeakers = null, bool $watermark = false)
 * @method static array getDubbing(string $dubbingId)
 * @method static array getDubbedAudio(string $dubbingId, string $languageCode)
 * @method static array getStudioProjects()
 * @method static array createStudioProject(UploadedFile|string $sourceFile, ?string $name = null)
 * @method static array getStudioProject(string $projectId)
 * @method static array deleteStudioProject(string $projectId)
 * @method static array convertStudioProject(string $projectId)
 * @method static array createPodcastProject(array $podcastData)
 *
 * Conversational AI Methods
 * @method static array getConversationalAISettings()
 * @method static array updateConversationalAISettings(array $settings)
 * @method static array getWorkspaceSecrets()
 * @method static array createKnowledgeBaseFromURL(string $url)
 * @method static array getKnowledgeBases(?string $cursor = null, ?int $pageSize = null)
 * @method static array deleteKnowledgeBase(string $documentationId)
 * @method static array getAgents(?string $cursor = null, ?int $pageSize = null)
 * @method static array createAgent(array $agentData)
 * @method static array getAgent(string $agentId)
 * @method static array updateAgent(string $agentId, array $agentData)
 * @method static array deleteAgent(string $agentId)
 * @method static array getConversations(?string $cursor = null, ?int $pageSize = null, ?int $callStartAfterUnix = null, ?int $callStartBeforeUnix = null)
 * @method static array getAgentConversations(string $agentId)
 * @method static array createConversation(string $agentId)
 * @method static array getConversation(string $conversationId)
 * @method static array getConversationAudio(string $conversationId)
 * @method static array submitBatchCalling(array $batchData)
 * @method static array getBatchCalling(string $batchId)
 *
 * Utility Methods
 * @method static bool saveAudioToFile(string $audioContent, string $filePath)
 *
 * @see ElevenLabsService
 */
class ElevenLabs extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return ElevenLabsService::class;
    }
}
