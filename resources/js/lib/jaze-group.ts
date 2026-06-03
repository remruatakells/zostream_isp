export type JazeGroup = {
    group_id: number;
    group_name: string;
    profile_id: string | null;
    profile_name: string | null;
    amount: string;
};

type ApiErrorResponse = {
    message?: unknown;
    errors?: Record<string, unknown>;
};

type LaravelPaginatorResponse<T> = {
    data?: T[];
};

type JazeGroupResponse = {
    group_id?: unknown;
    group_name?: unknown;
    profile_id?: unknown;
    profile_name?: unknown;
    amount?: unknown;
    Group_id?: unknown;
    Group_name?: unknown;
    Profile_id?: unknown;
    Profile_Name?: unknown;
    Amount?: unknown;
};

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === "object" && value !== null && !Array.isArray(value);
}

function toStringValue(value: unknown, fallback = ""): string {
    if (typeof value === "string") {
        return value;
    }

    if (typeof value === "number" && Number.isFinite(value)) {
        return String(value);
    }

    return fallback;
}

function toNumberValue(value: unknown): number {
    if (typeof value === "number" && Number.isFinite(value)) {
        return value;
    }

    if (typeof value === "string") {
        const parsed = Number(value);

        if (Number.isFinite(parsed)) {
            return parsed;
        }
    }

    return 0;
}

function toNullableString(value: unknown): string | null {
    if (value === null || value === undefined) {
        return null;
    }

    const normalized = toStringValue(value);

    return normalized.length > 0 ? normalized : null;
}

function extractErrorMessage(payload: unknown): string | undefined {
    if (!isRecord(payload)) {
        return undefined;
    }

    if (typeof payload.message === "string") {
        return payload.message;
    }

    if (isRecord(payload.message) && typeof payload.message.message === "string") {
        return payload.message.message;
    }

    if (isRecord(payload.errors)) {
        for (const error of Object.values(payload.errors)) {
            if (Array.isArray(error)) {
                const firstMessage = error.find(
                    (entry) => typeof entry === "string" && entry.trim().length > 0,
                );

                if (typeof firstMessage === "string") {
                    return firstMessage;
                }
            }

            if (typeof error === "string" && error.trim().length > 0) {
                return error;
            }
        }
    }

    return undefined;
}

function normalizeJazeGroup(payload: unknown): JazeGroup {
    const candidate = isRecord(payload) ? payload : {};

    return {
        group_id: toNumberValue(candidate.group_id ?? candidate.Group_id),
        group_name: toStringValue(candidate.group_name ?? candidate.Group_name),
        profile_id: toNullableString(candidate.profile_id ?? candidate.Profile_id),
        profile_name: toNullableString(
            candidate.profile_name ?? candidate.Profile_Name,
        ),
        amount: toStringValue(candidate.amount ?? candidate.Amount),
    };
}

async function fetchJazeGroupResponse<T>(
    path: string,
    token: string,
): Promise<T> {
    const response = await fetch(path, {
        headers: {
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
        },
    });

    const payload = (await response.json().catch(() => null)) as
        | ApiErrorResponse
        | T
        | null;

    if (!response.ok) {
        throw new Error(
            extractErrorMessage(payload) ?? "Unable to load WIFI plans.",
        );
    }

    return payload as T;
}

export async function fetchJazePlanGroups({
    token,
}: {
    token: string;
}): Promise<JazeGroup[]> {
    const payload = await fetchJazeGroupResponse<
        LaravelPaginatorResponse<JazeGroupResponse>
    >("/api/jaze-plans", token);

    return Array.isArray(payload?.data)
        ? payload.data.map((group) => normalizeJazeGroup(group))
        : [];
}

export async function fetchJazePlanGroup({
    token,
    groupId,
}: {
    token: string;
    groupId: number;
}): Promise<JazeGroup> {
    const payload = await fetchJazeGroupResponse<JazeGroupResponse>(
        `/api/jaze-plans/${encodeURIComponent(String(groupId))}`,
        token,
    );

    return normalizeJazeGroup(payload);
}
