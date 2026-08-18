import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import type { DefineComponent } from 'vue';

const pages = import.meta.glob<{ default: DefineComponent }>('./Pages/**/*.vue');

void createInertiaApp({
    title: (title) => (title ? `${title} — EduXora` : 'EduXora'),
    resolve: async (name) => (await resolvePageComponent(`./Pages/${name}.vue`, pages)).default,
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#2457d6',
        showSpinner: false,
    },
});
