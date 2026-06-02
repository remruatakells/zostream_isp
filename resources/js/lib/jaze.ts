export type JazeUsersCountStats = {
    total: number;
    active: number;
    online: number;
    expired: number;
    expiringNextWeek: number;
    newUsersLastWeek: number;
    blacklisted: number;
    suspended: number;
    blocked: number;
    pending: number;
    churned: number;
    others: number;
    frozen: number;
};

export type JazeUser = {
    id: string;
    username: string;
    account_id: string;
    last_name?: string;
    groupId?: string;
    status: string;
    name: string;
    phone?: string;
    email?: string;
    circuit_id?: string;
    address?: string;
    address_line1?: string;
    address_line2?: string;
    address_city?: string;
    address_pin?: string;
    address_state?: string;
    comments?: string;
    activationTime?: string;
    createdTime?: string;
    expirationTime?: string;
    installationTime?: string;
    billing_id?: string | null;
    type?: string;
    modified?: string;
    country_code?: string;
    alt_phone?: string;
    alt_email?: string;
    company_name?: string;
    Representative?: string;
    staticIp?: Array<{
        staticIpAddress?: string | null;
        staticIpBoundMac?: string | null;
    }>;
};

export type JazeUsersResponse = {
    status?: string;
    errorCode?: number;
    totalRecords?: number;
    data?: JazeUser[];
    message?: string;
};

export type JazeGroup = {
    Group_id: string;
    Group_name: string;
    Profile_id?: string;
    Profile_Name?: string;
    Active_Users?: number;
    Total_Users?: number;
    Online_Users?: number;
};

type JazeGroupsResponse = {
    status?: string;
    errorCode?: number;
    data?: JazeGroup[];
    message?: string;
};

type JazeAddUserResponse = {
    status?: string;
    errorCode?: number;
    message?:
        | string
        | {
              message?: string;
          };
    data?: unknown;
};

type JazeRenewResponse = {
    status?: string;
    errorCode?: number;
    message?:
        | string
        | {
              message?: string;
          };
    data?: unknown;
};

type JazeUserDetailsResponse = {
    status?: string;
    errorCode?: number;
    message?: string;
    data?: JazeUser | null;
};

type JazeUsersCountResponse = {
    status?: string;
    errorCode?: number;
    message?:
        | string
        | number
        | Partial<Record<keyof JazeUsersCountStats, number | string>>;
};

function toNumber(value: unknown): number {
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

function extractUsersCountStats(payload: unknown): JazeUsersCountStats | null {
    if (!payload || typeof payload !== "object") {
        return null;
    }

    const candidate = payload as JazeUsersCountResponse;

    if (!candidate.message || typeof candidate.message !== "object") {
        return null;
    }

    const message = candidate.message as Partial<
        Record<keyof JazeUsersCountStats, number | string>
    >;

    return {
        total: toNumber(message.total),
        active: toNumber(message.active),
        online: toNumber(message.online),
        expired: toNumber(message.expired),
        expiringNextWeek: toNumber(message.expiringNextWeek),
        newUsersLastWeek: toNumber(message.newUsersLastWeek),
        blacklisted: toNumber(message.blacklisted),
        suspended: toNumber(message.suspended),
        blocked: toNumber(message.blocked),
        pending: toNumber(message.pending),
        churned: toNumber(message.churned),
        others: toNumber(message.others),
        frozen: toNumber(message.frozen),
    };
}

export async function fetchUsersCount({
    accountId,
    token,
}: {
    accountId: string;
    token: string;
}): Promise<JazeUsersCountStats | null> {
    const url = new URL(
        `/api/jaze/users-count/${encodeURIComponent(accountId)}`,
        window.location.origin,
    );

    const response = await fetch(url.toString(), {
        headers: {
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
        },
    });

    const data = (await response.json().catch(() => null)) as
        | JazeUsersCountResponse
        | { message?: string }
        | null;

    if (!response.ok) {
        const errorMessage =
            data && typeof data === "object" && "message" in data
                ? data.message
                : undefined;

        throw new Error(errorMessage ?? "Unable to load dashboard stats.");
    }

    return extractUsersCountStats(data);
}

export async function fetchJazeUsers({
    token,
    page = 1,
    perPage = 10,
    status = "",
    branchCode = "",
}: {
    token: string;
    page?: number;
    perPage?: number;
    status?: string;
    branchCode?: string;
}): Promise<{
    totalRecords: number;
    users: JazeUser[];
}> {
    const url = new URL("/api/jaze/users", window.location.origin);

    url.searchParams.set("page", String(page));
    url.searchParams.set("per_page", String(perPage));

    if (status) {
        url.searchParams.set("status", status);
    }

    if (branchCode) {
        url.searchParams.set("branch_code", branchCode);
    }

    const response = await fetch(url.toString(), {
        headers: {
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
        },
    });

    const data = (await response.json().catch(() => null)) as
        | JazeUsersResponse
        | { message?: string }
        | null;

    if (!response.ok) {
        const errorMessage =
            data && typeof data === "object" && "message" in data
                ? data.message
                : undefined;

        throw new Error(errorMessage ?? "Unable to load Jaze users.");
    }

    return {
        totalRecords:
            data && typeof data === "object" && "totalRecords" in data
                ? Number(data.totalRecords) || 0
                : 0,
        users:
            data &&
            typeof data === "object" &&
            "data" in data &&
            Array.isArray(data.data)
                ? data.data
                : [],
    };
}

export async function fetchJazeUserDetails({
    token,
    branchCode = "",
    userId,
}: {
    token: string;
    branchCode?: string;
    userId: string;
}): Promise<JazeUser> {
    const url = new URL(
        `/api/jaze/users/${encodeURIComponent(userId)}`,
        window.location.origin,
    );

    if (branchCode) {
        url.searchParams.set("branch_code", branchCode);
    }

    const response = await fetch(url.toString(), {
        headers: {
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
        },
    });

    const data = (await response.json().catch(() => null)) as
        | JazeUserDetailsResponse
        | { message?: string }
        | null;

    if (!response.ok) {
        const errorMessage =
            data && typeof data === "object" && "message" in data
                ? data.message
                : undefined;

        throw new Error(errorMessage ?? "Unable to load subscriber details.");
    }

    const user =
        data &&
        typeof data === "object" &&
        "data" in data &&
        data.data &&
        typeof data.data === "object"
            ? (data.data as JazeUser)
            : null;

    if (!user) {
        throw new Error("Subscriber details were not returned by the server.");
    }

    return user;
}

export async function renewJazeUser({
    token,
    branchCode,
    userId,
    phone,
}: {
    token: string;
    branchCode: string;
    userId: string;
    phone?: string;
}): Promise<{ message: string }> {
    const payload = {
        branch_code: branchCode,
        userId,
        renewDefaultSettings: "true",
        isRenewPresentDate: "true",
        phone: phone ?? "",
    };

    console.log("Renew API payload:", payload);

    const response = await fetch("/api/jaze/renew", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify(payload),
    });

    const data = (await response.json().catch(() => null)) as
        | JazeRenewResponse
        | { message?: string }
        | null;

    const message =
        data && typeof data === "object" && "message" in data
            ? typeof data.message === "string"
                ? data.message
                : data.message &&
                    typeof data.message === "object" &&
                    "message" in data.message &&
                    typeof data.message.message === "string"
                  ? data.message.message
                  : undefined
            : undefined;

    if (!response.ok) {
        throw new Error(message ?? "Unable to renew subscriber.");
    }

    return {
        message: message ?? "Subscriber renewed successfully.",
    };
}

