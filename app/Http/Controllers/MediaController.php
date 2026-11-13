<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadMedia;
use App\Media\MediaStorage;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class MediaController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        $admin = $request->routeIs('admin.media.index');
        $trash = $request->routeIs('media.trash');
        $picker = $request->routeIs('media.picker');
        if ($picker && $request->boolean('avatar')) {
            $filters['avatar'] = 1;
        }
        $query = $trash ? Media::onlyTrashed() : Media::query();
        if ($picker) {
            $query->where('thumbnail', true);
        }
        if (! $admin) {
            $query->where('user_id', $request->user()->id);
        }
        $hasSearch = ($filters['q'] ?? '') !== '';
        $hasType = ! empty($filters['type']);
        $collectionTotal = ! $picker && ($hasSearch || $hasType) ? (clone $query)->count() : null;
        if ($hasSearch) {
            $pattern = '%'.$filters['q'].'%';
            $query->where(fn ($items) => $items->whereLike('filename', $pattern)->orWhereLike('title', $pattern)->orWhereLike('alt_text', $pattern));
        }
        if ($hasType) {
            $query->where('extension', $filters['type']);
        }
        match ($filters['sort'] ?? 'newest') {
            'name' => $query->orderBy('filename'),
            'name_desc' => $query->orderByDesc('filename'),
            'size' => $query->orderByDesc('bytes'),
            'oldest' => $query->orderBy('id'),
            default => $query->orderByDesc('id'),
        };

        $columns = ['id', 'filename', 'title', 'alt_text', 'extension', 'bytes', 'thumbnail', 'created_at', 'deleted_at', 'purge_requested_at'];
        if ($picker) {
            array_push($columns, 'caption', 'description');
        }
        $items = $query->orderBy('id')->paginate($picker ? 20 : (int) ($filters['per_page'] ?? 20), $columns)->appends($filters);

        $collectionTotal ??= $items->total();

        return response()->view($picker ? 'media.picker' : 'media.index', compact('items', 'filters', 'admin', 'trash', 'collectionTotal'));
    }

    public function legacyIndex(Request $request): RedirectResponse
    {
        return redirect()->route('media.index', $request->only(['q', 'type', 'sort', 'layout', 'per_page', 'page']));
    }

    public function legacyPicker(Request $request): RedirectResponse
    {
        return redirect()->route('media.picker', $request->only(['q', 'type', 'sort', 'page']));
    }

    public function legacyShow(Media $media): RedirectResponse
    {
        Gate::authorize('view', $media);

        return redirect()->route('media.show', $media);
    }

    public function legacyFile(Request $request, Media $media): RedirectResponse
    {
        Gate::authorize('view', $media);

        return redirect()->route('media.file', ['media' => $media, 'preview' => $request->boolean('preview')]);
    }

    public function show(Request $request, Media $media): View
    {
        $admin = $request->routeIs('admin.media.show');
        Gate::authorize($admin ? 'media.audit' : 'view', $admin ? [] : $media);

        $usedForBranding = DB::table('branding_assets')->where('media_id', $media->id)->exists();
        $usedForAvatar = User::query()->where('avatar_media_id', $media->id)->exists();

        return view('media.show', compact('media', 'admin', 'usedForBranding', 'usedForAvatar'));
    }

    public function store(UploadMedia $request, MediaStorage $storage)
    {
        $media = $storage->store($request->user(), $request->file('image'));

        return $request->expectsJson() ? response()->json(['id' => $media->id, 'filename' => $media->filename, 'details' => route('media.show', $media, absolute: false)], 201)
            : ($request->boolean('picker')
                ? redirect()->route('media.picker', $request->boolean('avatar') ? ['avatar' => 1] : [])
                : redirect()->route('media.show', $media))->with('status', 'Image uploaded.');
    }

    public function update(Request $request, Media $media)
    {
        Gate::authorize('view', $media);
        $media->forceFill($request->validate([
            'title' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:4000',
        ]))->save();

        return redirect()->route('media.show', $media)->with('status', 'Image metadata saved.');
    }

    public function destroy(Request $request, Media $media, MediaStorage $storage)
    {
        Gate::authorize('view', $media);
        $storage->moveToTrash($request->user(), [$media->id]);

        return redirect()->route('media.index')->with('status', 'Image moved to Trash.');
    }

    public function bulkDelete(Request $request, MediaStorage $storage)
    {
        $filters = $this->filters($request);
        $storage->moveToTrash($request->user(), $this->selection($request));

        return redirect()->route('media.index', $filters)->with('status', 'Selected images moved to Trash.');
    }

    public function restore(Request $request, MediaStorage $storage): RedirectResponse
    {
        $filters = $this->filters($request);
        $storage->restore($request->user(), $this->selection($request));

        return redirect()->route('media.trash', $filters)->with('status', 'Selected images restored.');
    }

    public function purge(Request $request, MediaStorage $storage): RedirectResponse
    {
        $filters = $this->filters($request);
        $ids = $this->selection($request);
        $completed = $storage->permanentlyDelete($request->user(), $ids);
        $response = redirect()->route('media.trash', $filters);
        if ($completed !== count($ids)) {
            return $response->withErrors(['media' => "{$completed} of ".count($ids).' images permanently deleted. Retry the remaining images from Trash.']);
        }

        return $response->with('status', 'Selected images permanently deleted.');
    }

    private function filters(Request $request): array
    {
        return $request->validate(['q' => 'nullable|string|max:100', 'type' => 'nullable|in:jpg,png,webp,gif', 'sort' => 'nullable|in:newest,oldest,name,name_desc,size', 'layout' => 'nullable|in:grid,list', 'per_page' => 'nullable|in:20,40,80,100']);
    }

    /** @return list<int> */
    private function selection(Request $request): array
    {
        $data = $request->validate([
            'items' => 'required|array|list|max:'.MediaStorage::MAX_SELECTION,
            'items.*' => 'required|integer|min:1|distinct',
        ]);

        return array_map('intval', $data['items']);
    }

    public function file(Request $request, Media $media, MediaStorage $storage)
    {
        $admin = $request->routeIs('admin.media.file');
        Gate::authorize($admin ? 'media.audit' : 'view', $admin ? [] : $media);
        $preview = $request->boolean('preview');
        abort_if($preview && ! $media->thumbnail, 404);
        $disk = Storage::disk('media');
        $path = $storage->path($media, $preview);
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Content-Type' => $preview ? 'image/png' : $media->mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
