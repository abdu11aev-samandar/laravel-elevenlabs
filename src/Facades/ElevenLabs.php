<?php

namespace Samandar\LaravelElevenLabs\Facades;

use Illuminate\Support\Facades\Facade;
use Samandar\LaravelElevenLabs\Services\ElevenLabsService;
use Illuminate\Http\UploadedFile;

/**
 * Text-to-Speech Methods
 * @method static array textToSpeech(string $text, string $voiceId = '21m00Tcm4TlvDq8ikWAM', array $voiceSettings = [])
 * @method static array textToSpeechAndSave(string $text, string $filePath, string $voiceId = '21m00Tcm4TlvDq8ikWAM', array $voiceSettings = [])
 * @method static \Generator streamTextToSpeech(string $text, string $voiceId = '21m00Tcm4TlvDq8ikWAM', string $modelId = 'eleven_multilingual_v2', array $voiceSettings = [])
 * 
 * Speech-to-Text Methods
 * @method static array speechToText(UploadedFile|string $audioFile, string $modelId = 'whisper-1')
 * 
 * Voice Management Methods
 * @method static array getVoices()
 * @method static array getVoice(string $voiceId)
 * @method static array addVoice(string $name, array $audioFiles, string $description = '', array $labels = [])
 * @method static array editVoiceSettings(string $voiceId, array $voiceSettings)
 * @method static array deleteVoice(string $voiceId)
 * @method static array getSimilarLibraryVoices(UploadedFile|string $audioFile)
 * 
 * History Methods
 * @method static array getHistory(int $pageSize = 100, string $startAfterHistoryItemId = null)
 * @method static array getHistoryItem(string $historyItemId)
 * @method static array deleteHistoryItem(string $historyItemId)
 * @method static array downloadHistory(array $historyItemIds)
 * 
 * Dubbing Methods
 * @method static array createDubbing(UploadedFile|string $sourceFile, string $targetLanguage, string $sourceLanguage = null, int $numSpeakers = null, bool $watermark = false)
 * @method static array getDubbing(string $dubbingId)
 * @method static array getDubbedAudio(string $dubbingId, string $languageCode)
 * 
 * Speech-to-Speech Methods
 * @method static array speechToSpeech(string $voiceId, UploadedFile|string $audioFile, string $modelId = 'eleven_multilingual_sts_v2', array $voiceSettings = [])
 * 
 * Pronunciation Dictionary Methods
 * @method static array getPronunciationDictionaries()
 * @method static array addPronunciationDictionary(string $name, array $rules, string $description = '')
 * 
 * Utility Methods
 * @method static array getUserInfo()
 * @method static array getModels()
 * @method static array getCharacterUsage()
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
