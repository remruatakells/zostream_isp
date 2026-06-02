import { MoonStar, SunMedium } from "lucide-react";
import type { Theme } from "../../lib/theme";

type ThemeToggleProps = {
    resolvedTheme: Theme;
    onToggle: () => void;
};

export function ThemeToggle({ resolvedTheme, onToggle }: ThemeToggleProps) {
    const isDark = resolvedTheme === "dark";

    return (
        <button
            type="button"
            onClick={onToggle}
            className="inline-flex items-center gap-3 rounded-full border border-[var(--border-strong)] bg-[var(--panel-soft)] px-2 py-2 text-sm font-medium text-[var(--foreground)] shadow-[0_10px_30px_rgba(15,23,42,0.08)] backdrop-blur transition hover:-translate-y-0.5 hover:border-[var(--accent)]"
            aria-label={`Switch to ${isDark ? "light" : "dark"} mode`}
        >
            <span className="inline-flex h-6 w-6 items-center justify-center rounded-full bg-[var(--panel)] text-[var(--accent-strong)]">
                {isDark ? (
                    <MoonStar className="h-4 w-4" />
                ) : (
                    <SunMedium className="h-4 w-4" />
                )}
            </span>
        </button>
    );
}
