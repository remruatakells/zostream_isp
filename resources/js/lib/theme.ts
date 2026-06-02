export type Theme = "light" | "dark";

const STORAGE_KEY = "zostream-theme";

export function isTheme(value: string | null): value is Theme {
    return value === "light" || value === "dark";
}

export function getSystemTheme(): Theme {
    if (typeof window === "undefined") {
        return "light";
    }

    return window.matchMedia("(prefers-color-scheme: dark)").matches
        ? "dark"
        : "light";
}

export function getStoredTheme(): Theme | null {
    if (typeof window === "undefined") {
        return null;
    }

    try {
        const storedTheme = window.localStorage.getItem(STORAGE_KEY);

        return isTheme(storedTheme) ? storedTheme : null;
    } catch {
        return null;
    }
}

export function getInitialTheme(): Theme {
    return getStoredTheme() ?? getSystemTheme();
}

export function applyTheme(theme: Theme) {
    document.documentElement.dataset.theme = theme;
    document.documentElement.classList.toggle("dark", theme === "dark");
}

export function toggleThemeValue(theme: Theme): Theme {
    return theme === "dark" ? "light" : "dark";
}

export function persistTheme(theme: Theme) {
    try {
        window.localStorage.setItem(STORAGE_KEY, theme);
    } catch {
        // Ignore storage failures; the visible theme has already changed.
    }
}
