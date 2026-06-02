import React, { useEffect, useState } from "react";
import { createRoot, type Root } from "react-dom/client";
import { ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import { AllUserPage } from "./components/all-user-page";
import { AppShell } from "./components/app-shell";
import { DashboardPage, getPageTitle } from "./components/dashboard-page";
import { LoginPage } from "./components/login-page";
import { ThemeToggle } from "./components/ui/theme-toggle";
import {
    clearAdminSession,
    getStoredAdminSession,
    type AdminLoginResponse,
} from "./lib/admin-auth";
import {
    applyTheme,
    getInitialTheme,
    getStoredTheme,
    type Theme,
    persistTheme,
    toggleThemeValue,
} from "./lib/theme";
import {
    ALL_USER_ROUTE,
    DASHBOARD_ROUTE,
    getRouteTitle,
    isProtectedRoute,
    LOGIN_ROUTE,
    normalizeAppPath,
} from "./lib/routes";

function App() {
    const [resolvedTheme, setResolvedTheme] = useState<Theme>(getInitialTheme);
    const [authState, setAuthState] = useState<AdminLoginResponse | null>(
        getStoredAdminSession,
    );
    const [activePath, setActivePath] = useState(() =>
        normalizeAppPath(window.location.pathname),
    );

    useEffect(() => {
        const mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
        const initialTheme = getInitialTheme();

        setResolvedTheme(initialTheme);
        applyTheme(initialTheme);

        const handleChange = () => {
            if (getStoredTheme()) {
                return;
            }

            const nextTheme = mediaQuery.matches ? "dark" : "light";
            setResolvedTheme(nextTheme);
            applyTheme(nextTheme);
        };

        const handlePopState = () => {
            setActivePath(normalizeAppPath(window.location.pathname));
        };

        mediaQuery.addEventListener("change", handleChange);
        window.addEventListener("popstate", handlePopState);

        return () => {
            mediaQuery.removeEventListener("change", handleChange);
            window.removeEventListener("popstate", handlePopState);
        };
    }, []);

    const isDark = resolvedTheme === "dark";

    const overlayClassName = isDark
        ? "absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.08),transparent_30%),linear-gradient(135deg,transparent,rgba(255,255,255,0.04)_45%,transparent_80%)]"
        : "absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.25),transparent_35%),linear-gradient(135deg,transparent,rgba(255,255,255,0.08)_48%,transparent_80%)]";

    const toggleTheme = () => {
        const nextTheme = toggleThemeValue(resolvedTheme);

        setResolvedTheme(nextTheme);
        applyTheme(nextTheme);
        persistTheme(nextTheme);
    };

    const navigate = (path: string, mode: "push" | "replace" = "push") => {
        const nextPath = normalizeAppPath(path);

        if (window.location.pathname !== nextPath) {
            if (mode === "replace") {
                window.history.replaceState({}, "", nextPath);
            } else {
                window.history.pushState({}, "", nextPath);
            }
        }

        setActivePath(nextPath);
    };

    const toastContainer = (
        <ToastContainer
            position="top-right"
            autoClose={3000}
            hideProgressBar={false}
            newestOnTop
            closeOnClick
            pauseOnHover
            draggable
            theme={resolvedTheme}
        />
    );

    useEffect(() => {
        if (authState && activePath === LOGIN_ROUTE) {
            navigate(DASHBOARD_ROUTE, "replace");
            return;
        }

        if (!authState && isProtectedRoute(activePath)) {
            navigate(LOGIN_ROUTE, "replace");
        }
    }, [activePath, authState]);

    const renderProtectedPage = () => {
        if (!authState) {
            return null;
        }

        if (activePath === ALL_USER_ROUTE) {
            return <AllUserPage />;
        }

        return <DashboardPage activePath={activePath} session={authState} />;
    };

    if (authState) {
        return (
            <>
                <AppShell
                    activePath={activePath}
                    onLogout={() => {
                        clearAdminSession();
                        setAuthState(null);
                        navigate(LOGIN_ROUTE, "replace");
                    }}
                    onNavigate={navigate}
                    onToggleTheme={toggleTheme}
                    resolvedTheme={resolvedTheme}
                    session={authState}
                    title={getRouteTitle(activePath)}
                >
                    {renderProtectedPage()}
                </AppShell>
                {toastContainer}
            </>
        );
    }

    return (
        <>
            <main className="relative min-h-screen overflow-hidden bg-[var(--background)] text-[var(--foreground)]">
                <div className="pointer-events-none absolute inset-0">
                    <div className="absolute left-[-8rem] top-[-6rem] h-72 w-72 rounded-full bg-[var(--orb-strong)] blur-3xl" />
                    <div className="absolute bottom-[-8rem] right-[-5rem] h-80 w-80 rounded-full bg-[var(--orb-soft)] blur-3xl" />
                    <div className={overlayClassName} />
                </div>

                <div className="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 py-4 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <img
                                src="/zostream-wifi.png"
                                alt="Zostream Wifi"
                                className="h-9 w-auto rounded-lg object-contain sm:h-12 sm:rounded-xl"
                            />
                            <span className="text-sm font-semibold uppercase tracking-[0.28em] text-[var(--accent-strong)]">
                                Zostream Wifi
                            </span>
                        </div>
                        <ThemeToggle
                            resolvedTheme={resolvedTheme}
                            onToggle={toggleTheme}
                        />
                    </div>

                    <LoginPage
                        onLoginSuccess={(session) => {
                            setAuthState(session);
                            navigate(DASHBOARD_ROUTE, "replace");
                        }}
                    />
                </div>
            </main>
            {toastContainer}
        </>
    );
}

type RootContainer = HTMLElement & {
    __zostreamRoot?: Root;
};

const mountApp = () => {
    const existingContainer = document.getElementById("app");
    const container =
        existingContainer ??
        (() => {
            const fallbackContainer = document.createElement("div");
            fallbackContainer.id = "app";
            document.body.appendChild(fallbackContainer);
            return fallbackContainer;
        })();

    const rootContainer = container as RootContainer;
    const root = rootContainer.__zostreamRoot ?? createRoot(rootContainer);

    rootContainer.__zostreamRoot = root;

    root.render(
        <React.StrictMode>
            <App />
        </React.StrictMode>,
    );
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mountApp, { once: true });
} else {
    mountApp();
}
