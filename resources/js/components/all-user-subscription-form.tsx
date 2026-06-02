import { ChevronLeft, ChevronRight, Search } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { toast } from "react-toastify";
import { getStoredAdminSession } from "../lib/admin-auth";
import { fetchJazeUsers, renewJazeUser, type JazeUser } from "../lib/jaze";
import { searchJazeUsers } from "../lib/jaze-user-search";

const PAGE_SIZE = 10;
const STATUS_OPTIONS = [
    { label: "All status", value: "" },
    { label: "Active", value: "active" },
    { label: "Expired", value: "expired" },
    { label: "Online", value: "online" },
    { label: "Blacklisted", value: "blacklisted" },
    { label: "Suspended", value: "suspended" },
    { label: "Blocked", value: "blocked" },
    { label: "Pending", value: "pending" },
    { label: "Churned", value: "churned" },
    { label: "Frozen", value: "frozen" },
] as const;

function formatActivationTime(value?: string) {
    if (!value) {
        return "-";
    }

    const parsedDate = new Date(value.replace(" ", "T"));

    if (Number.isNaN(parsedDate.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("en-GB", {
        day: "numeric",
        month: "short",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    }).format(parsedDate);
}

export function AllUserSubscriptionForm() {
    const [query, setQuery] = useState("");
    const [status, setStatus] = useState("");
    const [page, setPage] = useState(1);
    const [isLoading, setIsLoading] = useState(true);
    const [users, setUsers] = useState<JazeUser[]>([]);
    const [totalRecords, setTotalRecords] = useState(0);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const [renewingUserId, setRenewingUserId] = useState<string | null>(null);
    const [refreshTick, setRefreshTick] = useState(0);

    useEffect(() => {
        const storedSession = getStoredAdminSession();
        const token = storedSession?.token;
        const branchCode = storedSession?.admin_user.branch?.code ?? "";

        if (!token) {
            setIsLoading(false);
            setErrorMessage("Admin token is not available.");
            return;
        }

        let isActive = true;

        setIsLoading(true);
        setErrorMessage(null);

        void fetchJazeUsers({
            token,
            page,
            perPage: PAGE_SIZE,
            status,
            branchCode,
        })
            .then((payload) => {
                if (!isActive) {
                    return;
                }

                setUsers(payload.users);
                setTotalRecords(payload.totalRecords);
            })
            .catch((error) => {
                if (!isActive) {
                    return;
                }

                setErrorMessage(
                    error instanceof Error
                        ? error.message
                        : "Unable to load Jaze users.",
                );
            })
            .finally(() => {
                if (!isActive) {
                    return;
                }

                setIsLoading(false);
            });

        return () => {
            isActive = false;
        };
    }, [page, refreshTick, status]);

    useEffect(() => {
        setPage(1);
    }, [status]);

    const filteredUsers = useMemo(() => {
        return searchJazeUsers(users, query);
    }, [query, users]);

    const fetchedRecordsThroughCurrentPage =
        (page - 1) * PAGE_SIZE + users.length;
    const effectiveTotalRecords =
        totalRecords > 0
            ? totalRecords
            : Math.max(fetchedRecordsThroughCurrentPage, filteredUsers.length);
    const totalPages =
        totalRecords > 0
            ? Math.max(1, Math.ceil(totalRecords / PAGE_SIZE))
            : Math.max(page, users.length === PAGE_SIZE ? page + 1 : page);
    const hasServerTotal = totalRecords > 0;
    const hasPreviousPage = page > 1;
    const hasNextPage =
        (hasServerTotal && page < totalPages) || users.length === PAGE_SIZE;

    const handlePreviousPage = () => {
        if (hasPreviousPage) {
            setPage(page - 1);
        }
    };

    const handleNextPage = () => {
        if (hasNextPage) {
            setPage(page + 1);
        }
    };

    const handleRenew = async (subscriber: JazeUser) => {
        const storedSession = getStoredAdminSession();
        const token = storedSession?.token;
        const branchCode = storedSession?.admin_user.branch?.code ?? "";

        if (!token || !branchCode) {
            toast.error("Admin token or branch code is not available.");
            return;
        }

        setRenewingUserId(subscriber.id);

        try {
            const response = await renewJazeUser({
                token,
                branchCode,
                userId: subscriber.id,
                phone: subscriber.phone,
            });

            toast.success(response.message);
            setRefreshTick(refreshTick + 1);
        } catch (error) {
            toast.error(
                error instanceof Error
                    ? error.message
                    : "Unable to renew subscriber.",
            );
        } finally {
            setRenewingUserId(null);
        }
    };

    return (
        <>
            <div className="grid gap-5">
                <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px]">
                    <div className="flex items-center gap-3 rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] px-4 py-3">
                        <Search
                            size={16}
                            className="text-[var(--muted-foreground)]"
                        />
                        <input
                            type="text"
                            placeholder="Search by name, phone, email, username, or ID"
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            className="w-full bg-transparent text-sm text-[var(--foreground)] outline-none placeholder:text-[var(--muted-foreground)]"
                        />
                    </div>
                    <select
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] px-4 py-3 text-sm text-[var(--foreground)] outline-none"
                    >
                        {STATUS_OPTIONS.map((option) => (
                            <option
                                key={option.value || "all"}
                                value={option.value}
                            >
                                {option.label}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="overflow-hidden rounded-[1.75rem] border border-[var(--border-subtle)] bg-[var(--panel-soft)]">
                    <div className="grid grid-cols-[1.2fr_1fr_1fr_120px_150px] gap-3 border-b border-[var(--border-subtle)] px-5 py-4 text-xs font-semibold uppercase tracking-[0.22em] text-[var(--accent-strong)]">
                        <span>Subscriber</span>
                        <span>Activation Time</span>
                        <span>Username</span>
                        <span>Status</span>
                        <span>Action</span>
                    </div>

                    {isLoading ? (
                        <div className="px-5 py-8 text-sm text-[var(--muted-foreground)]">
                            Loading Jaze users...
                        </div>
                    ) : errorMessage ? (
                        <div className="px-5 py-8 text-sm text-red-300">
                            {errorMessage}
                        </div>
                    ) : filteredUsers.length > 0 ? (
                        filteredUsers.map((subscriber: JazeUser) => (
                            <div
                                key={subscriber.id}
                                className="grid grid-cols-[1.2fr_1fr_1fr_120px_150px] gap-3 border-b border-[var(--border-subtle)] px-5 py-4 text-sm text-[var(--foreground)] last:border-b-0"
                            >
                                <div>
                                    <p className="font-semibold">
                                        {subscriber.name || "Unnamed"}
                                    </p>
                                    <p className="mt-1 text-[var(--muted-foreground)]">
                                        {subscriber.phone ||
                                            subscriber.email ||
                                            "-"}
                                    </p>
                                </div>
                                <span>
                                    {formatActivationTime(
                                        subscriber.activationTime,
                                    )}
                                </span>
                                <span>{subscriber.username || "-"}</span>
                                <span className="text-[var(--muted-foreground)]">
                                    {subscriber.status}
                                </span>
                                {subscriber.status === "expired" ? (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            void handleRenew(subscriber)
                                        }
                                        disabled={
                                            renewingUserId === subscriber.id
                                        }
                                        className="inline-flex min-h-10 items-center justify-center rounded-2xl border border-[var(--border-subtle)] bg-blue-500 text-white px-4 py-2 text-sm font-medium transition hover:border-[var(--accent)] disabled:cursor-not-allowed disabled:opacity-70"
                                    >
                                        {renewingUserId === subscriber.id ? (
                                            <span className="h-4 w-4 animate-spin rounded-full border-2 border-[var(--accent)] border-t-transparent" />
                                        ) : (
                                            "Subscribe"
                                        )}
                                    </button>
                                ) : (
                                    <span className="text-sm text-[var(--muted-foreground)]">
                                        -
                                    </span>
                                )}
                            </div>
                        ))
                    ) : (
                        <div className="px-5 py-8 text-sm text-[var(--muted-foreground)]">
                            No Jaze users found.
                        </div>
                    )}
                </div>

                <div className="flex items-center justify-between rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] px-4 py-3 text-sm text-[var(--muted-foreground)]">
                    <span>
                        Showing{" "}
                        {users.length > 0 ? (page - 1) * PAGE_SIZE + 1 : 0}-
                        {users.length > 0
                            ? (page - 1) * PAGE_SIZE + users.length
                            : 0}{" "}
                        of {effectiveTotalRecords} users
                    </span>
                    <div className="flex items-center gap-3">
                        <span className="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--accent-strong)]">
                            Page {page} of {totalPages}
                        </span>
                        <button
                            type="button"
                            onClick={handlePreviousPage}
                            disabled={!hasPreviousPage}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-[var(--border-subtle)] bg-[var(--panel)] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <ChevronLeft size={16} />
                        </button>
                        <button
                            type="button"
                            onClick={handleNextPage}
                            disabled={!hasNextPage}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-[var(--border-subtle)] bg-[var(--panel)] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <ChevronRight size={16} />
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
}
