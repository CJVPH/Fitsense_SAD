/**
 * FitSense — Theme Manager (dark / light)
 *
 * - Reads saved theme from localStorage on load (instant, no flash)
 * - Applies 'light-mode' class to <html>
 * - Syncs preference to DB via API when toggled
 * - Works for all roles: member, trainer, admin
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'fitsense_theme';

    // ── Apply theme immediately (called before DOM ready to prevent flash) ──
    function applyTheme(theme) {
        if (theme === 'light') {
            document.documentElement.classList.add('light-mode');
        } else {
            document.documentElement.classList.remove('light-mode');
        }
    }

    // Apply saved theme right away
    var saved = localStorage.getItem(STORAGE_KEY) || 'dark';
    applyTheme(saved);

    // ── Sync to server ────────────────────────────────────────────────────────
    function syncTheme(theme) {
        var csrf = (typeof FITSENSE_CSRF !== 'undefined') ? FITSENSE_CSRF : '';
        if (!csrf) return;
        fetch('api/members.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'save_theme', theme: theme, csrf_token: csrf })
        }).catch(function () {}); // silent fail — localStorage is the source of truth
    }

    // ── Toggle ────────────────────────────────────────────────────────────────
    function toggleTheme() {
        var current = localStorage.getItem(STORAGE_KEY) || 'dark';
        var next    = current === 'dark' ? 'light' : 'dark';
        localStorage.setItem(STORAGE_KEY, next);
        applyTheme(next);
        syncTheme(next);
        updateToggleUI(next);
    }

    // ── Update button icon/label ──────────────────────────────────────────────
    function updateToggleUI(theme) {
        var btns = document.querySelectorAll('.theme-toggle-btn');
        btns.forEach(function (btn) {
            var iconDark  = btn.querySelector('.icon-dark');
            var iconLight = btn.querySelector('.icon-light');
            var label     = btn.querySelector('.theme-label');
            if (iconDark)  iconDark.classList.toggle('hidden',  theme === 'light');
            if (iconLight) iconLight.classList.toggle('hidden',  theme === 'dark');
            if (label)     label.textContent = theme === 'dark' ? 'Light Mode' : 'Dark Mode';
        });
    }

    // ── Init after DOM ready ──────────────────────────────────────────────────
    function init() {
        var theme = localStorage.getItem(STORAGE_KEY) || 'dark';
        updateToggleUI(theme);

        document.querySelectorAll('.theme-toggle-btn').forEach(function (btn) {
            btn.addEventListener('click', toggleTheme);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose for inline use if needed
    window.FitSenseTheme = { toggle: toggleTheme, apply: applyTheme };
})();
