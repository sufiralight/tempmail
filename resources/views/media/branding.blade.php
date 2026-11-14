<x-layouts.workspace title="Global branding" description="Select assets, preview their use, and save your public branding.">
    <form method="post" action="{{ route('admin.branding.update') }}" class="grid gap-6"
        x-data="{ drafts: @js($drafts), baseline: @js($baseline), saving: false,
            get dirty() { return Object.keys(this.baseline).some(slot => String(this.drafts[slot].id) !== this.baseline[slot]) } }"
        @submit="saving = true" @pageshow.window="saving = false"
        @beforeunload.window="if (dirty && !saving) { $event.preventDefault(); $event.returnValue = true }">
        @csrf
        @method('PATCH')
        @foreach([
            'Logos' => ['logo' => 'Site logo', 'dark-logo' => 'Dark-mode logo'],
            'Browser tab' => ['favicon' => 'Favicon'],
            'Social sharing' => ['social' => 'Social sharing image'],
        ] as $heading => $slots)
            <section class="grid gap-4 border-b border-border pb-5 md:grid-cols-2" aria-labelledby="branding-{{ array_key_first($slots) }}">
                <div class="min-w-0 space-y-4">
                    <h2 class="ui-section-title" id="branding-{{ array_key_first($slots) }}">{{ $heading }}</h2>
                    @foreach($slots as $slot => $label)
                        <div class="min-w-0" @media-selected="drafts['{{ $slot }}'] = $event.detail"
                            @media-cleared="drafts['{{ $slot }}'] = {id: '', preview: '', filename: ''}">
                            <x-ui.media-picker :name="$slot" :$label :value="$drafts[$slot]['id']" :preview-url="$drafts[$slot]['preview']" replace />
                            <p class="ui-help break-words" x-text="drafts['{{ $slot }}'].filename || (drafts['{{ $slot }}'].id ? 'Preview unavailable' : 'No image selected')"></p>
                            @error($slot)<x-ui.form-error :message="$message" />@enderror
                        </div>
                    @endforeach
                </div>
                <div class="min-w-0 space-y-2">
                    @if(isset($slots['logo']))
                        @foreach(['Light header', 'Dark header'] as $heading)
                            @php($dark = $heading === 'Dark header')
                            <p class="ui-help">{{ $heading }}</p>
                            <div @class(['flex min-h-14 items-center gap-2 rounded-ui border border-border px-3', 'bg-white text-zinc-900' => !$dark, 'bg-zinc-900 text-zinc-50' => $dark])>
                                @php($source = $dark ? "drafts['dark-logo'].preview || drafts.logo.preview" : 'drafts.logo.preview')
                                <img x-cloak x-show="{{ $source }}" :src="({{ $source }}) || null" alt="" class="size-9 object-contain">
                                <span x-show="!({{ $source }})"><x-ui.icon name="mail" /></span>
                                <span class="text-sm font-semibold">{{ config('app.name') }}</span>
                            </div>
                        @endforeach
                        <p class="ui-help" x-cloak x-show="!drafts['dark-logo'].preview && drafts.logo.preview">Using the primary logo as fallback.</p>
                    @elseif(isset($slots['favicon']))
                        <p class="ui-help">Browser tab preview · PNG at 16 px</p>
                        <div class="flex items-center gap-2 rounded-ui border border-border bg-muted px-3 py-2 text-sm">
                            <img x-cloak x-show="drafts.favicon.preview" :src="drafts.favicon.preview || null" alt="" class="size-4 object-contain">
                            <span x-show="!drafts.favicon.preview"><x-ui.icon name="mail" :size="16" /></span>
                            {{ config('app.name') }}
                        </div>
                    @else
                        <p class="ui-help">Sharing preview · Cropping may vary by platform</p>
                        <div class="overflow-hidden rounded-ui border border-border bg-surface">
                            <div class="aspect-[1.91/1] bg-muted">
                                <img x-cloak x-show="drafts.social.preview" :src="drafts.social.preview || null" alt="Draft social sharing image" class="size-full object-cover">
                            </div>
                            <div class="p-3"><p class="font-medium">{{ config('app.name') }}</p><p class="ui-help break-all">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</p></div>
                        </div>
                    @endif
                </div>
            </section>
        @endforeach
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button type="submit" busy-on-submit loading-label="Saving branding…">Save branding</x-ui.button>
            <p class="ui-help" role="status" x-text="dirty ? 'Unsaved changes' : 'Saved branding'"></p>
        </div>
    </form>
</x-layouts.workspace>
