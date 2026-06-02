import { CalendarDays, Monitor, Smartphone, User, X } from "lucide-react";

type AllUserSubscribeDialogProps = {
    onClose: () => void;
};

export function AllUserSubscribeDialog({
    onClose,
}: AllUserSubscribeDialogProps) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm">
            <div className="max-h-[calc(100vh-2rem)] w-full max-w-2xl overflow-y-auto rounded-[2rem] border border-[var(--border-subtle)] bg-[var(--panel)] p-5 shadow-[0_24px_90px_rgba(15,23,42,0.28)] backdrop-blur-xl sm:p-6">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.28em] text-[var(--accent-strong)]">
                            Confirm
                        </p>
                        <h2 className="mt-3 text-2xl font-semibold tracking-tight text-[var(--foreground)]">
                            Add Zo Stream subscription
                        </h2>
                        <p className="mt-2 text-sm leading-6 text-[var(--muted-foreground)]">
                            Review the subscriber and choose the allowed device
                            type.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] transition hover:border-[var(--accent)]"
                    >
                        <X size={18} />
                    </button>
                </div>

                <div className="mt-6 grid gap-4 md:grid-cols-[1.1fr_0.9fr]">
                    <section className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] p-4">
                        <div className="flex items-start gap-3">
                            <span className="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[var(--accent-soft)] text-[var(--accent-strong)]">
                                <User size={18} />
                            </span>
                            <div>
                                <h3 className="text-base font-semibold text-[var(--foreground)]">
                                    Toningam Rina
                                </h3>
                                <p className="mt-1 text-sm text-[var(--muted-foreground)]">
                                    8732856261 | rina@example.com
                                </p>
                            </div>
                        </div>

                        <div className="mt-4 grid gap-3 text-sm text-[var(--muted-foreground)]">
                            <p>
                                City:{" "}
                                <span className="text-[var(--foreground)]">
                                    Lungpho
                                </span>
                            </p>
                            <p>
                                UID:{" "}
                                <span className="font-mono text-[var(--foreground)]">
                                    ZS-UID-10093
                                </span>
                            </p>
                            <p>
                                Status:{" "}
                                <span className="text-[var(--foreground)]">
                                    Active WIFI plan
                                </span>
                            </p>
                        </div>
                    </section>

                    <section className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] p-4">
                        <p className="text-sm font-semibold uppercase tracking-[0.24em] text-[var(--accent-strong)]">
                            Devices
                        </p>
                        <div className="mt-4 grid gap-3">
                            <button
                                type="button"
                                className="flex items-center gap-3 rounded-2xl border border-[var(--accent)] bg-[var(--panel)] px-4 py-3 text-sm font-medium text-[var(--foreground)]"
                            >
                                <Smartphone size={18} />
                                Mobile
                            </button>
                            <button
                                type="button"
                                className="flex items-center gap-3 rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel)] px-4 py-3 text-sm font-medium text-[var(--foreground)]"
                            >
                                <Monitor size={18} />
                                TV
                            </button>
                        </div>

                        <div className="mt-5 rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel)] p-4">
                            <div className="flex items-center gap-3">
                                <CalendarDays
                                    size={18}
                                    className="text-[var(--accent-strong)]"
                                />
                                <div>
                                    <p className="text-sm font-medium text-[var(--foreground)]">
                                        Plan window
                                    </p>
                                    <p className="text-sm text-[var(--muted-foreground)]">
                                        2026-05-21 to 2026-06-20
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div className="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] px-4 py-3 text-sm font-medium text-[var(--foreground)]"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        className="rounded-2xl bg-[var(--accent)] px-4 py-3 text-sm font-semibold text-[var(--accent-foreground)]"
                    >
                        Confirm subscription
                    </button>
                </div>
            </div>
        </div>
    );
}
