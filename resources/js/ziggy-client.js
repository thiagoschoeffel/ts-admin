import { Ziggy as ZiggyStatic } from "./ziggy.js";
import { route as ziggyRoute } from "ziggy-js";

// Ziggy Route Helper for Laravel + Inertia
// Usage in Vue components:
// import { route } from '@/ziggy-client';
// <Link :href="route('users.index')">Users</Link>
// route('users.show', { user: 1 }) -> '/admin/users/1'
//
// The route helper automatically:
// - Uses runtime browser origin to avoid CORS issues
// - Defaults to relative URLs for Inertia compatibility
// - Provides better error messages in development

// Export Ziggy static (the generated config). We'll compute a runtime config
// that uses the browser's origin when available to avoid cross-origin URLs.
export { ZiggyStatic as Ziggy };

// Compute runtime config when calling route: if we're in a browser, prefer the
// current origin so generated absolute URLs match the page origin. This also
// prevents CORS issues when the generated Ziggy.url differs (e.g. 127.0.0.1 vs localhost).
const runtimeConfig =
    typeof window !== "undefined" && window.location
        ? { ...ZiggyStatic, url: window.location.origin }
        : ZiggyStatic;

// Export a wrapper that uses the runtime config. Default `absolute` to false so
// Inertia uses same-origin relative paths unless an absolute URL is specifically requested.
export const route = (name, params = {}, absolute = false) => {
    try {
        return ziggyRoute(name, params, absolute, runtimeConfig);
    } catch (error) {
        // Log error in development for debugging route issues
        if (process.env.NODE_ENV === "development") {
            console.warn(`Ziggy route error for "${name}":`, error.message);
        }
        // Return a fallback URL or throw the error
        throw error;
    }
};
