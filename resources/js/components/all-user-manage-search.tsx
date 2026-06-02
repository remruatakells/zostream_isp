import { Pencil, RefreshCw, Save, Search, Trash2, X } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { toast } from "react-toastify";
import { getStoredAdminSession } from "../lib/admin-auth";
import {
    fetchJazeUserDetails,
    fetchJazeUsers,
    type JazeUser,
    updateJazeUser,
} from "../lib/jaze";
import { searchJazeUsers } from "../lib/jaze-user-search";

const PAGE_SIZE = 50;

type EditFormState = {
    name: string;
    username: string;
    phone: string;
    email: string;
    activationTime: string;
    expirationTime: string;
};

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

function toDateInputValue(value?: string): string {
    if (!value) {
        return "";
    }

    const parsedDate = new Date(value.replace(" ", "T"));

    if (!Number.isNaN(parsedDate.getTime())) {
        return parsedDate.toISOString().slice(0, 10);
    }

    const trimmedValue = value.trim();

    return /^\d{4}-\d{2}-\d{2}$/.test(trimmedValue)
        ? trimmedValue
        : trimmedValue.slice(0, 10);
}

function splitName(name: string): { firstName: string; lastName: string } {
    const trimmedName = name.trim();

    if (!trimmedName) {
        return {
            firstName: "",
            lastName: "",
        };
    }

    const [firstName, ...rest] = trimmedName.split(/\s+/);

    return {
        firstName,
        lastName: rest.join(" "),
    };
}

function createEditFormState(user: JazeUser): EditFormState {
    return {
        name: user.name ?? "",
        username: user.username ?? "",
        phone: user.phone ?? "",
        email: user.email ?? "",
        activationTime: toDateInputValue(user.activationTime),
        expirationTime: toDateInputValue(user.expirationTime),
    };
}

type EditSubscriberModalProps = {
    form: EditFormState;
    isSaving: boolean;
    subscriber: JazeUser;
    onChange: (field: keyof EditFormState, value: string) => void;
    onClose: () => void;
    onSave: () => void;
};

function EditSubscriberModal({
    form,
    isSaving,
    subscriber,
    onChange,
    onClose,
    onSave,
}: EditSubscriberModalProps) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm">
            <div className="max-h-[calc(100vh-2rem)] w-full max-w-2xl overflow-y-auto rounded-[2rem] border border-[var(--border-subtle)] bg-[var(--panel)] p-5 shadow-[0_24px_90px_rgba(15,23,42,0.28)] sm:p-6">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.28em] text-[var(--accent-strong)]">
                            Edit Subscriber
                        </p>
                        <h2 className="mt-3 text-2xl font-semibold tracking-tight text-[var(--foreground)]">
                            Update subscriber details
                        </h2>
                        <p className="mt-2 text-sm leading-6 text-[var(--muted-foreground)]">
                            Editing Jaze user ID{" "}
                            <span className="font-mono text-[var(--foreground)]">
                                {subscriber.id}
                            </span>
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        disabled={isSaving}
                        className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] transition hover:border-[var(--accent)] disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <X size={18} />
                    </button>
                </div>

                <div className="mt-6 grid gap-4 md:grid-cols-2">
                    <label className="grid gap-2 md:col-span-2">
                        <span className="text-sm font-medium text-[var(--foreground)]">
                            Name
                        </span>
                        <input
                            type="text"
                            value={form.name}
                            onChange={(event) =>
                                onChange("name", event.target.value)
                            }
                            className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] px-4 py-3 text-sm text-[var(--foreground)] outline-none"
                        />
                    </label>

                    <label className="grid gap-2">
                        <span className="text-sm font-medium text-[var(--foreground)]">
                            Username
                        </span>
                        <input
                            type="text"
                            value={form.username}
                            onChange={(event) =>
                                onChange("username", event.target.value)
                            }
                            className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] px-4 py-3 text-sm text-[var(--foreground)] outline-none"
                        />
                    </label>

                    <label className="grid gap-2">
                        <span className="text-sm font-medium text-[var(--foreground)]">
                            Phone
                        </span>
                        <input
                            type="text"
                            value={form.phone}
                            onChange={(event) =>
                                onChange("phone", event.target.value)
                            }
                            className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] px-4 py-3 text-sm text-[var(--foreground)] outline-none"
                        />
                    </label>

                    <label className="grid gap-2 md:col-span-2">
                        <span className="text-sm font-medium text-[var(--foreground)]">
                            Email
                        </span>
                        <input
                            type="email"
                            value={form.email}
                            onChange={(event) =>
                                onChange("email", event.target.value)
                            }
                            className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] px-4 py-3 text-sm text-[var(--foreground)] outline-none"
                        />
                    </label>

                    <label className="grid gap-2">
                        <span className="text-sm font-medium text-[var(--foreground)]">
                            Activation Date
                        </span>
                        <input
                            type="date"
                            value={form.activationTime}
                            onChange={(event) =>
                                onChange("activationTime", event.target.value)
                            }
                            className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] px-4 py-3 text-sm text-[var(--foreground)] outline-none"
                        />
                    </label>

                    <label className="grid gap-2">
                        <span className="text-sm font-medium text-[var(--foreground)]">
                            Expiration Date
                        </span>
                        <input
                            type="date"
                            value={form.expirationTime}
                            onChange={(event) =>
                                onChange("expirationTime", event.target.value)
                            }
                            className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] px-4 py-3 text-sm text-[var(--foreground)] outline-none"
                        />
                    </label>
                </div>

                <div className="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        onClick={onClose}
                        disabled={isSaving}
                        className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] px-4 py-3 text-sm font-medium text-[var(--foreground)] disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        onClick={onSave}
                        disabled={isSaving}
                        className="inline-flex items-center gap-2 rounded-2xl bg-[var(--accent)] px-4 py-3 text-sm font-semibold text-[var(--accent-foreground)] disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <Save size={16} />
                        {isSaving ? "Saving..." : "Save changes"}
                    </button>
                </div>
            </div>
        </div>
    );
}

