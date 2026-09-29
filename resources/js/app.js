import "./bootstrap";
import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { initPwa } from "./pwa";

// Initialize PWA (Service Worker & Install Handler)
initPwa();

createInertiaApp({
    title: (title) => `${title} - ${import.meta.env.VITE_APP_NAME ?? "App"}`,
    resolve: (name) => {
        if (name.includes("::")) {
            const [module, page] = name.split("::");
            return resolvePageComponent(
                `../../Modules/${module}/resources/assets/js/Pages/${page}.vue`,
                import.meta.glob(
                    "../../Modules/*/resources/assets/js/Pages/**/*.vue",
                ),
            );
        }

        return resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue"),
        );
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});
