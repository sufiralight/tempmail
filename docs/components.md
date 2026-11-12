# Blade components

The implemented anonymous component is [x-theme-selector](../resources/views/components/theme-selector.blade.php). It accepts wrapper attributes including class, uses a labeled native select, and requires the Alpine theme store. The welcome view displays it when Vite assets are available.

## Planned catalog

The entries below are design contracts for Prompt 03. Confirm the exact public API when implementing and record defaults, slots, forwarded attributes, variants, and accessibility behavior here.

| Proposed components | API and accessibility contract |
| --- | --- |
| x-ui.button | Default label slot, type, variant and disabled state. Preserve link semantics for navigation. Name icon-only buttons. |
| x-ui.input, x-ui.select, x-ui.textarea, x-ui.password-field | Accept field name and ID, native attributes, label and help or error references. Associate feedback and announce password visibility state. |
| x-ui.checkbox, x-ui.radio-group, x-ui.label, x-ui.validation-message | Associate controls and labels. Group choices with fieldset and legend and connect messages by ID. |
| x-ui.card, x-ui.badge, x-ui.alert, x-ui.toast | Use slots for content and named semantic variants. Announce dynamic status when appropriate and provide a named dismiss action. |
| x-ui.dropdown, x-ui.modal, x-ui.theme-switcher | Define trigger and content contracts. Document initial focus, dismissal, selection and focus return. |
| x-ui.table, x-ui.pagination, x-ui.navigation | Preserve headers, captions, landmarks and named links. Identify the current page and support narrow screens. |
| x-ui.empty-state, x-ui.loading-indicator, x-ui.file-upload | Provide contextual content, visible status and labeled upload constraints. Surface rejected files with actionable feedback. |

Search existing templates before adding shared markup and follow the [component authoring rules](../agents/blade-components.md).
