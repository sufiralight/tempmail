<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadMedia;
use App\Media\MediaStorage;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class MediaController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'type' => 'nullable|in:jpg,png,webp,gif', 'sort' => 'nullable|in:newest,oldest,name,size', 'layout' => 'nullable|in:grid,list']);
        $admin = $request->routeIs('admin.media.index');
        $query = Media::query();
        if (! $admin) {
            $query->where('user_id', $request->user()->id);
        }
        if (! empty($filters['q'])) {
            $pattern = '%'.$filters['q'].'%';
            $query->where(fn ($items) => $items->whereLike('filename', $pattern)->orWhereLike('title', $pattern));
        }
        if (! empty($filters['type'])) {
            $query->where('extension', $filters['type']);
        }
        match ($filters['sort'] ?? 'newest') {
            'name' => $query->orderBy('filename'),
            'size' => $query->orderByDesc('bytes'),
            'oldest' => $query->orderBy('id'),
            default => $query->orderByDesc('id'),
        };

        return response()->view($request->routeIs('media.picker') ? 'media.picker' : 'media.index', [
            'items' => $query->orderBy('id')->paginate(20)->appends($filters), 'filters' => $filters, 'admin' => $admin,
        ]);
    }

    public function legacyIndex(Request $request): RedirectResponse
    {
        return redirect()->route('media.index', $request->only(['q', 'type', 'sort', 'layout', 'page']));
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

        return view('media.show', compact('media', 'admin'));
    }

    public function store(UploadMedia $request, MediaStorage $storage)
    {
        $media = $storage->store($request->user(), $request->file('image'));

        return $request->expectsJson() ? response()->json(['id' => $media->id], 201)
            : redirect()->route($request->boolean('picker') ? 'media.picker' : 'media.index')->with('status', 'Image uploaded.');
    }

    public function update(Request $request, Media $media)
    {
        Gate::authorize('view', $media);
        $media->forceFill($request->validate(['title' => 'nullable|string|max:255', 'alt_text' => 'nullable|string|max:255']))->save();

        return redirect()->route('media.show', $media)->with('status', 'Image details saved.');
    }

    public function destroy(Request $request, Media $media, MediaStorage $storage)
    {
        Gate::authorize('view', $media);
        $storage->delete($request->user(), [$media->id], false);

        return redirect()->route('media.index')->with('status', 'Image removed.');
    }

    public function bulkDelete(Request $request, MediaStorage $storage)
    {
        $data = $request->validate(['items' => 'required|array|max:20', 'items.*' => 'required|integer|distinct']);
        $storage->delete($request->user(), array_map('intval', $data['items']), true);

        return redirect()->route('media.index')->with('status', 'Selected images removed.');
    }

    public function file(Request $request, Media $media, MediaStorage $storage)
    {
        $admin = $request->routeIs('admin.media.file');
        Gate::authorize($admin ? 'media.audit' : 'view', $admin ? [] : $media);
        $preview = $media->thumbnail && $request->boolean('preview');
        $disk = Storage::disk('media');
        $path = $storage->path($media, $preview);
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Content-Type' => $preview ? 'image/png' : $media->mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
