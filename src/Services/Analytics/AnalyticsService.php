<?php

namespace Samandar\LaravelElevenLabs\Services\Analytics;

use Samandar\LaravelElevenLabs\Services\Core\BaseElevenLabsService;

class AnalyticsService extends BaseElevenLabsService
{
    /**
     * Get user subscription info
     */
    public function getUserInfo(): array
    {
        $result = $this->get('/user');

        if ($result['success']) {
            return [
                'success' => true,
                'user' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get user subscription details
     */
    public function getUserSubscription(): array
    {
        $result = $this->get('/user/subscription');

        if ($result['success']) {
            return [
                'success' => true,
                'subscription' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get available models
     */
    public function getModels(): array
    {
        $result = $this->get('/models');

        if ($result['success']) {
            return [
                'success' => true,
                'models' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get character usage statistics
     */
    public function getCharacterUsage(): array
    {
        $result = $this->get('/usage/character-stats');

        if ($result['success']) {
            return [
                'success' => true,
                'usage' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get generation history
     */
    public function getHistory(int $pageSize = 100, ?string $startAfterHistoryItemId = null): array
    {
        $params = ['page_size' => $pageSize];
        if ($startAfterHistoryItemId) {
            $params['start_after_history_item_id'] = $startAfterHistoryItemId;
        }

        $result = $this->get('/history?' . http_build_query($params));

        if ($result['success']) {
            return [
                'success' => true,
                'history' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get specific history item
     */
    public function getHistoryItem(string $historyItemId): array
    {
        $result = $this->get("/history/{$historyItemId}");

        if ($result['success']) {
            return [
                'success' => true,
                'item' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Delete history item
     */
    public function deleteHistoryItem(string $historyItemId): array
    {
        return $this->delete("/history/{$historyItemId}");
    }

    /**
     * Download history items
     */
    public function downloadHistory(array $historyItemIds): array
    {
        $result = $this->postBinary('/history/download', [
            'json' => ['history_item_ids' => $historyItemIds]
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
     * Get usage summary
     */
    public function getUsageSummary(): array
    {
        $userInfo = $this->getUserInfo();
        $characterUsage = $this->getCharacterUsage();
        
        if ($userInfo['success'] && $characterUsage['success']) {
            return [
                'success' => true,
                'summary' => [
                    'user' => $userInfo['user'],
                    'usage' => $characterUsage['usage'],
                    'generated_at' => date('c'), // ISO 8601 format
                ]
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to fetch usage summary',
        ];
    }
}
