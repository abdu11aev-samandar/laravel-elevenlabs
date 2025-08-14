<?php

namespace Samandar\LaravelElevenLabs\Tests\Unit\ServiceTestsComprehensive;

use Orchestra\Testbench\TestCase;
use Samandar\LaravelElevenLabs\Services\Analytics\AnalyticsService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Mockery;

/**
 * Comprehensive test coverage for AnalyticsService
 * 
 * @group analytics
 * @group comprehensive-coverage
 * @group unit
 */
class AnalyticsServiceComprehensiveTest extends TestCase
{
    protected AnalyticsService $service;
    protected $mockClient;
    protected string $apiKey = 'test-api-key';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockClient = Mockery::mock(Client::class);
        
        // Add default getConfig expectation for logging
        $this->mockClient->shouldReceive('getConfig')
            ->with('headers')
            ->andReturn(['xi-api-key' => $this->apiKey])
            ->byDefault();
            
        $this->service = new AnalyticsService($this->apiKey);
        
        // Inject mock client using reflection
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, $this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =====================================
    // User Information Tests
    // =====================================

    public function test_getUserInfo_success()
    {
        $mockResponseData = [
            'user_id' => 'user_123',
            'xi_api_key' => $this->apiKey,
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'is_new_user' => false,
            'profile_picture' => null
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('user')
            ->andReturn($mockResponse);

        $result = $this->service->getUserInfo();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['user']);
        $this->assertArrayHasKey('user_id', $result['user']);
        $this->assertArrayHasKey('xi_api_key', $result['user']);
    }

