import { Plus, RefreshCw } from "lucide-react";
import { useEffect, useState, type FormEvent } from "react";
import { toast } from "react-toastify";
import { getStoredAdminSession } from "../lib/admin-auth";
import { addJazeUser } from "../lib/jaze";
import {
    fetchJazePlanGroups,
    type JazeGroup,
} from "../lib/jaze-group";

function toApiDateTime(value: string) {
    const [datePart = "", timePart = "00:00"] = value.split("T");
    const [year = "", month = "", day = ""] = datePart.split("-");
    const normalizedTime = timePart.length === 5 ? `${timePart}:00` : timePart;

    if (!day || !month || !year) {
        return value;
    }

    return `${day}-${month}-${year} ${normalizedTime}`;
}

function addDays(date: Date, days: number) {
    const nextDate = new Date(date);
    nextDate.setDate(nextDate.getDate() + days);

    return nextDate;
}

function formatDateTimeLocal(date: Date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    const hours = String(date.getHours()).padStart(2, "0");
    const minutes = String(date.getMinutes()).padStart(2, "0");

    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function resolveExpirationDate(expirationInput: string) {
    return {
        expirationDate: "custom",
        customExpirationDate: expirationInput
            ? toApiDateTime(expirationInput)
            : toApiDateTime(formatDateTimeLocal(addDays(new Date(), 30))),
    };
}

export function AllUserAddForm() {
    const [firstName, setFirstName] = useState("");
    const [lastName, setLastName] = useState("");
    const [phoneNumber, setPhoneNumber] = useState("");
    const [emailId, setEmailId] = useState("");
    const [userName, setUserName] = useState("");
    const [password, setPassword] = useState("");
    const [expirationDateInput, setExpirationDateInput] = useState("");
    const [idFile, setIdFile] = useState<File | null>(null);
    const [fileInputKey, setFileInputKey] = useState(0);
    const [userGroupId, setUserGroupId] = useState<number | "">("");
    const [groups, setGroups] = useState<JazeGroup[]>([]);
    const [isLoadingGroups, setIsLoadingGroups] = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [groupError, setGroupError] = useState<string | null>(null);

    const loadGroups = async () => {
        const storedSession = getStoredAdminSession();
        const token = storedSession?.token;

        if (!token) {
            setGroupError("Admin token is not available.");
            setIsLoadingGroups(false);
            return;
        }

        setIsLoadingGroups(true);
        setGroupError(null);

        try {
            const nextGroups = await fetchJazePlanGroups({ token });

            setGroups(nextGroups);
            setUserGroupId(nextGroups[0]?.group_id ?? "");

            if (nextGroups.length === 0) {
                setGroupError("No WIFI plans are available.");
            }
        } catch (error) {
            setGroupError(
                error instanceof Error
                    ? error.message
                    : "Unable to load WIFI plans.",
            );
        } finally {
            setIsLoadingGroups(false);
        }
    };

    useEffect(() => {
        void loadGroups();
    }, []);

    const resetForm = () => {
        setFirstName("");
        setLastName("");
        setPhoneNumber("");
        setEmailId("");
        setUserName("");
        setPassword("");
        setExpirationDateInput("");
        setIdFile(null);
        setFileInputKey((value) => value + 1);
        setUserGroupId(groups[0]?.group_id ?? "");
    };

    const handleRefresh = () => {
        resetForm();
        void loadGroups();
    };

    const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const storedSession = getStoredAdminSession();
        const token = storedSession?.token;
        const branchCode = storedSession?.admin_user.branch?.code ?? "";

        if (!token || !branchCode) {
            toast.error("Admin token or branch code is not available.");
            return;
        }

        if (userGroupId === "") {
            toast.error("Please select a WIFI plan.");
            return;
        }

        const activationDate = "now";
        const { expirationDate, customExpirationDate } =
            resolveExpirationDate(expirationDateInput);
        const payload = {
            branch_code: branchCode,
            userGroupId,
            accountId: branchCode,
            userName,
            password,
            userState: "active",
            userType: "home",
            activationDate,
            expirationDate,
            customExpirationDate,
            phoneNumber,
            emailId,
            firstName,
            lastName,
        };

        console.log("Jaze add-user payload", payload);

        setIsSubmitting(true);

        try {
            const response = await addJazeUser({
                token,
                branchCode,
                userGroupId,
                accountId: branchCode,
                userName,
                password,
                phoneNumber,
                emailId,
                firstName,
                lastName,
                activationDate,
                expirationDate,
                customExpirationDate,
                idFile,
            });

            toast.success(response.message);
            resetForm();
        } catch (error) {
            toast.error(
                error instanceof Error
                    ? error.message
                    : "Unable to add subscriber.",
            );
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <section className="rounded-[1.75rem] border border-[var(--border-subtle)] bg-[var(--panel-soft)] p-5 shadow-[0_18px_50px_rgba(15,23,42,0.08)]">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--accent-strong)]">
                            Add Subscriber
                        </p>
                        <h3 className="mt-2 text-xl font-semibold text-[var(--foreground)]">
                            New WIFI subscriber
                        </h3>
                    </div>
                    <button
                        type="button"
                        onClick={handleRefresh}
                        className="inline-flex h-11 items-center gap-2 rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel)] px-4 text-sm font-medium text-[var(--foreground)] transition hover:border-[var(--accent)]"
                    >
                        <RefreshCw size={16} />
                        Refresh
                    </button>
                </div>

                <form
                    className="mt-6 grid gap-4 md:grid-cols-2"
                    onSubmit={handleSubmit}
                >
                    <label className="grid gap-2">
                        <span className="text-sm font-medium text-[var(--foreground)]">
                            First name
                        </span>
                        <input
                            type="text"
                            value={firstName}
                            onChange={(event) =>
                                setFirstName(event.target.value)
                            }
                            placeholder="Enter first name"
                            required
                            className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel)] px-4 py-3 text-sm text-[var(--foreground)] outline-none placeholder:text-[var(--muted-foreground)]"
                        />
                    </label>

                    <label className="grid gap-2">
                        <span className="text-sm font-medium text-[var(--foreground)]">
                            Last name
                        </span>
                        <input
                            type="text"
                            value={lastName}
                            onChange={(event) =>
                                setLastName(event.target.value)
                            }
                            placeholder="Enter last name"
                            required
                            className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel)] px-4 py-3 text-sm text-[var(--foreground)] outline-none placeholder:text-[var(--muted-foreground)]"
                        />
                    </label>

                    <label className="grid gap-2">
                        <span className="text-sm font-medium text-[var(--foreground)]">
                            Phone number
                        </span>
                        <input
                            type="text"
                            value={phoneNumber}
                            onChange={(event) =>
                                setPhoneNumber(event.target.value)
                            }
                            placeholder="7000000011"
                            required
                            className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel)] px-4 py-3 text-sm text-[var(--foreground)] outline-none placeholder:text-[var(--muted-foreground)]"
                        />
                    </label>

                    <label className="grid gap-2">
                        <span className="text-sm font-medium text-[var(--foreground)]">
                            Email
                        </span>
                        <input
                            type="email"
                            value={emailId}
                            onChange={(event) => setEmailId(event.target.value)}
                            placeholder="subscriber@example.com"
                            required
                            className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel)] px-4 py-3 text-sm text-[var(--foreground)] outline-none placeholder:text-[var(--muted-foreground)]"
                        />
                    </label>

                    <label className="grid gap-2">
                        <span className="text-sm font-medium text-[var(--foreground)]">
                            WIFI Plan
                        </span>
                        <select
                            value={
                                userGroupId === ""
                                    ? ""
                                    : String(userGroupId)
                            }
                            onChange={(event) =>
                                setUserGroupId(
                                    event.target.value === ""
                                        ? ""
                                        : Number(event.target.value),
                                )
                            }
                            required
                            disabled={isLoadingGroups || groups.length === 0}
                            className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel)] px-4 py-3 text-sm text-[var(--foreground)] outline-none disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <option value="">
                                {isLoadingGroups
                                    ? "Loading plans..."
                                    : groups.length === 0
                                      ? "No WIFI plans available"
                                      : "Select WIFI plan"}
                            </option>
                            {groups.map((group) => (
                                <option key={group.group_id} value={group.group_id}>
                                    {group.group_name}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="grid gap-2">
                        <span className="text-sm font-medium text-[var(--foreground)]">
                            Username
                        </span>
                        <input
                            type="text"
                            value={userName}
                            onChange={(event) =>
                                setUserName(event.target.value)
                            }
                            placeholder="TESTUSER001"
                            required
                            className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel)] px-4 py-3 text-sm text-[var(--foreground)] outline-none placeholder:text-[var(--muted-foreground)]"
                        />
                    </label>

                    <label className="grid gap-2">
                        <span className="text-sm font-medium text-[var(--foreground)]">
                            Password
                        </span>
                        <input
                            type="text"
                            value={password}
                            onChange={(event) =>
                                setPassword(event.target.value)
                            }
                            placeholder="password123"
                            required
                            className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel)] px-4 py-3 text-sm text-[var(--foreground)] outline-none"
                        />
                    </label>

                    <label className="grid gap-2 md:col-span-2">
                        <div className="flex items-center justify-between gap-3">
                            <span className="text-sm font-medium text-[var(--foreground)]">
                                ID file
                            </span>
                            {idFile ? (
                                <button
                                    type="button"
                                    onClick={() => {
                                        setIdFile(null);
                                        setFileInputKey((value) => value + 1);
                                    }}
                                    className="text-xs font-semibold text-[var(--accent-strong)] transition hover:text-[var(--accent)]"
                                >
                                    Remove file
                                </button>
                            ) : null}
                        </div>
                        <input
                            key={fileInputKey}
                            type="file"
                            accept="image/*,application/pdf"
                            onChange={(event) => {
                                setIdFile(event.target.files?.[0] ?? null);
                            }}
                            className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel)] px-4 py-3 text-sm text-[var(--foreground)] outline-none file:mr-4 file:rounded-xl file:border-0 file:bg-[var(--accent)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[var(--accent-foreground)]"
                        />
                        <p className="text-xs text-[var(--muted-foreground)]">
                            Optional. Upload an image or PDF for{" "}
                            <span className="font-semibold text-[var(--foreground)]">
                                idFile
                            </span>
                            .
                        </p>
                        {idFile ? (
                            <p className="text-xs font-medium text-[var(--accent-strong)]">
                                Selected file: {idFile.name}
                            </p>
                        ) : (
                            <p className="text-xs text-[var(--muted-foreground)]">
                                No file selected.
                            </p>
                        )}
                    </label>

                    <label className="grid gap-2">
                        <span className="text-sm font-medium text-[var(--foreground)]">
                            Expiration date
                        </span>
                        <input
                            type="datetime-local"
                            value={expirationDateInput}
                            onChange={(event) =>
                                setExpirationDateInput(event.target.value)
                            }
                            className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel)] px-4 py-3 text-sm text-[var(--foreground)] outline-none"
                        />
                    </label>

                    {groupError ? (
                        <div className="md:col-span-2 rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                            {groupError}
                        </div>
                    ) : null}

                    <div className="md:col-span-2 flex justify-end">
                        <button
                            type="submit"
                            disabled={isSubmitting || isLoadingGroups}
                            className="inline-flex min-h-12 items-center gap-2 rounded-2xl bg-[var(--accent)] px-5 py-3 text-sm font-semibold text-[var(--accent-foreground)] shadow-[0_14px_32px_rgba(15,159,156,0.22)] disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            {isSubmitting ? (
                                <span className="h-4 w-4 animate-spin rounded-full border-2 border-[var(--accent-foreground)] border-t-transparent" />
                            ) : (
                                <Plus size={16} />
                            )}
                            {isSubmitting
                                ? "Adding subscriber..."
                                : "Add subscriber"}
                        </button>
                    </div>
                </form>
            </section>

            <section className="rounded-[1.75rem] border border-[var(--border-subtle)] bg-[var(--panel-soft)] p-5 shadow-[0_18px_50px_rgba(15,23,42,0.08)]">
                <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--accent-strong)]">
                    Request Defaults
                </p>
                <h3 className="mt-2 text-xl font-semibold text-[var(--foreground)]">
                    Jaze add-user payload
                </h3>

                <div className="mt-6 grid gap-3">
                    <div className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel)] p-4 text-sm text-[var(--muted-foreground)]">
                        <p>
                            Branch code and account ID are pulled from the
                            cached login branch.
                        </p>
                        <p className="mt-2">
                            Activation date is always sent as{" "}
                            <span className="font-semibold text-[var(--foreground)]">
                                now
                            </span>
                            .
                        </p>
                        <p className="mt-2">
                            If expiration date is left empty, it is calculated
                            as{" "}
                            <span className="font-semibold text-[var(--foreground)]">
                                30 days
                            </span>{" "}
                            from the current time and sent as{" "}
                            <span className="font-semibold text-[var(--foreground)]">
                                customExpirationDate
                            </span>
                            .
                        </p>
                    </div>
                    <div className="rounded-2xl border border-dashed border-[var(--border-subtle)] bg-[var(--panel)] p-4 text-sm text-[var(--muted-foreground)]">
                        Selected WIFI plan sends its{" "}
                        <span className="font-semibold text-[var(--foreground)]">
                            group_id
                        </span>{" "}
                        as{" "}
                        <span className="font-semibold text-[var(--foreground)]">
                            userGroupId
                        </span>
                        .
                    </div>
                </div>
            </section>
        </div>
    );
}