export async function fetchJazeGroups({
    token,
    branchCode = "",
}: {
    token: string;
    branchCode?: string;
}): Promise<JazeGroup[]> {
    const url = new URL("/api/jaze/groups", window.location.origin);

    if (branchCode) {
        url.searchParams.set("branch_code", branchCode);
    }

    const response = await fetch(url.toString(), {
        headers: {
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
        },
    });

    const data = (await response.json().catch(() => null)) as
        | JazeGroupsResponse
        | { message?: string }
        | null;

    if (!response.ok) {
        const errorMessage =
            data && typeof data === "object" && "message" in data
                ? data.message
                : undefined;

        throw new Error(errorMessage ?? "Unable to load Jaze groups.");
    }

    return data &&
        typeof data === "object" &&
        "data" in data &&
        Array.isArray(data.data)
        ? data.data
        : [];
}

export async function addJazeUser({
    token,
    branchCode,
    userGroupId,
    accountId,
    userName,
    password,
    phoneNumber,
    emailId,
    firstName,
    lastName,
    activationDate,
    expirationDate,
    customExpirationDate,
}: {
    token: string;
    branchCode: string;
    userGroupId: string;
    accountId: string;
    userName: string;
    password: string;
    phoneNumber: string;
    emailId: string;
    firstName: string;
    lastName: string;
    activationDate: string;
    expirationDate: string;
    customExpirationDate?: string;
}): Promise<{ message: string }> {
    const response = await fetch("/api/jaze/users", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
            branch_code: branchCode,
            userGroupId,
            accountId,
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
        }),
    });

    const data = (await response.json().catch(() => null)) as
        | JazeAddUserResponse
        | { message?: string }
        | null;

    const message =
        data && typeof data === "object" && "message" in data
            ? typeof data.message === "string"
                ? data.message
                : data.message &&
                    typeof data.message === "object" &&
                    "message" in data.message &&
                    typeof data.message.message === "string"
                  ? data.message.message
                  : undefined
            : undefined;

    if (!response.ok) {
        throw new Error(message ?? "Unable to add subscriber.");
    }

    return {
        message: message ?? "Subscriber added successfully.",
    };
}

export async function updateJazeUser({
    token,
    branchCode,
    userId,
    userGroupId,
    accountId,
    userName,
    phoneNumber,
    emailId,
    firstName,
    lastName,
    activationDate,
    expirationDate,
}: {
    token: string;
    branchCode: string;
    userId: string;
    userGroupId?: string;
    accountId?: string;
    userName: string;
    phoneNumber: string;
    emailId: string;
    firstName: string;
    lastName: string;
    activationDate?: string;
    expirationDate?: string;
}): Promise<{ message: string }> {
    const response = await fetch(
        `/api/jaze/users/${encodeURIComponent(userId)}`,
        {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                Authorization: `Bearer ${token}`,
            },
            body: JSON.stringify({
                branch_code: branchCode,
                userGroupId,
                accountId,
                userName,
                phoneNumber,
                emailId,
                firstName,
                lastName,
                activationDate,
                expirationDate,
            }),
        },
    );

    const data = (await response.json().catch(() => null)) as
        | JazeAddUserResponse
        | { message?: string }
        | null;

    const message =
        data && typeof data === "object" && "message" in data
            ? typeof data.message === "string"
                ? data.message
                : data.message &&
                    typeof data.message === "object" &&
                    "message" in data.message &&
                    typeof data.message.message === "string"
                  ? data.message.message
                  : undefined
            : undefined;

    if (!response.ok) {
        throw new Error(message ?? "Unable to update subscriber.");
    }

    return {
        message: message ?? "Subscriber updated successfully.",
    };
}
