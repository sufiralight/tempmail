<?php

namespace App\Administration;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AuditLog
{
    /** @param array<string, string> $metadata */
    public function record(User $actor, string $action, string $type, int $id, array $metadata = []): void
    {
        DB::table('administrative_audits')->insert([
            'actor_id' => $actor->id, 'action' => $action, 'target_type' => $type, 'target_id' => $id,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }
}
