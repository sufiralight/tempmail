# Design system

Pure Zinc Monochrome Premium SaaS uses [app.css](../resources/css/app.css) as the token source.

| Token | Light | Dark |
| --- | --- | --- |
| page | Zinc 50 | Zinc 900 |
| surface | White | Zinc 800 |
| foreground | Zinc 900 | Zinc 50 |
| secondary-text | Zinc 500 | Zinc 400 |
| border | Zinc 200 | Zinc 700 |
| muted | Zinc 100 | Zinc 800 |
| primary | Zinc 900 | Zinc 50 |
| on-primary | White | Zinc 900 |
| focus | Zinc 600 | Zinc 300 |
| control-border | Zinc 500 | Zinc 400 |

Use panel borders for decoration and control-border for actionable boundaries. Use radius-ui (8 px) for controls, radius-panel (12 px) for cards, a 44 px default control height, a 20 px base page gutter, and panel or overlay shadows. Interaction transitions use the shared 150 ms duration and switch to zero when reduced motion is requested.

Use locally bundled Geist Sans for interface text and Geist Mono for addresses, code and technical metadata. Use the page, section and card title classes to maintain heading scale. Body uses base text, labels and help use small text, and captions and badges use the smallest scale. Apply generous spacing through shared components and Tailwind spacing utilities.

## Icons

Use x-ui.icon with the kebab-case symbol names in the [local sprite](../public/icons/lucide.svg). The sprite contains selected unmodified Lucide geometry from lucide-static 1.72.0, under its [license](../resources/icons/lucide/LICENSE). Update symbols from upstream SVG sources and retain the license. The icon component uses a 24-unit view box and a two-unit stroke. Give interactive icon-only controls an accessible name on the control. No runtime CDN is needed.

## Themes

The [head bootstrap](../resources/views/components/layouts/theme-init.blade.php) reads the saved tempmail.theme preference and applies a valid explicit override before styles load. Alpine uses that same state. The preference is Light, Dark or System, while the effective theme reflects the explicit override or the OS setting. In System mode, CSS follows prefers-color-scheme and the store reflects OS changes when matchMedia supports change events. Unavailable storage preserves in-page selection and unavailable matchMedia falls back to light for the store.

The theme control displays only the preference icon and opens a vertical radio menu with icons, labels and a selected checkmark. Check cold reloads with a saved override opposite the OS, and verify navigation in both themes. See [Components](components.md) and [Accessibility](accessibility.md).

## Page navigation progress

The global layout mounts x-ui.page-progress once. The controller observes same-origin full-page navigation with the Navigation API and falls back to ordinary link and form events. It never intercepts navigation. A short-lived timestamp in sessionStorage allows the destination layout to finish the animation. Immediate navigation may leave no time for the outgoing bar to paint. Browser chrome navigation, cancellations, and failures may be invisible to fallback listeners. A timeout and page lifecycle cleanup prevent stale progress. With blocked storage, the outgoing animation still works when the browser can paint it.

For asynchronous operations, pass the operation promise to window.tempMailPageProgress.track(promise) and await the returned promise. Pass a promise that settles when the tracked work finishes and use an AbortSignal or request timeout where appropriate. Overlapping operations share one bar. HTTP error responses and rejected promises clear the indicator without a completion animation. Use data-page-progress="off" to exclude actions such as downloads and uploads. For actual upload progress use a determinate control. Reduced motion shows a static short line during tracked work.

## Product patterns

Use Vite as a reference for typography, responsive identity and fast feedback. Use Vercel’s hierarchy as a reference for the 256 px sidebar, compact header and constrained content. Use Linear’s focus discipline for keyboard controls and contextual actions. Use Stripe’s form clarity for labels, inline errors and specific progress copy. Use GitHub’s collection patterns for server-side pagination, persistent query parameters and current-page selection.

Place the primary action near the page heading or the relevant form. Use a descriptive pending label for writes and confirm success only after the server completes them. Reuse shared dialogs with Escape, contained focus and focus return. Use shared alerts for errors and success, and distinguish empty collections from empty filtered results. Keep secondary filters collapsible on narrow screens. Display reversible Trash actions separately from explicitly confirmed permanent deletion.

Future implementation prompts should link to this guide and the [component catalog](components.md), identify existing entry points, and specify isolated regression checks.

See [Localization](localization.md) for the language registry, public locale rules, integration contracts, and release gates.
