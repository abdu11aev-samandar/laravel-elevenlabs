<?php

namespace Samandar\LaravelElevenLabs\Services\Core;

use Samandar\LaravelElevenLabs\Services\Core\BaseElevenLabsService;

class WorkspaceService extends BaseElevenLabsService
{
    /**
     * Share workspace resource
     */
    public function shareWorkspaceResource(string $resourceId, array $shareData): array
    {
        $result = $this->post("/workspace/resources/{$resourceId}/share", [
            'json' => $shareData
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'share' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get workspace resources
     */
    public function getWorkspaceResources(): array
    {
        $result = $this->get('/workspace/resources');

        if ($result['success']) {
            return [
                'success' => true,
                'resources' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get workspace members
     */
    public function getWorkspaceMembers(): array
    {
        $result = $this->get('/workspace/members');

        if ($result['success']) {
            return [
                'success' => true,
                'members' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Invite workspace member
     */
    public function inviteWorkspaceMember(string $email, array $permissions = []): array
    {
        $result = $this->post('/workspace/members/invite', [
            'json' => [
                'email' => $email,
                'permissions' => $permissions,
            ]
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'invitation' => $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Remove workspace member
     */
    public function removeWorkspaceMember(string $memberId): array
    {
        return $this->delete("/workspace/members/{$memberId}");
    }
}
