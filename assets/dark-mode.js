(function () {
  const STORAGE_KEY = 'theme';
  const mql = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)');

  function getStoredPreference() {
    try {
      const v = localStorage.getItem(STORAGE_KEY);
      if (v === 'dark' || v === 'light') return v;
    } catch (_) {}
    return null;
  }

  function setStoredPreference(theme) {
    try {
      localStorage.setItem(STORAGE_KEY, theme);
    } catch (_) {}
  }

  function applyTheme(theme, persist) {
    const b = document.body;
    if (!b) return;

    b.classList.remove('dark-mode', 'light-mode');
    if (theme === 'dark') {
      b.classList.add('dark-mode');
    } else if (theme === 'light') {
      b.classList.add('light-mode');
    }

    if (persist) setStoredPreference(theme);
    updateToggleIcon(theme);
  }

  function currentTheme() {
    const b = document.body;
    if (!b) return 'light';
    if (b.classList.contains('dark-mode')) return 'dark';
    if (b.classList.contains('light-mode')) return 'light';
    // fall back to system
    return (mql && mql.matches) ? 'dark' : 'light';
  }

  function updateToggleIcon(theme) {
    const btn = document.querySelector('.dark-toggle');
    const icon = btn ? btn.querySelector('i') : null;
    if (!btn || !icon) return;

    if (theme === 'dark') {
      icon.className = 'bi bi-sun';
      btn.setAttribute('aria-label', 'Switch to light mode');
      btn.title = 'Light mode';
    } else {
      icon.className = 'bi bi-moon-stars';
      btn.setAttribute('aria-label', 'Switch to dark mode');
      btn.title = 'Dark mode';
    }
  }

  function injectToggle() {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-outline-secondary dark-toggle d-flex align-items-center gap-2';
    btn.innerHTML = '<i class="bi"></i><span class="d-none d-md-inline">Theme</span>';
    btn.addEventListener('click', function () {
      const next = currentTheme() === 'dark' ? 'light' : 'dark';
      applyTheme(next, true);
    });

    const navbar = document.querySelector('.navbar');
    if (navbar) {
      // Place toggle near logout at right side without changing HTML structure
      btn.classList.add('ms-auto');
      const logout = navbar.querySelector('a.btn');
      if (logout) {
        logout.classList.add('ms-2');
        navbar.insertBefore(btn, logout);
      } else {
        navbar.appendChild(btn);
      }
    } else {
      // Fallback if navbar not found -> floating button
      btn.classList.add('dark-toggle-floating');
      document.body.appendChild(btn);
    }

    // Sync icon with current theme
    updateToggleIcon(currentTheme());
  }

  // Initialize after DOM is ready (for safe DOM access)
  document.addEventListener('DOMContentLoaded', function () {
    // 1) Determine startup theme
    const stored = getStoredPreference();
    if (stored) {
      applyTheme(stored, false);
    } else {
      // follow system by default; do not persist
      const systemTheme = (mql && mql.matches) ? 'dark' : 'light';
      applyTheme(systemTheme, false);

      // keep syncing with system when no user preference set
      if (mql && mql.addEventListener) {
        mql.addEventListener('change', function (e) {
          if (getStoredPreference()) return; // user overrode later
          applyTheme(e.matches ? 'dark' : 'light', false);
        });
      } else if (mql && mql.addListener) {
        // Safari/old browsers
        mql.addListener(function (e) {
          if (getStoredPreference()) return;
          applyTheme(e.matches ? 'dark' : 'light', false);
        });
      }
    }

    // 2) Inject toggle control
    injectToggle();
  });
})();
