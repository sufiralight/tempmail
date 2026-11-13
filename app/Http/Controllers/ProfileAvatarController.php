<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadMedia;
use App\Media\MediaStorage;
use App\Media\ProfileAvatar;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProfileAvatarController extends Controller
{
    public function upload(UploadMedia $request, MediaStorage $storage, ProfileAvatar $avatar): RedirectResponse
    {
        $media = $storage->store($request->user(), $request->file('image'));
        $avatar->change($request->user(), $media->id);

        return redirect()->route('profile.show')->with('status', 'Profile photo updated.');
    }

    public function update(Request $request, ProfileAvatar $avatar): RedirectResponse
    {
        $data = $request->validate(['avatar_media_id' => 'required|integer|min:1']);
        $avatar->change($request->user(), (int) $data['avatar_media_id']);

        return redirect()->route('profile.show')->with('status', 'Profile photo updated.');
    }

    public function destroy(Request $request, ProfileAvatar $avatar): RedirectResponse
    {
        $avatar->change($request->user(), null);

        return redirect()->route('profile.show')->with('status', 'Profile photo removed.');
    }

    public function show(Request $request, int $mediaId, ProfileAvatar $avatar): StreamedResponse
    {
        return $avatar->preview($request->user(), $mediaId);
    }

    public function adminShow(User $user, int $mediaId, ProfileAvatar $avatar): StreamedResponse
    {
        return $avatar->preview($user, $mediaId);
    }
}
