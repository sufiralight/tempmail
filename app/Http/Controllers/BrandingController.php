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
        return view('media.branding', ['selected' => DB::table('branding_assets')->pluck('media_id', 'slot')]);
    }

    public function update(Request $request, AuditLog $audit)
    {
        $rules = array_fill_keys(self::SLOTS, 'present|nullable|integer|min:1');
        $data = $request->validate($rules);
        DB::transaction(function () use ($request, $audit, $data): void {
            DB::table('workspace_settings')->where('id', 1)->increment('revision');
            foreach ($data as $slot => $id) {
                if ($id !== null && ! Media::query()->whereKey($id)->where('user_id', $request->user()->id)->where('thumbnail', true)->exists()) {
                    throw ValidationException::withMessages([$slot => 'Choose an image from your library with an available preview.']);
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
