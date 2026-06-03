import { useEffect, useState } from "react";
import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from "recharts";
import {
    getStoredAdminSession,
    type AdminLoginResponse,
} from "../lib/admin-auth";
import { getRouteTitle } from "../lib/routes";
import { DashboardSkeleton } from "./ui/dashboard-skeleton";
import { fetchUsersCount, type JazeUsersCountStats } from "../lib/jaze";

type DashboardPageProps = {
    activePath: string;
    session: AdminLoginResponse;
};

export function getPageTitle(path: string) {
    return getRouteTitle(path);
}

function UsersCountChart({ stats }: { stats: JazeUsersCountStats }) {
    const [hoveredLabel, setHoveredLabel] = useState<string | null>(null);
    const [activeIndex, setActiveIndex] = useState<number | null>(null);
    const total = Math.max(stats.total, 1);
    const segments = [
        { label: "Active", value: stats.active, color: "#0f9f9c" },
        { label: "Online", value: stats.online, color: "#22c55e" },
        { label: "Expired", value: stats.expired, color: "#f97316" },
        {
            label: "Other",
            value: Math.max(
                stats.total - stats.active - stats.online - stats.expired,
                0,
            ),
            color: "#94a3b8",
        },
    ];
    const handleSegmentHover = (index: number) => {
        const segment = segments[index];

        setActiveIndex(index);
        setHoveredLabel(
            `${segment.label} • ${Math.round((segment.value / total) * 100)}%`,
        );
    };

    const handleSegmentLeave = () => {
        setActiveIndex(null);
        setHoveredLabel(null);
    };

    return (
        <div className="grid gap-5 lg:grid-cols-[260px_minmax(0,1fr)] lg:items-center">
            <div className="mx-auto flex w-full max-w-[260px] flex-col items-center justify-center">
                <div className="min-h-10 text-center">
                    {hoveredLabel ? (
                        <p className="text-sm font-semibold uppercase tracking-[0.2em] text-[var(--accent-strong)]">
                            {hoveredLabel}
                        </p>
                    ) : (
                        <p className="text-sm text-[var(--muted-foreground)]">
                            Hover the chart to inspect a segment
                        </p>
                    )}
                </div>

                <div className="relative h-[220px] w-full">
                    <ResponsiveContainer width="100%" height="100%">
                        <PieChart>
                            <Tooltip
                                formatter={(value: number | string, name: string) =>
                                    [value, name]
                                }
                                contentStyle={{
                                    border: "1px solid var(--border-subtle)",
                                    borderRadius: "1rem",
                                    backgroundColor: "var(--panel)",
                                    color: "var(--foreground)",
                                }}
                            />
                            <Pie
                                data={segments}
                                dataKey="value"
                                nameKey="label"
                                cx="50%"
                                cy="50%"
                                innerRadius={54}
                                outerRadius={82}
                                stroke="none"
                                paddingAngle={0}
                                activeIndex={activeIndex ?? undefined}
                                onMouseEnter={(_: unknown, index: number) =>
                                    handleSegmentHover(index)
                                }
                                onMouseLeave={handleSegmentLeave}
                            >
                                {segments.map((segment) => (
                                    <Cell
                                        key={segment.label}
                                        fill={segment.color}
                                    />
                                ))}
                            </Pie>
                        </PieChart>
                    </ResponsiveContainer>

                    <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                        <p className="text-[11px] font-semibold uppercase tracking-[0.24em] text-[var(--muted-foreground)]">
                            Total
                        </p>
                        <p className="mt-1 text-[28px] font-semibold text-[var(--foreground)]">
                            {stats.total}
                        </p>
                    </div>
                </div>
            </div>

            <div className="grid gap-3">
                {segments.map((segment, index) => (
                    <div
                        key={segment.label}
                        onMouseEnter={() => handleSegmentHover(index)}
                        onMouseLeave={handleSegmentLeave}
                        className="rounded-4xl flex cursor-pointer items-center justify-between border border-[var(--border-subtle)] bg-[var(--panel-soft)] px-4 py-3 transition hover:border-[var(--accent)]"
                    >
                        <div className="flex items-center gap-3">
                            <span
                                className="h-3 w-3 rounded-full"
                                style={{ backgroundColor: segment.color }}
                            />
                            <span className="text-sm font-medium text-[var(--foreground)]">
                                {segment.label}
                            </span>
                        </div>
                        <span className="text-sm font-semibold text-[var(--foreground)]">
                            {segment.value}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}

function MetricCard({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-[1.5rem] border border-[var(--border-subtle)] bg-[var(--panel)] p-5 shadow-[0_18px_50px_rgba(15,23,42,0.12)] backdrop-blur-2xl">
            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--accent-strong)]">
                {label}
            </p>
            <p className="mt-3 text-2xl font-semibold text-[var(--foreground)]">
                {value}
            </p>
        </div>
    );
}

export function DashboardPage({ activePath, session }: DashboardPageProps) {
    const cachedSession = getStoredAdminSession();
    const branchName =
        cachedSession?.admin_user.branch?.name ??
        session.admin_user.branch?.name ??
        "No branch assigned";
    const pageTitle = getPageTitle(activePath);
    const [usersCount, setUsersCount] = useState<JazeUsersCountStats | null>(
        null,
    );
    const [isLoadingCount, setIsLoadingCount] = useState(
        activePath === "/dashboard",
    );
    const [countError, setCountError] = useState<string | null>(null);

    useEffect(() => {
        if (activePath !== "/dashboard") {
            return;
        }

        const storedSession = getStoredAdminSession();
        const accountId =
            storedSession?.admin_user.branch?.code ??
            session.admin_user.branch?.code;
        const token = storedSession?.token ?? session.token;

        if (!accountId) {
            setUsersCount(null);
            setCountError("Branch code is not configured for this branch.");
            setIsLoadingCount(false);
            return;
        }

        let isActive = true;

        setIsLoadingCount(true);
        setCountError(null);

        void fetchUsersCount({
            accountId,
            token,
        })
            .then((count) => {
                if (!isActive) {
                    return;
                }

                setUsersCount(count);
            })
            .catch((error) => {
                if (!isActive) {
                    return;
                }

                setCountError(
                    error instanceof Error
                        ? error.message
                        : "Unable to load dashboard stats.",
                );
            })
            .finally(() => {
                if (!isActive) {
                    return;
                }

                setIsLoadingCount(false);
            });

        return () => {
            isActive = false;
        };
    }, [activePath, session.admin_user.branch?.code, session.token]);

    return (
        <section className="mt-6 grid gap-4">
            <div className="rounded-[2rem] border border-[var(--border-subtle)] bg-[var(--panel)] p-6 backdrop-blur-2xl sm:p-8">
                <p className="text-sm font-semibold uppercase tracking-[0.28em] text-[var(--accent-strong)]">
                    {pageTitle}
                </p>
                <h1 className="mt-3 text-3xl font-semibold tracking-tight text-[var(--foreground)] sm:text-4xl">
                    Welcome, {branchName}
                </h1>
                <p className="mt-3 max-w-2xl text-sm leading-6 text-[var(--muted-foreground)] sm:text-base">
                    Hei hi Zo Stream WIFI management system ani.
                </p>
            </div>

            <div className="flex justify-center">
                <div className="w-full max-w-5xl p-5">
                    <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--accent-strong)]">
                        Users Overview
                    </p>

                    <div className="mt-6">
                        {isLoadingCount ? (
                            <DashboardSkeleton />
                        ) : countError ? (
                            <div className="rounded-[1.25rem] border border-red-400/20 bg-red-500/10 px-4 py-8 text-center text-sm text-[var(--muted-foreground)]">
                                {countError}
                            </div>
                        ) : usersCount ? (
                            <UsersCountChart stats={usersCount} />
                        ) : (
                            <div className="rounded-[1.25rem] border border-[var(--border-subtle)] bg-[var(--panel-soft)] px-4 py-8 text-center text-sm text-[var(--muted-foreground)]">
                                No users count data available.
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {usersCount ? (
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <MetricCard label="Total" value={usersCount.total} />

                    <MetricCard
                        label="Expiring Next Week"
                        value={usersCount.expiringNextWeek}
                    />
                    <MetricCard
                        label="New Users Last Week"
                        value={usersCount.newUsersLastWeek}
                    />
                    <MetricCard
                        label="Blacklisted"
                        value={usersCount.blacklisted}
                    />
                    <MetricCard
                        label="Suspended"
                        value={usersCount.suspended}
                    />
                    <MetricCard label="Blocked" value={usersCount.blocked} />
                    <MetricCard label="Pending" value={usersCount.pending} />
                    <MetricCard label="Churned" value={usersCount.churned} />
                    <MetricCard label="Others" value={usersCount.others} />
                    <MetricCard label="Frozen" value={usersCount.frozen} />
                </div>
            ) : null}
        </section>
    );
}