    public function test_getUserInfo_failure()
    {
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('user')
            ->andThrow(new RequestException('Unauthorized', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->getUserInfo();

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // User Subscription Tests
    // =====================================

    public function test_getUserSubscription_success()
    {
        $mockResponseData = [
            'tier' => 'pro',
            'character_count' => 25000,
            'character_limit' => 100000,
            'can_extend_character_limit' => true,
            'allowed_to_extend_character_limit' => true,
            'next_character_count_reset_unix' => time() + 86400,
            'voice_limit' => 50,
            'voice_count' => 12,
            'professional_voice_limit' => 10,
            'can_extend_voice_limit' => true,
            'can_use_instant_voice_cloning' => true,
            'can_use_professional_voice_cloning' => true,
            'currency' => 'USD',
            'status' => 'active'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('user/subscription')
            ->andReturn($mockResponse);

        $result = $this->service->getUserSubscription();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['subscription']);
        $this->assertArrayHasKey('tier', $result['subscription']);
        $this->assertArrayHasKey('character_count', $result['subscription']);
        $this->assertArrayHasKey('character_limit', $result['subscription']);
    }

    public function test_getUserSubscription_free_tier()
    {
        $mockResponseData = [
            'tier' => 'free',
            'character_count' => 9500,
            'character_limit' => 10000,
            'can_extend_character_limit' => false,
            'voice_limit' => 3,
            'voice_count' => 2,
            'can_use_instant_voice_cloning' => false,
            'can_use_professional_voice_cloning' => false,
            'status' => 'active'
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('user/subscription')
            ->andReturn($mockResponse);

        $result = $this->service->getUserSubscription();

        $this->assertTrue($result['success']);
        $this->assertEquals('free', $result['subscription']['tier']);
        $this->assertFalse($result['subscription']['can_extend_character_limit']);
    }

    // =====================================
    // Models Tests
    // =====================================

    public function test_getModels_success()
    {
        $mockResponseData = [
            [
                'model_id' => 'eleven_monolingual_v1',
                'name' => 'Eleven English v1',
                'can_be_finetuned' => false,
                'can_do_text_to_speech' => true,
                'can_do_voice_conversion' => false,
                'can_use_style' => false,
                'can_use_speaker_boost' => true,
                'serves_pro_voices' => false,
                'token_cost_factor' => 1.0,
                'description' => 'Use our English model for British voices.',
                'requires_alpha_access' => false,
                'max_characters_request_free_user' => 500,
                'max_characters_request_subscribed_user' => 5000,
                'maximum_text_length_per_request' => 5000,
                'language' => 'en'
            ],
            [
                'model_id' => 'eleven_multilingual_v2',
                'name' => 'Eleven Multilingual v2',
                'can_be_finetuned' => false,
                'can_do_text_to_speech' => true,
                'can_do_voice_conversion' => false,
                'can_use_style' => true,
                'can_use_speaker_boost' => true,
                'serves_pro_voices' => true,
                'token_cost_factor' => 1.0,
                'description' => 'Cutting-edge model for multilingual speech synthesis.',
                'requires_alpha_access' => false,
                'max_characters_request_free_user' => 500,
                'max_characters_request_subscribed_user' => 5000,
                'maximum_text_length_per_request' => 5000,
                'language' => 'multilingual'
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('models')
            ->andReturn($mockResponse);

        $result = $this->service->getModels();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['models']);
        $this->assertCount(2, $result['models']);
        $this->assertEquals('eleven_monolingual_v1', $result['models'][0]['model_id']);
        $this->assertEquals('eleven_multilingual_v2', $result['models'][1]['model_id']);
    }

    public function test_getModels_empty_response()
    {
        $mockResponseData = [];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('models')
            ->andReturn($mockResponse);

        $result = $this->service->getModels();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['models']);
        $this->assertEmpty($result['models']);
    }

    // =====================================
    // Character Usage Tests
    // =====================================

    public function test_getCharacterUsage_with_custom_dates()
    {
        $startUnix = time() - (7 * 24 * 60 * 60); // 7 days ago
        $endUnix = time();
        
        $mockResponseData = [
            'history' => [
                [
                    'usage' => 1500,
                    'date' => date('Y-m-d', $startUnix)
                ],
                [
                    'usage' => 2300,
                    'date' => date('Y-m-d', $startUnix + 86400)
                ],
                [
                    'usage' => 1800,
                    'date' => date('Y-m-d', $endUnix)
                ]
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $expectedUrl = 'usage/character-stats?' . http_build_query([
            'start_unix' => $startUnix,
            'end_unix' => $endUnix
        ]);
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with($expectedUrl)
            ->andReturn($mockResponse);

        $result = $this->service->getCharacterUsage($startUnix, $endUnix);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['history']);
        $this->assertCount(3, $result['history']['history']);
    }

    public function test_getCharacterUsage_with_default_dates()
    {
        $mockResponseData = [
            'history' => [
                [
                    'usage' => 5000,
                    'date' => date('Y-m-d', time() - 86400)
                ]
            ]
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with(Mockery::on(function ($url) {
                return str_contains($url, 'usage/character-stats?') && 
                       str_contains($url, 'start_unix=') && 
                       str_contains($url, 'end_unix=');
            }))
            ->andReturn($mockResponse);

        $result = $this->service->getCharacterUsage();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['history']);
    }

    public function test_getCharacterUsage_no_data()
    {
        $mockResponseData = ['history' => []];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with(Mockery::any())
            ->andReturn($mockResponse);

        $result = $this->service->getCharacterUsage();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['history']);
        $this->assertEmpty($result['history']['history']);
    }

    // =====================================
    // History Tests
    // =====================================

    public function test_getHistory_with_pagination()
    {
        $pageSize = 50;
        $startAfterId = 'history_item_123';
        
        $mockResponseData = [
            'history' => [
                [
                    'history_item_id' => 'hist_1',
                    'request_id' => 'req_1',
                    'voice_id' => 'voice_123',
                    'voice_name' => 'Test Voice',
                    'text' => 'Hello world',
                    'date_unix' => time() - 3600,
                    'character_count_change_from' => 1000,
                    'character_count_change_to' => 989,
                    'content_type' => 'text/plain',
                    'state' => 'created',
                    'settings' => [
                        'stability' => 0.5,
                        'similarity_boost' => 0.5
                    ]
                ],
                [
                    'history_item_id' => 'hist_2',
                    'request_id' => 'req_2',
                    'voice_id' => 'voice_456',
                    'voice_name' => 'Another Voice',
                    'text' => 'Test message',
                    'date_unix' => time() - 1800,
                    'character_count_change_from' => 989,
                    'character_count_change_to' => 977,
                    'content_type' => 'text/plain',
                    'state' => 'created'
                ]
            ],
            'last_history_item_id' => 'hist_2',
            'has_more' => true
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $expectedUrl = 'history?' . http_build_query([
            'page_size' => $pageSize,
            'start_after_history_item_id' => $startAfterId
        ]);
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with($expectedUrl)
            ->andReturn($mockResponse);

        $result = $this->service->getHistory($pageSize, $startAfterId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['history']);
        $this->assertCount(2, $result['history']['history']);
        $this->assertTrue($result['history']['has_more']);
    }

    public function test_getHistory_default_parameters()
    {
        $mockResponseData = [
            'history' => [
                [
                    'history_item_id' => 'hist_1',
                    'voice_name' => 'Default Voice',
                    'text' => 'Test text'
                ]
            ],
            'has_more' => false
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $expectedUrl = 'history?' . http_build_query(['page_size' => 100]);
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with($expectedUrl)
            ->andReturn($mockResponse);

        $result = $this->service->getHistory();

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['history']);
    }

    // =====================================
    // History Item Tests
    // =====================================

    public function test_getHistoryItem_success()
    {
        $historyItemId = 'hist_123';
        $mockResponseData = [
            'history_item_id' => $historyItemId,
            'request_id' => 'req_456',
            'voice_id' => 'voice_789',
            'voice_name' => 'Test Voice',
            'model_id' => 'eleven_multilingual_v2',
            'text' => 'This is a test message',
            'date_unix' => time() - 7200,
            'character_count_change_from' => 1000,
            'character_count_change_to' => 977,
            'content_type' => 'text/plain',
            'state' => 'created',
            'settings' => [
                'stability' => 0.75,
                'similarity_boost' => 0.85,
                'style' => 0.25,
                'use_speaker_boost' => true
            ],
            'feedback' => null,
            'share_link_id' => null
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("history/{$historyItemId}")
            ->andReturn($mockResponse);

        $result = $this->service->getHistoryItem($historyItemId);

        $this->assertTrue($result['success']);
        $this->assertEquals($mockResponseData, $result['item']);
        $this->assertEquals($historyItemId, $result['item']['history_item_id']);
        $this->assertArrayHasKey('settings', $result['item']);
    }

    public function test_getHistoryItem_not_found()
    {
        $historyItemId = 'invalid_hist_id';
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("history/{$historyItemId}")
            ->andThrow(new RequestException('History item not found', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->getHistoryItem($historyItemId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Delete History Item Tests
    // =====================================

    public function test_deleteHistoryItem_success()
    {
        $historyItemId = 'hist_123';
        
        $mockResponse = new Response(200, [], json_encode(['message' => 'History item deleted']));
        
        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("history/{$historyItemId}")
            ->andReturn($mockResponse);

        $result = $this->service->deleteHistoryItem($historyItemId);

        $this->assertTrue($result['success']);
    }

    public function test_deleteHistoryItem_not_found()
    {
        $historyItemId = 'invalid_hist_id';
        
        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("history/{$historyItemId}")
            ->andThrow(new RequestException('History item not found', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->deleteHistoryItem($historyItemId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Download History Tests
    // =====================================

    public function test_downloadHistory_success()
    {
        $historyItemIds = ['hist_1', 'hist_2', 'hist_3'];
        $audioData = 'fake-zip-binary-data-containing-audio-files';
        
        $mockResponse = new Response(200, ['Content-Type' => 'application/zip'], $audioData);
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('history/download', Mockery::on(function ($options) use ($historyItemIds) {
                return isset($options['json']['history_item_ids']) && 
                       $options['json']['history_item_ids'] === $historyItemIds;
            }))
            ->andReturn($mockResponse);

        $result = $this->service->downloadHistory($historyItemIds);

        $this->assertTrue($result['success']);
        $this->assertEquals($audioData, $result['audio']);
        $this->assertEquals('application/zip', $result['content_type']);
    }

    public function test_downloadHistory_empty_list()
    {
        $historyItemIds = [];
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('history/download', Mockery::on(function ($options) use ($historyItemIds) {
                return isset($options['json']['history_item_ids']) && 
                       $options['json']['history_item_ids'] === $historyItemIds;
            }))
            ->andThrow(new RequestException('No history items provided', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->downloadHistory($historyItemIds);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_downloadHistory_invalid_ids()
    {
        $historyItemIds = ['invalid_id_1', 'invalid_id_2'];
        
        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('history/download', Mockery::any())
            ->andThrow(new RequestException('Some history items not found', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->downloadHistory($historyItemIds);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // =====================================
    // Usage Summary Tests
    // =====================================

    public function test_getUsageSummary_success()
    {
        $userInfoData = [
            'user_id' => 'user_123',
            'email' => 'test@example.com',
            'tier' => 'pro'
        ];
        
        $characterUsageData = [
            'history' => [
                ['usage' => 1000, 'date' => '2023-12-01'],
                ['usage' => 1500, 'date' => '2023-12-02']
            ]
        ];
        
        // Mock getUserInfo
        $userInfoResponse = new Response(200, [], json_encode($userInfoData));
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('user')
            ->andReturn($userInfoResponse);
        
        // Mock getCharacterUsage
        $characterUsageResponse = new Response(200, [], json_encode($characterUsageData));
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with(Mockery::on(function ($url) {
                return str_contains($url, 'usage/character-stats?');
            }))
            ->andReturn($characterUsageResponse);

        $result = $this->service->getUsageSummary();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('summary', $result);
        $this->assertEquals($userInfoData, $result['summary']['user']);
        $this->assertEquals($characterUsageData, $result['summary']['history']);
        $this->assertArrayHasKey('generated_at', $result['summary']);
        
        // Verify generated_at is a valid ISO 8601 date
        $generatedAt = $result['summary']['generated_at'];
        $this->assertNotFalse(strtotime($generatedAt));
    }

    public function test_getUsageSummary_user_info_failure()
    {
        // Mock getUserInfo failure
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('user')
            ->andThrow(new RequestException('Unauthorized', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->getUsageSummary();

        $this->assertFalse($result['success']);
        $this->assertEquals('Failed to fetch usage summary', $result['error']);
    }

    public function test_getUsageSummary_character_usage_failure()
    {
        $userInfoData = ['user_id' => 'user_123'];
        
        // Mock getUserInfo success
        $userInfoResponse = new Response(200, [], json_encode($userInfoData));
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('user')
            ->andReturn($userInfoResponse);
        
        // Mock getCharacterUsage failure
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with(Mockery::on(function ($url) {
                return str_contains($url, 'usage/character-stats?');
            }))
            ->andThrow(new RequestException('Service unavailable', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->getUsageSummary();

        $this->assertFalse($result['success']);
        $this->assertEquals('Failed to fetch usage summary', $result['error']);
    }

    public function test_getUsageSummary_both_failures()
    {
        // Mock both failures
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('user')
            ->andThrow(new RequestException('Unauthorized', Mockery::mock('Psr\Http\Message\RequestInterface')));

        $result = $this->service->getUsageSummary();

        $this->assertFalse($result['success']);
        $this->assertEquals('Failed to fetch usage summary', $result['error']);
    }

    // =====================================
    // Edge Cases and Integration Tests
    // =====================================

    public function test_large_page_size_history()
    {
        $pageSize = 500; // Large page size
        $mockResponseData = [
            'history' => array_fill(0, $pageSize, [
                'history_item_id' => 'hist_' . rand(1, 999),
                'voice_name' => 'Test Voice',
                'text' => 'Test message'
            ]),
            'has_more' => false
        ];
        
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("history?page_size={$pageSize}")
            ->andReturn($mockResponse);

        $result = $this->service->getHistory($pageSize);

        $this->assertTrue($result['success']);
        $this->assertCount($pageSize, $result['history']['history']);
    }

    public function test_character_usage_edge_dates()
    {
        $startUnix = 0; // Unix epoch
        $endUnix = PHP_INT_MAX; // Far future
        
        $mockResponseData = ['history' => []];
        $mockResponse = new Response(200, [], json_encode($mockResponseData));
        
        $expectedUrl = 'usage/character-stats?' . http_build_query([
            'start_unix' => $startUnix,
            'end_unix' => $endUnix
        ]);
        
        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with($expectedUrl)
            ->andReturn($mockResponse);

        $result = $this->service->getCharacterUsage($startUnix, $endUnix);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['history']['history']);
    }
}
