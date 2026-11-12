# Blade components

The implemented catalog lives in [resources/views/components/ui](../resources/views/components/ui). Pass trusted URLs to link and image props. Blade escapes string props. Supply slots as Blade markup and use native attributes for form behavior.

| Component | API and accessibility contract |
| --- | --- |
| button | variant primary, secondary, outline, ghost or destructive. size sm, md (default) or lg. type defaults to button. href renders a link unless disabled or loading. icon is optional. Give icon-only controls an aria-label. Opt into native form submission protection with busy-on-submit on a submit button and supply loading-label for action-specific loading text. |
| icon | name, numeric size (default 20), optional label. Decorative by default. A supplied label makes the SVG a named image. |
| input, textarea, select | id, name, label, help, error, required, leading-icon, trailing-icon and native attributes such as disabled and autocomplete. Inputs accept type and value, textarea accepts value or a slot, and select takes option markup. Assign a label or an accessible name. Help and validation IDs are included in aria-describedby. |
| password-field | Shares the input API and defaults autocomplete to current-password. Override with new-password for creation and reset. A pressed-state toggle preserves the value and leaves keyboard focus on the toggle. The component omits the value attribute. |
| label, form-field, form-error | label accepts for and required. form-field groups a slot with id, label, help and error. When composing custom fields, connect help and error with the field ID plus -help and -error. form-error accepts message or a slot. |
| checkbox, radio | Native name, checked, required, disabled and aria attributes pass through. Supply label or a slot and set value for radio. Group related options with fieldset and legend. |
| card | Optional title, description, heading (h2–h4), header and footer slots. Use a heading level that follows the page outline. |
| badge | variant muted (default), outline or solid. Optional icon and required text slot. |
| alert, toast | variant success, error, warning or info and optional title. Use announce for dynamic alerts. Toast has a named dismiss control and a live-region role. For asynchronous messages, populate a live region already present in the DOM. |
| dropdown | label, optional icon, align start or end, optional icon-only and trigger slot. Supply buttons or links with role menuitem or menuitemradio, tabindex -1 and class ui-menu-item. Use native disabled buttons. Arrow keys, Home, End and Escape are managed centrally. |
| modal | title, optional description, dismissible (true), trigger and footer slots. Trigger slot takes a button. Use autofocus for the intended initial control, generally Cancel for destructive confirmations. Close through a method=dialog form or the dismiss control. |
| table, pagination | Table requires caption and a head slot with scoped headers. Body rows use the default slot. The wrapper can be scrolled using the keyboard. Pagination accepts a Laravel paginator and renders previous and next links when needed. |
| tabs | tabs is an associative map of slot key to label. Supply a matching named slot for each key, a label, and optionally selected. ArrowLeft, ArrowRight, Home and End activate and focus tabs. |
| empty-state | title, optional description, icon (inbox by default), and optional action slot. |
| skeleton, spinner | Skeleton dimensions come from classes. Supply label when it represents a standalone status. Spinner accepts size and label. Decorative loaders should be paired with visible or announced status when appropriate. |
| avatar | name, optional src, size sm/md/lg, optional decorative. Falls back to initials. |
| tooltip | text and unique id. Add aria-describedby pointing to that id on the focusable trigger in the slot. Keep essential instructions visible. |
| file-upload | Shares form control props, including accept and multiple. Optional progress is clamped to 0–100 and progress-label names the indicator. The default slot can render feature-managed status. |
| image-preview | src, alt, thumbnail (false), and optional caption slot for metadata. Use the modal component for preview or delete confirmation. |
| logo | name defaults to Temp Mail. Optional href and src allow navigation and a future managed image. The wordmark names the brand. |
| theme-switcher | Uses the app layout's theme bootstrap. An icon trigger opens a radio menu with three preferences. See [Design system](design-system.md). |

## Layout

Use [x-layouts.app](../resources/views/components/layouts/app.blade.php) with mode installer, auth, dashboard, administration or public. It provides a skip link, brand header, theme control, main container, and optional navigation, breadcrumbs and footer slots. Use heading and description for standard page headers. Navigation wraps at narrow widths. The local-only /_ui route previews the components.

Check existing components before adding a pattern. Extend native slots and props with an accessibility contract and test the normal entry point. See [agent rules](../agents/blade-components.md).
