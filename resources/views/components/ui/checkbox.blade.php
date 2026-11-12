@props(['label' => null, 'value' => '1'])
<label class="ui-choice">
    <input type="checkbox" value="{{ $value }}" {{ $attributes->class('ui-choice-input') }}>
    <span>{{ $label ?? $slot }}</span>
</label>
