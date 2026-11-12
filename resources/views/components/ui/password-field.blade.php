<x-ui.control type="password" {{ $attributes->except(['as', 'type', 'value'])->merge(['autocomplete' => 'current-password']) }} />
