export type AdminBranch = {
    id?: number;
    name?: string | null;
    code?: string | null;
};

export type AdminUser = {
    id?: number;
    name?: string | null;
    phone?: string | null;
    email?: string | null;
    role: string | null;
    status: string;
    branch_id: number | null;
    branch?: AdminBranch | null;
};

export type AdminLoginResponse = {
    token: string;
    token_type: string;
    admin_user: AdminUser;
};

type ApiErrorResponse = {
    message?: string;
};

type AdminLoginPayload = {
    login: string;
    password: string;
};

export async function loginAdmin(
    payload: AdminLoginPayload,
): Promise<AdminLoginResponse> {
    const response = await fetch("/api/admin-login", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        body: JSON.stringify(payload),
    });

    const data = (await response.json().catch(() => null)) as
        | AdminLoginResponse
        | ApiErrorResponse
        | null;

    if (!response.ok) {
        const errorMessage =
            data && "message" in data ? data.message : undefined;

        throw new Error(errorMessage ?? "Unable to login right now.");
    }

    return data as AdminLoginResponse;
}

const TOKEN_STORAGE_KEY = "zostream-admin-token";
const BRANCH_STORAGE_KEY = "zostream-admin-branch";

export function persistAdminSession(session: AdminLoginResponse) {
    window.localStorage.setItem(TOKEN_STORAGE_KEY, session.token);
    window.localStorage.setItem(
        BRANCH_STORAGE_KEY,
        JSON.stringify({
            name: session.admin_user.branch?.name ?? null,
            code: session.admin_user.branch?.code ?? null,
        }),
    );
}

export function toCachedAdminSession(
    session: AdminLoginResponse,
): AdminLoginResponse {
    return {
        token: session.token,
        token_type: session.token_type,
        admin_user: {
            role: session.admin_user.role,
            status: session.admin_user.status,
            branch_id: session.admin_user.branch_id,
            branch: {
                name: session.admin_user.branch?.name ?? null,
                code: session.admin_user.branch?.code ?? null,
            },
        },
    };
}

export function clearAdminSession() {
    window.localStorage.removeItem(TOKEN_STORAGE_KEY);
    window.localStorage.removeItem(BRANCH_STORAGE_KEY);
}

export function getStoredAdminSession(): AdminLoginResponse | null {
    if (typeof window === "undefined") {
        return null;
    }

    try {
        const token = window.localStorage.getItem(TOKEN_STORAGE_KEY);
        const rawBranch = window.localStorage.getItem(BRANCH_STORAGE_KEY);

        if (!token || !rawBranch) {
            return null;
        }

        const branch = JSON.parse(rawBranch) as AdminBranch;

        return {
            token,
            token_type: "Bearer",
            admin_user: {
                role: null,
                status: "active",
                branch_id: null,
                branch,
            },
        };
    } catch {
        return null;
    }
}
