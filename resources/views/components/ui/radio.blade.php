@props(['label' => null, 'value'])
<label class="ui-choice">
    <input type="radio" value="{{ $value }}" {{ $attributes->class('ui-choice-input') }}>
    <span>{{ $label ?? $slot }}</span>
</label>
