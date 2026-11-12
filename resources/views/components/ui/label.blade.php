@props(['for' => null, 'required' => false])
<label {{ $attributes->class('ui-label')->merge(['for' => $for]) }}>{{ $slot }} @if($required)<span aria-hidden="true">*</span>@endif</label>
