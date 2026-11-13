<?php

namespace App\Media;

use App\Administration\AuditLog;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

final class MediaStorage
{
    public function __construct(private AuditLog $audit) {}

    public function store(User $owner, UploadedFile $file): Media
    {
        $path = $file->getPathname();
        $mime = $file->getMimeType();
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $size = @getimagesize($path);
        $extension = strtolower($file->getClientOriginalExtension());
        $memoryLimit = ini_parse_quantity((string) ini_get('memory_limit'));

        if (! isset($types[$mime]) || ! $size || $size['mime'] !== $mime
            || ($extension !== $types[$mime] && ! ($mime === 'image/jpeg' && $extension === 'jpeg'))
            || $size[0] < 1 || $size[1] < 1 || max($size[0], $size[1]) > 4096 || $size[0] * $size[1] > 8000000
            || ($memoryLimit > 0 && memory_get_usage(true) + 12 * $size[0] * $size[1] + 2 * $file->getSize() + 8388608 > $memoryLimit)
            || ! function_exists('imagecreatefromstring')) {
            throw ValidationException::withMessages(['image' => 'Choose a supported image within the size limits. Uploads require PHP GD.']);
        }

        $contents = file_get_contents($path);
        $image = $contents === false ? false : @imagecreatefromstring($contents);
        if ($image === false) {
            throw ValidationException::withMessages(['image' => 'The image could not be decoded.']);
        }

        $key = (string) Str::uuid();
        $disk = Storage::disk('media');
        $original = $key.'.'.$types[$mime];
        try {
            if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
                $orientation = @exif_read_data($path)['Orientation'] ?? 1;
                if (in_array($orientation, [2, 4, 5, 7], true)) {
                    imageflip($image, IMG_FLIP_HORIZONTAL);
                }
                $angle = match ($orientation) {
                    3, 4 => 180, 5, 8 => 90, 6, 7 => 270, default => 0
                };
                if ($angle !== 0) {
                    $image = imagerotate($image, $angle, 0) ?: $image;
                }
            }
            $scale = min(1, 768 / max(imagesx($image), imagesy($image)));
            $thumb = imagescale($image, max(1, (int) round(imagesx($image) * $scale)), max(1, (int) round(imagesy($image) * $scale)));
            $preview = false;
            if ($thumb !== false) {
                imagesavealpha($thumb, true);
                ob_start();
                try {
                    $encoded = imagepng($thumb);
                    $preview = $encoded ? ob_get_contents() : false;
                } finally {
                    ob_end_clean();
                }
            }

            if (! $disk->put($original, $contents) || ($preview !== false && ! $disk->put($key.'.preview.png', $preview))) {
                throw new HttpException(507);
            }

            return DB::transaction(function () use ($owner, $file, $key, $types, $mime, $size, $preview): Media {
                $media = new Media;
                $media->forceFill([
                    'user_id' => $owner->id, 'storage_key' => $key,
                    'filename' => Str::limit(preg_replace('/[[:cntrl:]]/u', '', basename(str_replace('\\', '/', $file->getClientOriginalName()))) ?: 'Image', 240, ''),
                    'mime' => $mime, 'extension' => $types[$mime], 'bytes' => $file->getSize(),
                    'width' => $size[0], 'height' => $size[1], 'thumbnail' => $preview !== false,
                ])->save();
                $this->audit->record($owner, 'media.uploaded', 'media', $media->id);

                return $media;
            });
        } catch (Throwable $exception) {
            if (! Media::withTrashed()->where('storage_key', $key)->exists()) {
                $disk->delete([$original, $key.'.preview.png']);
            }
            throw $exception;
        }
    }

    /** @param list<int> $ids */
    public function delete(User $owner, array $ids, bool $bulk): void
    {
        $items = DB::transaction(function () use ($owner, $ids, $bulk) {
            DB::table('workspace_settings')->where('id', 1)->increment('revision');
            $items = Media::withTrashed()->whereIn('id', $ids)->where('user_id', $owner->id)->get();
            abort_unless($items->count() === count($ids), 404);
            if (DB::table('branding_assets')->whereIn('media_id', $ids)->exists()) {
                throw ValidationException::withMessages(['media' => 'Remove the branding references before deleting these images.']);
            }
            foreach ($items as $item) {
                if (! $item->trashed()) {
                    $item->delete();
                    $this->audit->record($owner, $bulk ? 'media.bulk_deleted' : 'media.deleted', 'media', $item->id);
                }
            }

            return $items;
        }, 3);

        foreach ($items as $item) {
            $this->purge($item);
        }
    }

    public function purge(Media $media): void
    {
        if (Storage::disk('media')->delete([$this->path($media), $this->path($media, true)])) {
            $media->forceDelete();
        }
    }

    public function path(Media $media, bool $preview = false): string
    {
        abort_unless(Str::isUuid($media->storage_key) && in_array($media->extension, ['jpg', 'png', 'webp', 'gif'], true), 404);

        return $media->storage_key.($preview ? '.preview.png' : '.'.$media->extension);
    }
}
