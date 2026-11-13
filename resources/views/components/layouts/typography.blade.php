@props(['fonts', 'stylesheet' => null, 'preview' => false])

@if($stylesheet)
    <link rel="stylesheet" href="{{ $stylesheet }}" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="{{ $stylesheet }}"></noscript>
@endif
<style>
    {{ $preview ? '.font-preview' : ':root' }} {
        @foreach($fonts as $role => $font)
            --font-{{ $role }}: @if($font['family']) '{{ $font['family'] }}', @endif 'Geist Variable', {{ $font['fallback'] }};
            --weight-{{ $role }}: {{ $font['weight'] }};
        @endforeach
    }
</style>
