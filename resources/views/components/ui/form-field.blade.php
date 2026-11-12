@props(['id', 'label' => null, 'help' => null, 'error' => null, 'required' => false])
<div {{ $attributes->class('ui-field') }}>
    @if ($label)<x-ui.label :for="$id" :required="$required">{{ $label }}</x-ui.label>@endif
    {{ $slot }}
    @if ($help)<p id="{{ $id }}-help" class="ui-help">{{ $help }}</p>@endif
    <x-ui.form-error :id="$id.'-error'" :message="$error" />
</div>
