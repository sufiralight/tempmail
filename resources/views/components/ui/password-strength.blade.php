@props(['passwordId', 'confirmationId'])

@php
    $policy = [
        'minimum' => \App\Rules\AccountPassword::MINIMUM,
        'maximum' => \App\Rules\AccountPassword::MAXIMUM,
        'maximumBytes' => \App\Rules\AccountPassword::usesBcrypt() ? \App\Rules\AccountPassword::BCRYPT_BYTES : null,
        'common' => \App\Rules\AccountPassword::COMMON,
    ];
@endphp

<div {{ $attributes->class('ui-help grid gap-2') }} x-data="uiPasswordStrength(@js($passwordId), @js($confirmationId), @js($policy))" x-cloak>
    <div class="ui-password-strength" aria-hidden="true">
        @for ($segment = 1; $segment <= 4; $segment++)
            <span :data-filled="score >= {{ $segment }}"></span>
        @endfor
    </div>
    <p role="status" aria-atomic="true">
        <span x-text="level ? 'Estimated strength: ' + level + '.' : 'Estimated strength will appear as you type.'"></span>
        <span x-text="match"></span>
    </p>
    <ul class="grid gap-1" aria-label="Password requirements">
        <li><span aria-hidden="true" x-text="longEnough ? '✓' : '○'"></span>
            At least {{ $policy['minimum'] }} characters <span class="sr-only" x-text="longEnough ? 'met' : 'not met'"></span></li>
        <li><span aria-hidden="true" x-text="withinLimit && score ? '✓' : '○'"></span>
            Within the {{ $policy['maximumBytes'] ?? $policy['maximum'] }}-{{ $policy['maximumBytes'] ? 'byte' : 'character' }} limit
            <span class="sr-only" x-text="withinLimit && score ? 'met' : 'not met'"></span></li>
        <li><span aria-hidden="true" x-text="uncommon ? '✓' : '○'"></span>
            Avoid common passwords and simple repeats <span class="sr-only" x-text="uncommon ? 'met' : 'not met'"></span></li>
    </ul>
</div>
