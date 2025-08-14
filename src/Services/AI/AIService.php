<?php

namespace Samandar\LaravelElevenLabs\Services\AI;

use Samandar\LaravelElevenLabs\Services\Core\BaseElevenLabsService;

class AIService extends BaseElevenLabsService
{
    /**
     * Get signed URL token for starting websocket conversation
     */
    public function getSignedUrl(string $agentId): array
    {
        $endpoint = 'convai/conversations/get-signed-url?'.http_build_query(['agent_id' => $agentId]);
        return $this->get($endpoint);
    }

    /**
     * Get agent widget configuration
     */
    public function getAgentWidgetConfig(string $agentId, ?string $conversationSignature = null): array
    {
        $query = [];
        if ($conversationSignature) {
            $query['conversation_signature'] = $conversationSignature;
        }
        $endpoint = "convai/agents/{$agentId}/widget" . (!empty($query) ? ('?'.http_build_query($query)) : '');
        return $this->get($endpoint);
    }
    /**
     * Get conversational AI settings
     */
    public function getConversationalAISettings(): array
    {
        $result = $this->get('convai/settings');

        if ($result['success']) {
            return [
                'success' => true,
                'settings' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Update conversational AI settings
     */
    public function updateConversationalAISettings(array $settings): array
    {
        return $this->patch('convai/settings', [
            'json' => $settings
        ]);
    }

    /**
     * Get workspace secrets
     */
    public function getWorkspaceSecrets(): array
    {
        $result = $this->get('convai/secrets');

        if ($result['success']) {
            return [
                'success' => true,
                'secrets' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Create knowledge base from URL
     */
    public function createKnowledgeBaseFromURL(string $url): array
    {
        $result = $this->post('convai/knowledge-base/url', [
            'json' => ['url' => $url]
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'knowledge_base' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get knowledge base list
     */
    public function getKnowledgeBases(?string $cursor = null, ?int $pageSize = null): array
    {
        $params = [];
        if ($cursor) $params['cursor'] = $cursor;
        if ($pageSize) $params['page_size'] = $pageSize;

        $queryString = $params ? '?' . http_build_query($params) : '';
        $result = $this->get('convai/knowledge-base' . $queryString);

        if ($result['success']) {
            return [
                'success' => true,
                'knowledge_bases' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Delete knowledge base
     */
    public function deleteKnowledgeBase(string $documentationId): array
    {
        return $this->delete("convai/knowledge-base/{$documentationId}");
    }

    /**
     * Knowledge Base Documents: upload from file
     */
    public function createKnowledgeBaseDocumentFromFile(array $multipart): array
    {
        // $multipart e.g. [['name' => 'file', 'contents' => fopen(...), 'filename' => '...'], ...]
        return $this->post('convai/knowledge-base/documents/create-from-file', [
            'multipart' => $multipart,
            'headers' => ['xi-api-key' => $this->apiKey]
        ]);
    }

    /**
     * Knowledge Base Documents: get content by document id
     */
    public function getKnowledgeBaseDocumentContent(string $documentId): array
    {
        return $this->get("convai/knowledge-base/documents/{$documentId}/content");
    }

    /**
     * RAG Index overview
     */
    public function getRagIndexOverview(): array
    {
        return $this->get('convai/knowledge-base/rag-index-overview');
    }

    /**
     * Get agents with pagination support
     */
    public function getAgents(?string $cursor = null, ?int $pageSize = null): array
    {
        $query = [];
        if ($cursor) $query['cursor'] = $cursor;
        if ($pageSize) $query['page_size'] = $pageSize;
        
        $endpoint = 'convai/agents';
        if (!empty($query)) {
            $endpoint .= '?' . http_build_query($query);
        }
        
        $result = $this->get($endpoint);

        if ($result['success']) {
            return [
                'success' => true,
                'agents' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Create agent using the correct ElevenLabs API endpoint
     */
    public function createAgent(array $agentData): array
    {
        $result = $this->post('convai/agents/create', [
            'json' => $agentData
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'agent' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get agent
     */
    public function getAgent(string $agentId): array
    {
        $result = $this->get("convai/agents/{$agentId}");

        if ($result['success']) {
            return [
                'success' => true,
                'agent' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Update agent
     */
    public function updateAgent(string $agentId, array $agentData): array
    {
        $result = $this->post("convai/agents/{$agentId}", [
            'json' => $agentData
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'agent' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Delete agent
     */
    public function deleteAgent(string $agentId): array
    {
        return $this->delete("convai/agents/{$agentId}");
    }

    /**
     * Get conversations with pagination and filtering support
     */
    public function getConversations(
        ?string $cursor = null,
        ?int $pageSize = null,
        ?int $callStartAfterUnix = null,
        ?int $callStartBeforeUnix = null
    ): array {
        $query = [];
        if ($cursor) $query['cursor'] = $cursor;
        if ($pageSize) $query['page_size'] = $pageSize;
        if ($callStartAfterUnix) $query['call_start_after_unix'] = $callStartAfterUnix;
        if ($callStartBeforeUnix) $query['call_start_before_unix'] = $callStartBeforeUnix;
        
        $endpoint = 'convai/conversations';
        if (!empty($query)) {
            $endpoint .= '?' . http_build_query($query);
        }
        
        $result = $this->get($endpoint);

        if ($result['success']) {
            return [
                'success' => true,
                'conversations' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get conversations for a specific agent (backward compatibility)
     */
    public function getAgentConversations(string $agentId): array
    {
        $result = $this->get("convai/agents/{$agentId}/conversations");

        if ($result['success']) {
            return [
                'success' => true,
                'conversations' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Create conversation
     */
    public function createConversation(string $agentId): array
    {
        $result = $this->post("convai/agents/{$agentId}/conversations");

        if ($result['success']) {
            return [
                'success' => true,
                'conversation' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get specific conversation
     */
    public function getConversation(string $conversationId): array
    {
        $result = $this->get("convai/conversations/{$conversationId}");
        
        if ($result['success']) {
            return [
                'success' => true,
                'conversation' => $result['data'],
            ];
        }
        
        return $result;
    }

    /**
     * Get conversation audio
     */
    public function getConversationAudio(string $conversationId): array
    {
        // API docs ko'p hollarda GET misollarini ko'rsatadi
        $result = $this->getBinary("convai/conversations/{$conversationId}/audio");
        
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
     * Submit batch calling job
     */
    public function submitBatchCalling(array $batchData): array
    {
        $result = $this->post('convai/batch-calling/submit', ['json' => $batchData]);
        
        if ($result['success']) {
            return [
                'success' => true,
                'batch' => $result['data'],
            ];
        }
        
        return $result;
    }

    /**
     * Get batch calling status
     */
    public function getBatchCalling(string $batchId): array
    {
        $result = $this->get("convai/batch-calling/{$batchId}");
        
        if ($result['success']) {
            return [
                'success' => true,
                'batch' => $result['data'],
            ];
        }
        
        return $result;
    }

    /**
     * Tools: list
     */
    public function listTools(): array
    {
        return $this->get('convai/tools');
    }

    /**
     * Tools: get
     */
    public function getTool(string $toolId): array
    {
        return $this->get("convai/tools/{$toolId}");
    }

    /**
     * Tools: create
     */
    public function createTool(array $payload): array
    {
        return $this->post('convai/tools', ['json' => $payload]);
    }

    /**
     * Tools: dependent agents
     */
    public function getDependentAgents(string $toolId): array
    {
        return $this->get("convai/tools/{$toolId}/dependent-agents");
    }

    /**
     * MCP servers: list
     */
    public function listMcpServers(): array
    {
        return $this->get('convai/mcp-servers');
    }

    /**
     * MCP servers: create
     */
    public function createMcpServer(array $payload): array
    {
        return $this->post('convai/mcp-servers', ['json' => $payload]);
    }

    /**
     * MCP approval policies: create
     */
    public function createMcpApprovalPolicy(array $payload): array
    {
        return $this->post('convai/mcp-servers/approval-policies', ['json' => $payload]);
    }

    /**
     * Dashboard settings
     */
    public function getDashboardSettings(): array
    {
        return $this->get('convai/dashboard/settings');
    }
}
