@props(['tabs', 'label', 'selected' => null])
@php($selected = array_key_exists($selected ?? '', $tabs) ? $selected : array_key_first($tabs))
<div x-data="uiTabs(@js($selected))" x-id="['tab', 'panel']" {{ $attributes }}>
    <div class="ui-tab-list" role="tablist" aria-label="{{ $label }}" @keydown="navigate($event)">
        @foreach ($tabs as $key => $text)
            <button type="button" class="ui-tab" role="tab" :id="$id('tab', @js($key))"
                :aria-controls="$id('panel', @js($key))" :aria-selected="selected === @js($key)"
                :tabindex="selected === @js($key) ? 0 : -1" @click="activate(@js($key))">{{ $text }}</button>
        @endforeach
    </div>
    @foreach ($tabs as $key => $text)
        <section role="tabpanel" class="pt-4" tabindex="0" :id="$id('panel', @js($key))"
            :aria-labelledby="$id('tab', @js($key))" x-show="selected === @js($key)" @if($key !== $selected) x-cloak @endif>
            {{ $$key ?? '' }}
        </section>
    @endforeach
</div>
