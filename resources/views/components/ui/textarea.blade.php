<x-ui.control as="textarea" {{ $attributes->except('as')->merge(['rows' => 4]) }}>{{ $slot }}</x-ui.control>
