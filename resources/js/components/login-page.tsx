import { type FormEvent, useState } from "react";
import { KeyRound, Phone } from "lucide-react";
import {
    loginAdmin,
    persistAdminSession,
    toCachedAdminSession,
    type AdminLoginResponse,
} from "../lib/admin-auth";

type LoginPageProps = {
    onLoginSuccess: (payload: AdminLoginResponse) => void;
};

export function LoginPage({ onLoginSuccess }: LoginPageProps) {
    const [login, setLogin] = useState("");
    const [password, setPassword] = useState("");
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setIsSubmitting(true);
        setErrorMessage(null);

        try {
            const response = await loginAdmin({
                login,
                password,
            });

            persistAdminSession(response);
            onLoginSuccess(toCachedAdminSession(response));
        } catch (error) {
            setErrorMessage(
                error instanceof Error
                    ? error.message
                    : "Unable to login right now.",
            );
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <section className="flex flex-1 items-center justify-center py-8 lg:py-12">
            <div className="w-full max-w-lg">
                <div className="rounded-4xl border border-[var(--border-strong)] bg-[var(--panel)] p-6 shadow-[0_30px_80px_rgba(15,23,42,0.18)] backdrop-blur xl:p-8">
                    <div className="space-y-3">
                        <p className="text-sm font-semibold uppercase tracking-[0.28em] text-[var(--accent-strong)]">
                            Allow user login
                        </p>
                        <h1 className="text-3xl font-semibold tracking-tight text-[var(--foreground)] sm:text-4xl">
                            Login to Zostream Wifi
                        </h1>
                    </div>

                    <form className="mt-8 space-y-5" onSubmit={handleSubmit}>
                        <label className="block space-y-2">
                            <span className="text-sm font-medium text-[var(--foreground)]">
                                Phone number or email
                            </span>
                            <div className="flex items-center gap-3 rounded-2xl border border-[var(--border-strong)] bg-[var(--panel-soft)] px-4 py-3 transition focus-within:border-[var(--accent)]">
                                <Phone className="h-4 w-4 shrink-0 text-[var(--accent-strong)]" />
                                <input
                                    type="text"
                                    value={login}
                                    onChange={(event) =>
                                        setLogin(event.target.value)
                                    }
                                    placeholder="+91 9009909989"
                                    autoComplete="username"
                                    required
                                    className="w-full bg-transparent text-base text-[var(--foreground)] outline-none placeholder:text-[var(--muted)]"
                                />
                            </div>
                        </label>

                        <label className="block space-y-2">
                            <span className="text-sm font-medium text-[var(--foreground)]">
                                Password
                            </span>
                            <div className="flex items-center gap-3 rounded-2xl border border-[var(--border-strong)] bg-[var(--panel-soft)] px-4 py-3 transition focus-within:border-[var(--accent)]">
                                <KeyRound className="h-4 w-4 shrink-0 text-[var(--accent-strong)]" />
                                <input
                                    type="password"
                                    value={password}
                                    onChange={(event) =>
                                        setPassword(event.target.value)
                                    }
                                    placeholder="Enter your password"
                                    autoComplete="current-password"
                                    required
                                    className="w-full bg-transparent text-base text-[var(--foreground)] outline-none placeholder:text-[var(--muted)]"
                                />
                            </div>
                        </label>

                        {errorMessage ? (
                            <div className="rounded-2xl border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-200 dark:text-red-200">
                                {errorMessage}
                            </div>
                        ) : null}

                        <button
                            type="submit"
                            disabled={isSubmitting}
                            className="w-full rounded-2xl bg-[var(--accent)] px-4 py-3 text-sm font-semibold text-[var(--accent-contrast)] shadow-[0_18px_40px_rgba(44,143,183,0.35)] transition hover:-translate-y-0.5 hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0"
                        >
                            {isSubmitting ? "Signing in..." : "Sign in"}
                        </button>
                    </form>
                </div>
            </div>
        </section>
    );
}
