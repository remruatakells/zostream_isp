import {
    ChevronLeft,
    ChevronRight,
    Download,
    LayoutDashboard,
    LogOut,
    Settings2,
    ShieldUser,
    UserCircle,
    Users,
} from "lucide-react";
import { useEffect, useState, type ReactNode } from "react";
import type { AdminLoginResponse } from "../lib/admin-auth";
import {
    ALL_USER_ROUTE,
    DASHBOARD_ROUTE,
    DOWNLOAD_ROUTE,
    MANAGEMENT_ROUTE,
    OPERATOR_ROUTE,
    PROFILE_ROUTE,
} from "../lib/routes";
import { ThemeToggle } from "./ui/theme-toggle";
import type { Theme } from "../lib/theme";

type AppShellProps = {
    activePath: string;
    children: ReactNode;
    onLogout: () => void;
    onNavigate: (path: string) => void;
    resolvedTheme: Theme;
    session: AdminLoginResponse;
    title: string;
    onToggleTheme: () => void;
};

type NavItem = {
    href: string;
    label: string;
    mobileLabel?: string;
    icon: typeof LayoutDashboard;
};

const navItems: NavItem[] = [
    {
        href: DASHBOARD_ROUTE,
        label: "Dashboard",
        icon: LayoutDashboard,
    },
    {
        href: ALL_USER_ROUTE,
        label: "All User",
        mobileLabel: "Subs",
        icon: Users,
    },
    {
        href: OPERATOR_ROUTE,
        label: "Operator",
        mobileLabel: "Ops",
        icon: ShieldUser,
    },
    {
        href: MANAGEMENT_ROUTE,
        label: "Management",
        mobileLabel: "Manage",
        icon: Settings2,
    },
    {
        href: DOWNLOAD_ROUTE,
        label: "Download",
        mobileLabel: "Report",
        icon: Download,
    },
];

