@if ($errors->any())
    <x-ui.alert class="mb-4" variant="error" title="Check the form" tabindex="-1" x-data x-init="$el.focus()" announce>
        Review the highlighted fields and try again.
        <x-ui.form-error :message="$errors->first('role')" />
        <x-ui.form-error :message="$errors->first('is_super_admin')" />
        <x-ui.form-error :message="$errors->first('token')" />
    </x-ui.alert>
@endif
