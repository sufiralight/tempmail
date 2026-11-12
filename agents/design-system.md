# Design system

Use Pure Zinc Monochrome Premium SaaS for shared UI. Build on the semantic token table in the [design system](../docs/design-system.md). Use Geist Sans, Geist Mono for code and technical values, and the shared Lucide component. Use soft borders, restrained elevation, and consistent spacing. Avoid colorful accents, decorative gradients, heavy glass effects, and near-black dark page surfaces.

Reuse the theme switcher and the single preference store. Keep explicit overrides available before first paint, tolerate unavailable storage, and follow OS preference changes in System mode. Check initial rendering and keyboard use in both themes.
