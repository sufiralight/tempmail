<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AccountSessions
{
    public function digest(Request $request): string
    {
        return hash_hmac('sha256', $request->session()->getId(), (string) config('app.key'));
    }

    public function active(User $user): Builder
    {
        return DB::table('account_sessions')->where('user_id', $user->id)
            ->where('revoked', false)->where('last_seen', '>', now()->timestamp - 60 * (int) config('session.lifetime'));
    }

    public function observe(Request $request, User $user): bool
    {
        $digest = $this->digest($request);
        $query = DB::table('account_sessions')->where('digest', $digest);
        $entry = $query->first();
        if ($entry === null) {
            $agent = (string) $request->userAgent();
            $device = match (true) {
                str_contains($agent, 'Edg/') => 'Edge',
                str_contains($agent, 'Firefox/') => 'Firefox',
                str_contains($agent, 'Chrome/') => 'Chrome',
                str_contains($agent, 'Safari/') => 'Safari',
                default => 'Unknown browser',
            };

            try {
                DB::table('account_sessions')->insert([
                    'id' => (string) Str::uuid(), 'user_id' => $user->id, 'digest' => $digest,
                    'device' => $device, 'last_seen' => now()->timestamp,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Another request registered the same session.
            }
        }

        if (! (clone $query)->where('user_id', $user->id)->where('revoked', false)->exists()) {
            return false;
        }

        $query->where('revoked', false)->where('last_seen', '<', now()->timestamp - 60)
            ->update(['last_seen' => now()->timestamp]);

        return true;
    }

    public function revokeOthers(Request $request, User $user, ?string $id = null): int
    {
        return $this->active($user)->where('digest', '<>', $this->digest($request))
            ->when($id !== null, fn (Builder $query) => $query->where('id', $id))
            ->update(['revoked' => true, 'last_seen' => now()->timestamp]);
    }

    public function retire(Request $request): void
    {
        DB::table('account_sessions')->where('digest', $this->digest($request))
            ->update(['revoked' => true, 'last_seen' => now()->timestamp]);
    }

    public function prune(): void
    {
        DB::table('account_sessions')->where('last_seen', '<', now()->subMinutes(max(1440, (int) config('session.lifetime')))->timestamp)->delete();
    }
}
