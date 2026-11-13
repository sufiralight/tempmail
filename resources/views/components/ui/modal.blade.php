@props(['title', 'description' => null, 'dismissible' => true, 'drawer' => false])

<div x-data="uiDialog" x-id="['dialog-title', 'dialog-description']" {{ $attributes }}>
    <div @click="show($event.target.closest('button') ?? document.activeElement)">{{ $trigger }}</div>
    <dialog x-ref="dialog" @class(['ui-dialog', 'ui-dialog-drawer' => $drawer]) :aria-labelledby="$id('dialog-title')"
        @if($description) :aria-describedby="$id('dialog-description')" @endif
        @close="finish()" @keydown.tab="trap($event)"
        @if($drawer && $dismissible)
            @click.self="if ($event.clientX > $el.getBoundingClientRect().right) $el.close()"
        @endif
        @unless($dismissible) @cancel.prevent @endunless>
        <header class="flex items-start justify-between gap-4">
            <div>
                <h2 :id="$id('dialog-title')" class="ui-section-title">{{ $title }}</h2>
                @if ($description)<p :id="$id('dialog-description')" class="ui-help mt-2">{{ $description }}</p>@endif
            </div>
            @if ($dismissible)
                <x-ui.button variant="ghost" size="sm" icon="x" aria-label="Close dialog" @click="$refs.dialog.close()" />
            @endif
        </header>
        <div class="mt-4">{{ $slot }}</div>
        @isset($footer)<footer class="mt-6 flex flex-wrap justify-end gap-2">{{ $footer }}</footer>@endisset
    </dialog>
</div>
