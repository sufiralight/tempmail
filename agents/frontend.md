# Frontend

Render pages and forms in Blade. Use native navigation and form submission where possible. Add Alpine for small interactive behaviors with explicit keyboard and focus handling. Preserve readable content and usable core flows if JavaScript fails. Use semantic HTML, mobile-first layouts, and test reflow before widening containers.

Use the existing Laravel Vite plugin, Tailwind CSS 4 CSS-first config, and app CSS and JS entry points. Keep Tailwind class names discoverable as complete strings. Add explicit source coverage only when placing templates outside the current globs. Compile assets locally or in CI. Livewire, React, Vue, and Inertia are outside the selected stack.

Use x-layouts.app for shared page structure and load its theme bootstrap before Vite styles. Follow [component reuse](blade-components.md), [visual rules](design-system.md), and [accessibility](accessibility.md).

Use the global page progress controller for opt-in async work. Keep upload progress tied to measurable bytes and preserve native navigation.
