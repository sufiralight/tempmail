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
