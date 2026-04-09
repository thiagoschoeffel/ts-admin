import { Ziggy, route } from "./ziggy-client.js";
import { ZiggyVue } from "ziggy-js";
// Ensure a global Ziggy exists for any code (or third-party libs) that expect it on window.
window.Ziggy = Ziggy;

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import VueApexCharts from "vue3-apexcharts";

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        // Use Ziggy's Vue plugin to register `route` as a global property and provide it
        // for injection; pass our generated Ziggy object to the plugin.
        app.use(ZiggyVue, Ziggy);
        // (no dev-only logging here)
        // Also register our wrapper as a global property to ensure consistent behavior
        // in templates and scripts that call `route(...)` directly.
        app.config.globalProperties.route = route;
        app.use(VueApexCharts);
        app.use(plugin).mount(el);
    },
    progress: {
        color: "#2563eb",
    },
});
