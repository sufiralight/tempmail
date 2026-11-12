# Design system

Use Pure Zinc Monochrome Premium SaaS for shared UI. Build on the semantic token table in the [design system](../docs/design-system.md). Use Geist Sans, Geist Mono for code and technical values, and Lucide SVG icons with consistent sizes and stroke. Use soft borders, restrained elevation, and consistent spacing. Avoid colorful accents, decorative gradients, heavy glass effects, and near-black dark page surfaces.

Prompt 03 should replace the current native selector with one named icon button opening a vertical Light, Dark, System menu. Reuse the existing preference and CSS system fallback. Apply a validated saved override before first paint, tolerate unavailable storage, and follow OS preference changes in System mode. Check initial rendering and keyboard use in both themes.
