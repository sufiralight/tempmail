@props(['action', 'single' => false])
<form method="post" action="{{ $action }}" class="grid gap-4">
    @csrf
    @if($single) @method('DELETE') @endif
    <x-ui.password-field name="current_password" label="Current password" required />
    <x-ui.button type="submit" variant="destructive" busy-on-submit>Confirm sign out</x-ui.button>
</form>
