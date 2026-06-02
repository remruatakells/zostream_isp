declare namespace JSX {
    interface IntrinsicElements {
        [elemName: string]: any;
    }
}

declare module 'react' {
    export type FormEvent<T = Element> = {
        preventDefault(): void;
        currentTarget: T;
        target: T;
    };

    const React: any;
    export default React;
    export const StrictMode: any;
    export function useEffect(...args: any[]): any;
    export function useState<T>(initialState: T | (() => T)): [T, any];
}

declare module 'react-dom/client' {
    export type Root = {
        render(children: any): void;
        unmount(): void;
    };

    export function createRoot(container: Element | DocumentFragment): Root;
}

declare module 'react/jsx-runtime' {
    export const Fragment: any;
    export function jsx(type: any, props: any, key?: any): any;
    export function jsxs(type: any, props: any, key?: any): any;
}

declare module 'react/jsx-dev-runtime' {
    export const Fragment: any;
    export function jsxDEV(type: any, props: any, key: any, isStaticChildren: any, source: any, self: any): any;
}

declare module 'lucide-react' {
    export const MoonStar: any;
    export const Phone: any;
    export const ShieldCheck: any;
    export const SunMedium: any;
}

declare module 'recharts' {
    export const Cell: any;
    export const Pie: any;
    export const PieChart: any;
    export const ResponsiveContainer: any;
    export const Tooltip: any;
}

declare module 'react-toastify' {
    export const ToastContainer: any;
    export const toast: {
        success(message: string): void;
        error(message: string): void;
    };
}

declare module '*.css';
