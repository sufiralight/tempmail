<?php

namespace App\Administration;

use App\Auth\WorkspaceRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class WorkspaceAdministration
{
    public const ACTIONS = ['user.role_changed', 'user.activated', 'user.deactivated', 'user.change_denied', 'settings.updated'];

    public function updateUser(User $actor, User $target, WorkspaceRole $role, bool $active): void
    {
        $denied = DB::transaction(function () use ($actor, $target, $role, $active): bool {
            $this->lock();
            $actor = User::query()->findOrFail($actor->id);
            $target = User::query()->findOrFail($target->id);
            abort_unless($actor->can('manage', $target), 403);

            if ($target->isSuperAdmin() && $target->is_active && (! $active || $role !== WorkspaceRole::SuperAdmin)
                && ! User::query()->where('is_super_admin', true)->where('is_active', true)->whereKeyNot($target->id)->exists()) {
                $this->record($actor, 'user.change_denied', 'user', $target->id, ['reason' => 'last_active_super_admin']);

                return true;
            }

            $previous = $target->workspaceRole();
            $wasActive = $target->is_active;
            $target->forceFill([
                'role' => $role === WorkspaceRole::Admin ? 'admin' : 'user',
                'is_super_admin' => $role === WorkspaceRole::SuperAdmin,
                'is_active' => $active,
            ])->save();

            if ($previous !== $role) {
                $this->record($actor, 'user.role_changed', 'user', $target->id, ['from' => $previous->value, 'to' => $role->value]);
            }
            if ($wasActive !== $active) {
                $this->record($actor, $active ? 'user.activated' : 'user.deactivated', 'user', $target->id);
            }

            return false;
        }, 3);

        if ($denied) {
            throw ValidationException::withMessages(['role' => 'Keep at least one active Super Admin.']);
        }
    }

    public function updateName(User $actor, string $name): void
    {
        DB::transaction(function () use ($actor, $name): void {
            $this->lock();
            $actor = User::query()->findOrFail($actor->id);
            abort_unless($actor->can('settings.manage') && $actor->hasVerifiedEmail(), 403);
            if (DB::table('workspace_settings')->where('id', 1)->value('display_name') !== $name) {
                DB::table('workspace_settings')->where('id', 1)->update(['display_name' => $name]);
                $this->record($actor, 'settings.updated', 'workspace', 1, ['field' => 'display_name']);
            }
        }, 3);
    }

    private function lock(): void
    {
        // A write to the same row serializes privileged changes, including on SQLite.
        abort_unless(DB::table('workspace_settings')->where('id', 1)->increment('revision') === 1, 503);
    }

    /** @param array<string, string> $metadata */
    private function record(User $actor, string $action, string $type, int $id, array $metadata = []): void
    {
        DB::table('administrative_audits')->insert([
            'actor_id' => $actor->id, 'action' => $action, 'target_type' => $type, 'target_id' => $id,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }
}
