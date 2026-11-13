@php($user = auth()->user())
<x-workspace.profile title="Personal information" description="Manage the name and email address on your account.">
    <x-ui.card title="Profile photo">
        <div class="grid gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.avatar :name="$user->name" :src="$user->avatarUrl()" size="lg" />
                <div>
                    <p class="text-sm font-medium">{{ $user->avatar_media_id ? 'Current profile photo' : 'Initials avatar' }}</p>
                    <p class="ui-help">JPEG, PNG, WebP or GIF. Up to {{ config('media.max_kib') }} KiB and 4,096 pixels per side.</p>
                </div>
            </div>
            <form method="post" action="{{ route('profile.avatar.upload', absolute: false) }}" enctype="multipart/form-data" class="grid max-w-xl gap-3">
                @csrf
                <x-ui.input type="file" name="image" label="New profile photo" accept="image/jpeg,image/png,image/webp,image/gif" required />
                <x-ui.button type="submit" icon="upload" class="justify-self-start" busy-on-submit loading-label="Uploading photo…">{{ $user->avatar_media_id ? 'Replace with upload' : 'Upload photo' }}</x-ui.button>
            </form>
            <form method="post" action="{{ route('profile.avatar.update', absolute: false) }}" class="grid justify-items-start gap-2 border-t border-border pt-3">
                @csrf
                @method('PATCH')
                <x-ui.media-picker name="avatar_media_id" label="Photo from Media Library" avatar />
                <x-ui.button type="submit" variant="outline" busy-on-submit>Use selected photo</x-ui.button>
            </form>
            @if($user->avatar_media_id)
                <x-ui.modal title="Remove profile photo?">
                    <x-slot:trigger><x-ui.button variant="ghost">Remove photo</x-ui.button></x-slot:trigger>
                    <p class="ui-help mb-3">Your initials will be shown. The image stays in your Media Library.</p>
                    <form method="post" action="{{ route('profile.avatar.destroy', absolute: false) }}">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="destructive" busy-on-submit>Remove photo</x-ui.button>
                    </form>
                </x-ui.modal>
            @endif
        </div>
    </x-ui.card>
    <x-ui.card>
        <form method="post" action="/profile" class="grid max-w-xl gap-4">
            @csrf
            @method('PATCH')
            <x-ui.input name="name" label="Full name" :value="old('name', auth()->user()->name)" autocomplete="name" maxlength="255" required />
            <x-ui.input name="email" label="Email address" type="email" :value="old('email', auth()->user()->email)" autocomplete="username" maxlength="255" required />
            <x-ui.password-field name="current_password" label="Current password" help="Required to change your email address." />
            <x-ui.button type="submit" class="justify-self-start" busy-on-submit loading-label="Saving profile…">Save profile</x-ui.button>
        </form>
    </x-ui.card>
</x-workspace.profile>