export function AppShell({
    activePath,
    children,
    onLogout,
    onNavigate,
    resolvedTheme,
    session,
    title,
    onToggleTheme,
}: AppShellProps) {
    const [isExpanded, setIsExpanded] = useState(true);
    const [currentTime, setCurrentTime] = useState(() => new Date());
    const isOperator = session.admin_user.role === "operator";

    useEffect(() => {
        const timer = window.setInterval(() => {
            setCurrentTime(new Date());
        }, 1000);

        return () => {
            window.clearInterval(timer);
        };
    }, []);

    const formattedDate = new Intl.DateTimeFormat("en-GB", {
        weekday: "short",
        day: "numeric",
        month: "short",
        year: "numeric",
    }).format(currentTime);

    const formattedTime = new Intl.DateTimeFormat("en-GB", {
        hour: "numeric",
        minute: "2-digit",
        second: "2-digit",
        hour12: true,
    }).format(currentTime);

    const visibleNavItems = navItems.filter((item) => {
        if (isOperator) {
            return (
                item.href !== "/operator" &&
                item.href !== "/management" &&
                item.href !== "/download"
            );
        }

        return true;
    });
    const mobileNavItems: NavItem[] = [
        ...visibleNavItems,
        { href: PROFILE_ROUTE, label: "Profile", icon: UserCircle },
    ];

    return (
        <main className="relative min-h-screen overflow-hidden bg-[var(--background)] text-[var(--foreground)]">
            <div className="pointer-events-none absolute inset-0">
                <div className="absolute left-[-8rem] top-[-6rem] h-72 w-72 rounded-full bg-[var(--orb-strong)] blur-3xl" />
                <div className="absolute bottom-[-8rem] right-[-5rem] h-80 w-80 rounded-full bg-[var(--orb-soft)] blur-3xl" />
            </div>

            <div className="relative mx-auto flex min-h-screen w-full flex-col gap-4 px-3 pb-28 pt-4 sm:flex-row sm:px-4 sm:py-4 lg:px-5 lg:py-5">
                <aside
                    className={`sticky top-4 z-30 hidden h-[calc(100vh-2rem)] flex-col rounded-[1.75rem] border border-[var(--border-subtle)] bg-[var(--panel)] p-3 shadow-[0_24px_90px_rgba(15,23,42,0.18)] backdrop-blur-2xl transition-[width] duration-300 sm:flex ${
                        isExpanded ? "w-64" : "w-[4.75rem]"
                    }`}
                >
                <div className="flex h-12 items-center gap-3">
                    <img
                        src="/zostream-wifi.png"
                        alt=""
                        className="h-11 w-11 shrink-0 rounded-2xl object-cover shadow-[0_14px_32px_rgba(15,159,156,0.28)]"
                    />
                    {isExpanded ? (
                        <div className="min-w-0">
                            <p className="truncate text-sm font-semibold">
                                Zostream ISP
                            </p>
                            <p className="truncate text-xs text-[var(--muted-foreground)]">
                                Control panel
                            </p>
                        </div>
                    ) : null}
                </div>

                <button
                    type="button"
                    onClick={() => setIsExpanded((value) => !value)}
                    title={isExpanded ? "Collapse sidebar" : "Expand sidebar"}
                    aria-label={
                        isExpanded ? "Collapse sidebar" : "Expand sidebar"
                    }
                    className="absolute -right-3 top-14 flex h-8 w-8 items-center justify-center rounded-full border border-[var(--border-subtle)] bg-[var(--panel)] text-[var(--foreground)] shadow-[0_12px_28px_rgba(15,23,42,0.16)] backdrop-blur transition hover:border-[var(--accent)]"
                >
                    {isExpanded ? (
                        <ChevronLeft size={17} />
                    ) : (
                        <ChevronRight size={17} />
                    )}
                </button>

                <nav className="mt-8 grid gap-2">
                    {visibleNavItems.map((item) => {
                        const Icon = item.icon;
                        const isActive =
                            activePath === item.href ||
                            activePath.startsWith(`${item.href}/`) ||
                            (item.href === "/management" &&
                                activePath.startsWith("/management/"));

                        return (
                            <button
                                key={item.href}
                                type="button"
                                title={item.label}
                                onClick={() => onNavigate(item.href)}
                                className={`flex h-12 items-center gap-3 rounded-2xl px-3 text-sm font-medium transition ${
                                    isActive
                                        ? "bg-[var(--accent)] text-[var(--accent-foreground)] shadow-[0_14px_32px_rgba(15,159,156,0.24)]"
                                        : "text-[var(--muted-foreground)] hover:bg-[var(--panel-soft)] hover:text-[var(--foreground)]"
                                } ${isExpanded ? "" : "justify-center"}`}
                            >
                                <Icon size={20} strokeWidth={2.2} />
                                {isExpanded ? <span>{item.label}</span> : null}
                            </button>
                        );
                    })}
                </nav>

                <div className="mt-auto grid gap-2">
                    <button
                        type="button"
                        title="Profile"
                        onClick={() => onNavigate(PROFILE_ROUTE)}
                        className={`flex h-12 items-center gap-3 rounded-2xl px-3 text-sm font-medium transition ${
                            activePath === PROFILE_ROUTE
                                ? "bg-[var(--accent)] text-[var(--accent-foreground)] shadow-[0_14px_32px_rgba(15,159,156,0.24)]"
                                : "text-[var(--muted-foreground)] hover:bg-[var(--panel-soft)] hover:text-[var(--foreground)]"
                        } ${isExpanded ? "" : "justify-center"}`}
                    >
                        <UserCircle size={20} strokeWidth={2.2} />
                        {isExpanded ? (
                            <span className="min-w-0 truncate">
                                {session.admin_user.name || "Profile"}
                            </span>
                        ) : null}
                    </button>

                    <button
                        type="button"
                        onClick={onLogout}
                        title="Log out"
                        className={`flex h-12 items-center gap-3 rounded-2xl px-3 text-sm font-medium text-[var(--muted-foreground)] transition hover:bg-[var(--panel-soft)] hover:text-[var(--foreground)] ${
                            isExpanded ? "" : "justify-center"
                        }`}
                    >
                        <LogOut size={20} strokeWidth={2.2} />
                        {isExpanded ? <span>Log out</span> : null}
                    </button>
                </div>
                </aside>

                <div className="flex min-h-screen w-full min-w-0 flex-1 flex-col">
                    <div className="flex min-h-screen w-full flex-col">
                        <header className="flex items-center justify-between gap-4">
                            <div className="rounded-full border border-[var(--border-subtle)] bg-[var(--panel-soft)] px-4 py-2 text-xs font-semibold uppercase tracking-[0.32em] text-[var(--accent-strong)] backdrop-blur">
                                {title}
                            </div>
                            <div className="flex items-center gap-3">
                                <div className="hidden rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] px-4 py-2 text-right backdrop-blur sm:block">
                                    <p className="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-[var(--accent-strong)]">
                                        {formattedDate}
                                    </p>
                                    <p className="mt-1 text-sm font-semibold text-[var(--foreground)]">
                                        {formattedTime}
                                    </p>
                                </div>
                                <ThemeToggle
                                    resolvedTheme={resolvedTheme}
                                    onToggle={onToggleTheme}
                                />
                            </div>
                        </header>

                        <div className="mt-6 flex-1">{children}</div>
                    </div>
                </div>
            </div>

            <nav
                className="fixed bottom-3 left-3 right-3 z-30 grid gap-1 rounded-[1.5rem] border border-[var(--border-subtle)] bg-[var(--panel)] p-2 shadow-[0_24px_70px_rgba(15,23,42,0.22)] backdrop-blur-2xl sm:hidden"
                style={{
                    gridTemplateColumns: `repeat(${mobileNavItems.length}, minmax(0, 1fr))`,
                }}
            >
                {mobileNavItems.map((item) => {
                    const Icon = item.icon;
                    const isActive =
                        activePath === item.href ||
                        activePath.startsWith(`${item.href}/`) ||
                        (item.href === "/management" &&
                            activePath.startsWith("/management/"));
                    const mobileLabel =
                        "mobileLabel" in item ? item.mobileLabel : item.label;

                    return (
                        <button
                            key={item.href}
                            type="button"
                            aria-label={item.label}
                            onClick={() => onNavigate(item.href)}
                            className={`flex h-14 flex-col items-center justify-center gap-1 rounded-2xl text-[0.68rem] font-semibold transition ${
                                isActive
                                    ? "bg-[var(--accent)] text-[var(--accent-foreground)] shadow-[0_12px_28px_rgba(15,159,156,0.24)]"
                                    : "text-[var(--muted-foreground)] hover:bg-[var(--panel-soft)] hover:text-[var(--foreground)]"
                            }`}
                        >
                            <Icon size={20} strokeWidth={2.2} />
                            <span className="max-w-full truncate">
                                {mobileLabel}
                            </span>
                        </button>
                    );
                })}
            </nav>
        </main>
    );
}
