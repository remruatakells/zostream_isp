export const LOGIN_ROUTE = "/login";
export const DASHBOARD_ROUTE = "/dashboard";
export const ALL_USER_ROUTE = "/all-user";
export const OPERATOR_ROUTE = "/operator";
export const MANAGEMENT_ROUTE = "/management";
export const DOWNLOAD_ROUTE = "/download";
export const PROFILE_ROUTE = "/profile";

export const protectedRoutes = [
    DASHBOARD_ROUTE,
    ALL_USER_ROUTE,
    OPERATOR_ROUTE,
    MANAGEMENT_ROUTE,
    DOWNLOAD_ROUTE,
    PROFILE_ROUTE,
] as const;

export function normalizeAppPath(pathname: string) {
    if (!pathname || pathname === "/") {
        return LOGIN_ROUTE;
    }

    return pathname;
}

export function isProtectedRoute(pathname: string) {
    return protectedRoutes.includes(pathname as (typeof protectedRoutes)[number]);
}

export function getRouteTitle(pathname: string) {
    switch (pathname) {
        case DASHBOARD_ROUTE:
            return "Dashboard";
        case ALL_USER_ROUTE:
            return "All User";
        case OPERATOR_ROUTE:
            return "Operator";
        case MANAGEMENT_ROUTE:
            return "Management";
        case DOWNLOAD_ROUTE:
            return "Download";
        case PROFILE_ROUTE:
            return "Profile";
        case LOGIN_ROUTE:
            return "Login";
        default:
            return "Dashboard";
    }
}
