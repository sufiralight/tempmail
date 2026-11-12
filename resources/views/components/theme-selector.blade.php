<label x-data x-cloak {{ $attributes->class(['flex items-center gap-2 text-sm']) }}>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect width="20" height="14" x="2" y="3" rx="2"/>
        <line x1="8" x2="16" y1="21" y2="21"/>
        <line x1="12" x2="12" y1="17" y2="21"/>
    </svg>
    Theme
    <select :value="$store.theme.preference" @change="$store.theme.set($event.target.value)" class="rounded-md border border-border bg-surface px-2 py-1">
        <option value="system">System</option>
        <option value="light">Light</option>
        <option value="dark">Dark</option>
    </select>
</label>
