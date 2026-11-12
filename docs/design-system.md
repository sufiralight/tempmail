# Design system

Pure Zinc Monochrome Premium SaaS uses these semantic tokens from [app.css](../resources/css/app.css).

| Token | Light | Dark |
| --- | --- | --- |
| page | Zinc 50 | Zinc 900 |
| surface | White | Zinc 800 |
| foreground | Zinc 900 | Zinc 50 |
| border | Zinc 200 | Zinc 700 |
| primary | Zinc 900 | Zinc 50 |
| on-primary | Zinc 50 | Zinc 900 |

Use self-hosted Geist Sans for interface text, Geist Mono for code and technical values, and Lucide SVG icons. Use a consistent spacing rhythm, quiet borders, modest elevation, and a responsive typography hierarchy. Use copy and shape to communicate state and avoid decorative gradients, colored accents, and heavy glass effects.

## Themes

The [Alpine store](../resources/js/app.js) reads and writes tempmail.theme in localStorage. Explicit Light or Dark sets the root data-theme attribute. System removes the attribute so CSS follows prefers-color-scheme, including changes while the page is open. Unavailable storage falls back to the available in-page and system behavior.

The current control is a native select and applies a stored override when the Vite module loads. Prompt 03 should use one accessible icon trigger and a vertical Light, Dark, System menu with a visible selected state. Apply the validated stored preference before first paint and verify cold loads with an override opposite the OS theme. Follow [Components](components.md) and [Accessibility](accessibility.md).
