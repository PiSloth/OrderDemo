import '../css/app.css';
import 'tippy.js/dist/tippy.css';

import '@fontsource/roboto/300.css';
import '@fontsource/roboto/400.css';
import '@fontsource/roboto/500.css';
import '@fontsource/roboto/700.css';

import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp, router } from '@inertiajs/react';

// Some sidebar links point to Blade/Livewire pages (non-Inertia). When an Inertia
// <Link> visits one of them, the server returns a full HTML document and Inertia
// would render it inside its iframe modal. Instead, fall back to a normal full
// page navigation for successful non-Inertia GET responses.
let lastVisit = null;
router.on('before', (event) => {
    lastVisit = event.detail.visit;
});
router.on('httpException', (event) => {
    const response = event.detail.response;
    const isInertia = response?.headers && response.headers['x-inertia'] !== undefined;
    const method = String(lastVisit?.method || 'get').toLowerCase();

    if (!isInertia && response?.status < 400 && method === 'get' && lastVisit?.url) {
        event.preventDefault();
        window.location.href = lastVisit.url.href ?? String(lastVisit.url);
    }
});

if (typeof window !== 'undefined' && typeof window.route !== 'undefined') {
    globalThis.route = window.route;
}

const appName = import.meta.env.VITE_APP_NAME || 'ShweTatar';
const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });

const el = document.getElementById('app');
let initialPage = null;

if (el && el.dataset && el.dataset.page) {
    try {
        initialPage = JSON.parse(el.dataset.page);
    } catch (e) {
        console.error('Failed to parse Inertia dataset page:', e);
    }
}

if (initialPage && initialPage.component) {
    createInertiaApp({
        page: initialPage,
        title: (title) => `${title} - ${appName}`,
        resolve: (name) => {
            const pageKey = `./Pages/${name}.jsx`;
            const page = pages[pageKey];
            if (!page) {
                console.error(`Inertia page component [${pageKey}] not found. Available pages:`, Object.keys(pages));
                throw new Error(`Inertia page component [${pageKey}] not found.`);
            }
            return page.default || page;
        },
        setup({ el, App, props }) {
            const root = createRoot(el);
            root.render(<App {...props} />);
        },
        progress: {
            color: '#3b82f6',
        },
    });
}
