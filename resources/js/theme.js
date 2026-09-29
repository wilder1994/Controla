const KEY = 'controla-theme';

export function currentTheme() {
    return document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
}

export function setTheme(theme) {
    const next = theme === 'light' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    try {
        localStorage.setItem(KEY, next);
    } catch {
        // ignore
    }
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) {
        meta.setAttribute('content', next === 'light' ? '#eaf4f1' : '#020617');
    }
}

export function toggleTheme() {
    setTheme(currentTheme() === 'light' ? 'dark' : 'light');
}

window.ControlaTheme = { current: currentTheme, set: setTheme, toggle: toggleTheme };
