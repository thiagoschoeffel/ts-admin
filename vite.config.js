import { defineConfig } from "vite";
import { fileURLToPath, URL } from "node:url";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    server: {
        host: "0.0.0.0",
        hmr: {
            // When running inside Docker the browser must connect to localhost,
            // not the container's internal IP. clientPort handles the case where
            // the host port differs from the container port (VITE_PORT override).
            host: "localhost",
            clientPort: parseInt(process.env.VITE_PORT ?? "5173"),
        },
        watch: {
            // Required for Docker on macOS/Windows: inotify events don't propagate
            // through Docker Desktop's VM, so Vite falls back to polling.
            usePolling: true,
            interval: 1000,
        },
    },
    resolve: {
        alias: {
            "lodash.isequal": "fast-deep-equal",
            "@": fileURLToPath(new URL("./resources/js", import.meta.url)),
        },
    },
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                // Inertia entry (mantém Blade intacto)
                "resources/js/inertia.js",
            ],
            // refresh: true only watches resources/views/** and routes/**.
            // Explicit config: adds app/** so controller/service changes also
            // trigger a full reload, and passes usePolling so this watcher
            // (vite-plugin-full-reload, independent from server.watch) works
            // inside Docker on macOS/Windows where inotify doesn't propagate.
            refresh: [
                {
                    paths: ["resources/views/**", "routes/**", "app/**"],
                    config: { usePolling: true, interval: 1000 },
                },
            ],
        }),
        vue(),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // Separa bibliotecas grandes em chunks específicos
                    "inertia-vendor": ["@inertiajs/vue3"],
                    "vue-vendor": ["vue"],
                    "heroicons-vendor": ["@heroicons/vue"],
                    "axios-vendor": ["axios"],
                    "ziggy-vendor": ["ziggy-js"],
                },
            },
        },
        chunkSizeWarningLimit: 1000, // Aumenta o limite de aviso para 1000kb
    },
});
