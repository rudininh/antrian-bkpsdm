import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import ServerIssueBanner from '@/Components/ServerIssueBanner.vue';
import { clearServerIssue, reportServerIssue, serverIssueState } from '@/utils/serverIssue';

const appName = import.meta.env.VITE_APP_NAME || 'Antrian BKPSDM';
const defaultServerIssueMessage = 'Server sedang bermasalah. Data terakhir tetap ditampilkan.';

createInertiaApp({
    title: (title) => (title ? `${title} | ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const showServerIssue = (message = defaultServerIssueMessage) => {
            reportServerIssue(message);
        };

        document.addEventListener('inertia:invalid', (event) => {
            event.preventDefault();
            showServerIssue();
        });

        document.addEventListener('inertia:exception', (event) => {
            event.preventDefault();
            showServerIssue();
        });

        document.addEventListener('inertia:success', () => {
            clearServerIssue();
        });

        document.addEventListener('inertia:navigate', () => {
            clearServerIssue();
        });

        return createApp({
            render: () =>
                h('div', [
                    h(App, props),
                    serverIssueState.active
                        ? h(ServerIssueBanner, {
                            message: serverIssueState.message || defaultServerIssueMessage,
                            onRetry: () => {
                                clearServerIssue();
                                window.location.reload();
                            },
                        })
                        : null,
                ]),
        })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#0f766e',
    },
});
