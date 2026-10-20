import '../css/app.css';
import './bootstrap.js';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { toast } from 'vue-sonner';
import ToastProvider from './Components/ToastProvider.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const baseComponents = import.meta.glob('./Components/Base/*.vue', { eager: true });
const pageComponents = import.meta.glob('./Pages/**/*.vue');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            pageComponents,
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({
            render() {
                return h('div', null, [
                    h(ToastProvider),
                    h(App, props),
                ])
            }
        });

        Object.entries(baseComponents).forEach(([path, module]) => {
            app.component(path.split('/').pop().replace(/\.vue$/, ''), module.default);
        });

        return app
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: 'var(--color-secondary)',
    },
});

router.on('success', (event) => {
    const page = event?.detail?.page || event?.page;
    if (!page?.props) return;
    if (page.props.flash?.success) {
        toast.success(page.props.flash.success);
    }
    if (page.props.flash?.error) {
        toast.error(page.props.flash.error);
    }
    if (page.props.status) {
        toast(page.props.status);
    }
});

router.on('error', (event) => {
    const errors = event?.detail?.errors || event?.errors;
    if (!errors) return;
    const messages = Object.values(errors).flat().filter(Boolean);
    if (messages.length > 0) {
        toast.error(messages[0]);
    }
});
