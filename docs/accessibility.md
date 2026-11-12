# Accessibility

[WCAG 2.2 AA](https://www.w3.org/TR/WCAG22/) is the project target. Conformance requires verification across complete flows. Apply semantic landmarks and headings, accessible names, clear form instructions, visible and unobscured focus, and descriptive inline errors associated with fields. Use native controls before assigning ARIA roles.

## Manual verification

1. Navigate desktop and narrow layouts with Tab and Shift+Tab. Activate controls with Enter and Space as appropriate. Check focus order and visibility, including near sticky UI.
2. Open each dialog with the keyboard. Check its accessible name, initial focus, contained tab sequence, Escape dismissal where allowed, and focus return. Test dropdowns with their chosen disclosure or menu semantics. ARIA menus require appropriate item focus and arrow-key handling.
3. Check form labels, invalid state, described errors, and dynamic announcements with a screen reader. Verify password visibility controls announce state and preserve focus. Check paste and password-manager support.
4. Test at 200% text zoom and a 320 CSS pixel viewport for reflow. Check touch targets against the 24 by 24 CSS pixel AA minimum or an applicable exception.
5. Check both themes for at least 4.5:1 contrast for normal text, 3:1 for large text, and 3:1 for required non-text control indicators and graphical objects against adjacent colors. Verify reduced-motion settings and ensure color is supplemented with text or another cue.

Record the tested page, browser or assistive technology, theme, and outcome with the change. Use automated scans to supplement manual interaction checks. See [Testing](testing.md).
