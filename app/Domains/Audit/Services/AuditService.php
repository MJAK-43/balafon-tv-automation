<?php

namespace App\Domains\Audit\Services;

use App\Domains\Audit\Models\AuditLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AuditService
{
    public function log(
        string $action,
        string $entityType,
        ?string $entityId = null,
        array $context = [],
        ?int $actorId = null,
        string $actorType = 'system',
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AuditLog {
        return AuditLog::create([
            'uuid' => (string) Str::uuid(),
            'actor_id' => $actorId,
            'actor_type' => $actorType,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'context' => $context,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => Carbon::now(),
        ]);
    }
}
