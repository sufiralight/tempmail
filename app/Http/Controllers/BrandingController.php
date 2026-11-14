<?php

namespace App\Http\Controllers;

use App\Administration\AuditLog;
use App\Media\MediaStorage;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class BrandingController extends Controller
{
    public const SLOTS = ['logo', 'dark-logo', 'favicon', 'social'];

    public function show(Request $request)
    {
        $selected = DB::table('branding_assets')->pluck('media_id', 'slot')->all();
        $submitted = $request->session()->getOldInput();
        $owned = Media::query()->where('user_id', $request->user()->id)->where('thumbnail', true)
            ->whereKey(array_values(array_filter(array_intersect_key($submitted, array_flip(self::SLOTS)),
                fn ($id) => is_scalar($id) && filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false)))
            ->pluck('filename', 'id');
        $names = Media::query()->whereKey(array_values($selected))->pluck('filename', 'id');
        $drafts = [];
        $baseline = [];
        foreach (self::SLOTS as $slot) {
            $saved = $selected[$slot] ?? null;
            $baseline[$slot] = $saved === null ? '' : (string) $saved;
            $id = array_key_exists($slot, $submitted) ? $submitted[$slot] : $saved;
            $id = is_scalar($id) && filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false ? (string) $id : '';
            $published = $id !== '' && $id === $baseline[$slot];
            $drafts[$slot] = [
                'id' => $id,
                'filename' => $published ? ($names[$id] ?? '') : ($owned[$id] ?? ''),
                'preview' => $published ? route('branding.asset', ['slot' => $slot], absolute: false)
                    : (isset($owned[$id]) ? route('media.file', ['media' => $id, 'preview' => 1], absolute: false) : ''),
            ];
        }

        return view('media.branding', compact('drafts', 'baseline'));
    }

    public function update(Request $request, AuditLog $audit, MediaStorage $storage)
    {
        $rules = array_fill_keys(self::SLOTS, 'present|nullable|integer|min:1');
        $data = $request->validate($rules);
        DB::transaction(function () use ($request, $audit, $data, $storage): void {
            DB::table('workspace_settings')->where('id', 1)->increment('revision');
            $current = DB::table('branding_assets')->pluck('media_id', 'slot');
            foreach ($data as $slot => $id) {
                if ($id !== null) {
                    $media = Media::query()->lockForUpdate()->find($id);
                    if ($media === null || ! $media->thumbnail
                        || ((int) $current->get($slot) !== (int) $id && (int) $media->user_id !== (int) $request->user()->id)
                        || ! Storage::disk('media')->exists($storage->path($media, true))) {
                        throw ValidationException::withMessages([$slot => 'Choose an active image from your library with an available preview.']);
                    }
                }

                DB::table('branding_assets')->where('slot', $slot)->delete();
                if ($id !== null) {
                    DB::table('branding_assets')->insert(['slot' => $slot, 'media_id' => $id]);
                }
            }
            $audit->record($request->user(), 'branding.updated', 'workspace', 1);
        }, 3);

        return redirect()->route('admin.branding')->with('status', 'Branding saved.');
    }

    public function asset(string $slot, MediaStorage $storage)
    {
        abort_unless(in_array($slot, self::SLOTS, true), 404);
        $media = Media::findOrFail(DB::table('branding_assets')->where('slot', $slot)->value('media_id'));
        abort_unless($media->thumbnail, 404);
        $disk = Storage::disk('media');
        $path = $storage->path($media, true);
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Content-Type' => 'image/png', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store',
        ]);
    }
}