export function AllUserManageSearch() {
    const [query, setQuery] = useState("");
    const [isLoading, setIsLoading] = useState(true);
    const [isReloading, setIsReloading] = useState(false);
    const [users, setUsers] = useState<JazeUser[]>([]);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const [editingUserId, setEditingUserId] = useState<string | null>(null);
    const [editModalUser, setEditModalUser] = useState<JazeUser | null>(null);
    const [editForm, setEditForm] = useState<EditFormState | null>(null);
    const [isSaving, setIsSaving] = useState(false);

    const loadUsers = async (isManualReload = false) => {
        const storedSession = getStoredAdminSession();
        const token = storedSession?.token;
        const branchCode = storedSession?.admin_user.branch?.code ?? "";

        if (!token) {
            setIsLoading(false);
            setIsReloading(false);
            setErrorMessage("Admin token is not available.");
            return;
        }

        if (isManualReload) {
            setIsReloading(true);
        } else {
            setIsLoading(true);
        }

        setErrorMessage(null);

        try {
            const payload = await fetchJazeUsers({
                token,
                page: 1,
                perPage: PAGE_SIZE,
                branchCode,
            });

            setUsers(payload.users);
        } catch (error) {
            setErrorMessage(
                error instanceof Error
                    ? error.message
                    : "Unable to load Jaze users.",
            );
        } finally {
            setIsLoading(false);
            setIsReloading(false);
        }
    };

    useEffect(() => {
        void loadUsers();
    }, []);

    const filteredUsers = useMemo(() => searchJazeUsers(users, query), [query, users]);

    const handleEditClick = async (subscriber: JazeUser) => {
        const storedSession = getStoredAdminSession();
        const token = storedSession?.token;
        const branchCode = storedSession?.admin_user.branch?.code ?? "";

        if (!token) {
            toast.error("Admin token is not available.");
            return;
        }

        setEditingUserId(subscriber.id);

        try {
            const detailedUser = await fetchJazeUserDetails({
                token,
                branchCode,
                userId: subscriber.id,
            });

            setEditModalUser(detailedUser);
            setEditForm(createEditFormState(detailedUser));
        } catch (error) {
            toast.error(
                error instanceof Error
                    ? error.message
                    : "Unable to load subscriber details.",
            );
        } finally {
            setEditingUserId(null);
        }
    };

    const handleSave = async () => {
        const storedSession = getStoredAdminSession();
        const token = storedSession?.token;
        const branchCode = storedSession?.admin_user.branch?.code ?? "";

        if (!token || !branchCode || !editModalUser || !editForm) {
            toast.error("Subscriber context is not available.");
            return;
        }

        const { firstName, lastName } = splitName(editForm.name);

        if (!editForm.username.trim()) {
            toast.error("Username is required.");
            return;
        }

        setIsSaving(true);

        try {
            const response = await updateJazeUser({
                token,
                branchCode,
                userId: editModalUser.id,
                userGroupId: editModalUser.groupId,
                accountId: editModalUser.account_id,
                userName: editForm.username.trim(),
                phoneNumber: editForm.phone.trim(),
                emailId: editForm.email.trim(),
                firstName,
                lastName,
                activationDate: editForm.activationTime || undefined,
                expirationDate: editForm.expirationTime || undefined,
            });

            const updatedUser: JazeUser = {
                ...editModalUser,
                name: editForm.name.trim(),
                username: editForm.username.trim(),
                phone: editForm.phone.trim(),
                email: editForm.email.trim(),
                activationTime: editForm.activationTime || editModalUser.activationTime,
                expirationTime: editForm.expirationTime || editModalUser.expirationTime,
            };

            setUsers((currentUsers) =>
                currentUsers.map((user) =>
                    user.id === updatedUser.id ? { ...user, ...updatedUser } : user,
                ),
            );
            setEditModalUser(updatedUser);
            toast.success(response.message);
            void loadUsers(true);
            setEditModalUser(null);
            setEditForm(null);
        } catch (error) {
            toast.error(
                error instanceof Error
                    ? error.message
                    : "Unable to update subscriber.",
            );
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <>
            <div className="grid gap-5">
                <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_180px]">
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

                    <button
                        type="button"
                        onClick={() => void loadUsers(true)}
                        disabled={isLoading || isReloading}
                        className="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] px-4 py-3 text-sm font-medium text-[var(--foreground)] transition hover:border-[var(--accent)] disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <RefreshCw
                            size={16}
                            className={isReloading ? "animate-spin" : ""}
                        />
                        {isReloading ? "Reloading..." : "Reload"}
                    </button>
                </div>

                <div className="overflow-hidden rounded-[1.75rem] border border-[var(--border-subtle)] bg-[var(--panel-soft)]">
                    <div className="grid grid-cols-[1.2fr_1fr_1fr_120px_180px] gap-3 border-b border-[var(--border-subtle)] px-5 py-4 text-xs font-semibold uppercase tracking-[0.22em] text-[var(--accent-strong)]">
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
                        filteredUsers.map((subscriber) => (
                            <div
                                key={subscriber.id}
                                className="grid grid-cols-[1.2fr_1fr_1fr_120px_180px] gap-3 border-b border-[var(--border-subtle)] px-5 py-4 text-sm text-[var(--foreground)] last:border-b-0"
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
                                    {subscriber.status || "-"}
                                </span>
                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            void handleEditClick(subscriber)
                                        }
                                        disabled={editingUserId === subscriber.id}
                                        className="inline-flex min-h-10 items-center justify-center gap-2 rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel)] px-4 py-2 text-sm font-medium transition hover:border-[var(--accent)] disabled:cursor-not-allowed disabled:opacity-70"
                                    >
                                        <Pencil size={15} />
                                        {editingUserId === subscriber.id
                                            ? "Loading..."
                                            : "Edit"}
                                    </button>
                                    <button
                                        type="button"
                                        disabled
                                        title="Delete API is not available yet."
                                        className="inline-flex min-h-10 items-center justify-center gap-2 rounded-2xl border border-red-400/30 bg-red-500/10 px-4 py-2 text-sm font-medium text-red-200 opacity-60"
                                    >
                                        <Trash2 size={15} />
                                        Delete
                                    </button>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="px-5 py-8 text-sm text-[var(--muted-foreground)]">
                            No Jaze users matched this search.
                        </div>
                    )}
                </div>
            </div>

            {editModalUser && editForm ? (
                <EditSubscriberModal
                    form={editForm}
                    isSaving={isSaving}
                    subscriber={editModalUser}
                    onChange={(field, value) =>
                        setEditForm((currentForm) =>
                            currentForm
                                ? {
                                      ...currentForm,
                                      [field]: value,
                                  }
                                : currentForm,
                        )
                    }
                    onClose={() => {
                        if (isSaving) {
                            return;
                        }

                        setEditModalUser(null);
                        setEditForm(null);
                    }}
                    onSave={() => void handleSave()}
                />
            ) : null}
        </>
    );
}
