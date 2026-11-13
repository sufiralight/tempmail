<?php

namespace App\Media;

use App\Administration\AuditLog;
use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProfileAvatar
{
    public function __construct(private MediaStorage $storage, private AuditLog $audit) {}

    public function change(User $owner, ?int $id): void
    {
        DB::transaction(function () use ($owner, $id): void {
            DB::table('workspace_settings')->where('id', 1)->increment('revision');
            $account = User::query()->findOrFail($owner->id);
            abort_unless($account->is_active, 403);

            if ($id !== null) {
                $media = $this->eligibleMedia($account, $id);
                if ($media === null || ! Storage::disk('media')->exists($this->storage->path($media))) {
                    throw ValidationException::withMessages(['avatar_media_id' => 'Choose an active image from your library with an available preview.']);
                }
                Gate::forUser($account)->authorize('view', $media);
            }
            $account->forceFill(['avatar_media_id' => $id])->save();
            $this->audit->record($account, $id === null ? 'account.avatar_removed' : 'account.avatar_changed', 'user', $account->id);
        }, 3);

        $owner->refresh();
    }

    public function preview(User $account, int $id): StreamedResponse
    {
        abort_unless((int) $account->avatar_media_id === $id, 404);
        $media = $this->eligibleMedia($account, $id);
        abort_if($media === null, 404);

        return Storage::disk('media')->response($this->storage->path($media, true), null, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    private function eligibleMedia(User $account, int $id): ?Media
    {
        $media = Media::query()->where('user_id', $account->id)->where('thumbnail', true)->find($id);
        $disk = Storage::disk('media');

        return $media && $disk->exists($this->storage->path($media, true)) ? $media : null;
    }
}
