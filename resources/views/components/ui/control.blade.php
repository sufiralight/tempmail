@props([
    'as' => 'input', 'type' => 'text', 'name' => null, 'id' => null, 'label' => null, 'help' => null,
    'error' => null, 'required' => false, 'leadingIcon' => null, 'trailingIcon' => null, 'value' => null,
])

@php
    $id ??= 'field-'.Illuminate\Support\Str::random(12);
    $error ??= $name === null ? null : ($errors ?? null)?->first($name);
    $describedBy = implode(' ', array_filter([$attributes->get('aria-describedby'), $help ? $id.'-help' : null, $error ? $id.'-error' : null]));
    $control = $attributes->except('aria-describedby')->class([
        'ui-control', 'ui-control-leading' => $leadingIcon, 'ui-control-trailing' => $trailingIcon || $type === 'password',
    ])->merge([
        'id' => $id, 'name' => $name, 'required' => $required,
        'aria-describedby' => $describedBy ?: null, 'aria-invalid' => $error ? 'true' : null,
    ]);
@endphp

<x-ui.form-field :$id :$label :$help :$error :$required>
    <div class="relative" @if($type === 'password') x-data="{ visible: false }" @endif>
        @if ($leadingIcon)<x-ui.icon :name="$leadingIcon" :size="16" class="ui-control-icon start-3" />@endif
        @if ($as === 'textarea')
            <textarea {{ $control }}>{{ $value ?? $slot }}</textarea>
        @elseif ($as === 'select')
            <select {{ $control }}>{{ $slot }}</select>
        @else
            <input type="{{ $type }}" @if($type === 'password') :type="visible ? 'text' : 'password'" @endif
                {{ $control->merge(['value' => in_array($type, ['password', 'file'], true) ? null : $value]) }}>
        @endif
        @if ($type === 'password')
            <button type="button" x-cloak class="ui-password-toggle" aria-label="Show password" :aria-pressed="visible"
                @disabled($attributes->get('disabled')) @click="visible = !visible">
                <x-ui.icon name="eye" :size="18" x-show="!visible" />
                <x-ui.icon name="eye-off" :size="18" x-show="visible" />
            </button>
        @elseif ($trailingIcon)
            <x-ui.icon :name="$trailingIcon" :size="16" class="ui-control-icon end-3" />
        @endif
    </div>
</x-ui.form-field>
