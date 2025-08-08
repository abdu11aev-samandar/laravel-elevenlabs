<?php

namespace Samandar\LaravelElevenLabs\Services\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

abstract class BaseElevenLabsService
{
    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl = 'https://api.elevenlabs.io/v1';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = config('elevenlabs.base_uri', $this->baseUrl);
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            // Content-Type ni global emas, har bir so'rov turiga qarab Guzzle o'zi belgilaydi
            'headers' => [
                'xi-api-key' => $this->apiKey,
            ],
            'timeout' => (int) config('elevenlabs.timeout', 30),
        ]);
    }

    /**
     * Make a GET request
     */
    protected function get(string $endpoint): array
    {
        try {
            $response = $this->client->get($endpoint);
            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('ElevenLabs GET Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }

    /**
     * Make a POST request
     */
    protected function post(string $endpoint, array $data = []): array
    {
        try {
            $response = $this->client->post($endpoint, $data);
            $responseData = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'data' => $responseData,
                'headers' => $response->getHeaders(),
            ];
        } catch (GuzzleException $e) {
            Log::error('ElevenLabs POST Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }

    /**
     * Make a PATCH request
     */
    protected function patch(string $endpoint, array $data = []): array
    {
        try {
            $this->client->patch($endpoint, $data);

            return ['success' => true];
        } catch (GuzzleException $e) {
            Log::error('ElevenLabs PATCH Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }

    /**
     * Make a DELETE request
     */
    protected function delete(string $endpoint): array
    {
        try {
            $this->client->delete($endpoint);

            return ['success' => true];
        } catch (GuzzleException $e) {
            Log::error('ElevenLabs DELETE Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }

    /**
     * Make a POST request and return binary data
     */
    protected function postBinary(string $endpoint, array $data = []): array
    {
        try {
            $response = $this->client->post($endpoint, $data);

            return [
                'success' => true,
                'data' => $response->getBody()->getContents(),
                'content_type' => $response->getHeader('Content-Type')[0] ?? 'audio/mpeg',
            ];
        } catch (GuzzleException $e) {
            Log::error('ElevenLabs POST Binary Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
