# Dark Mode Implementation - Task Tracker

Requirements

- [x] Global toggle class: body.dark-mode (and body.light-mode for user-forced light)
- [x] Save user preference via localStorage (no backend changes)
- [x] Default follow system (prefers-color-scheme) guarded by body:not(.light-mode):not(.dark-mode)
- [x] Support components: background, text, table, form input, button, navbar & sidebar
- [x] Clean, modern, non-neon palette

Files Added

- [x] assets/dark-mode.css
- [x] assets/dark-mode.js

Files Updated

- [x] views/layout/header.php (include dark-mode.css + dark-mode.js)

CSS Scope (assets/dark-mode.css)

- [x] Variables (bg, card, text, border, accent)
- [x] body.dark-mode: background, text color, smoothing
- [x] Navbar & Sidebar overrides (including .navbar.bg-white)
- [x] Content, headers, subtitles
- [x] Customers toolbar & submenu states (default/hover/active)
- [x] Form inputs (.form-control, .form-select, .input-group-text, focus)
- [x] Buttons (.btn, .btn-primary, .btn-outline-secondary)
- [x] Tables (.table, thead, striped, hover, borders)
- [x] Cards (.card, .dashboard-card)
- [x] Scrollbar for content-body
- [x] System default via @media (prefers-color-scheme: dark) with guard body:not(.light-mode):not(.dark-mode)

JS Scope (assets/dark-mode.js)

- [x] Read/save preference under localStorage key "theme"
- [x] Apply theme: add/remove body.dark-mode / body.light-mode
- [x] System sync when no user preference (listen to mql change)
- [x] Toggle button injection into .navbar with Bootstrap Icons (moon/sun)
- [x] Fallback to floating button if .navbar not found
- [x] Accessible labels (aria-label, title)

Manual Toggle (Optional)
If you prefer a manual button in the navbar (instead of JS-injected), you can add this snippet into views/layout/navbar.php within the existing navbar container:
<button type="button" class="btn btn-outline-secondary dark-toggle d-flex align-items-center gap-2">
<i class="bi bi-moon-stars"></i><span class="d-none d-md-inline">Theme</span>
</button>

Behavior

- On first visit: follows OS theme (prefers-color-scheme) unless user has a saved preference.
- User toggle: immediately switches theme and persists choice in localStorage.
- Guard: prefers-color-scheme only applies if body does not have .light-mode or .dark-mode.

Validation Checklist

- [x] Navbar background and text readable in dark mode
- [x] Sidebar states (default/hover/active) readable and consistent
- [x] Tables readable with subtle striping and hover
- [x] Forms contrast adequate; focus ring uses accent
- [x] Buttons respect Bootstrap styling, no neon
- [x] No structural HTML changes beyond adding link/script includes
