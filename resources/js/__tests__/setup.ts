import { config } from '@vue/test-utils';
import { vi } from 'vitest';

// Mock window.location
Object.defineProperty(window, 'location', {
    value: {
        origin: 'http://localhost',
        pathname: '/',
        href: 'http://localhost/',
        search: '',
        hash: '',
    },
    writable: true,
});

// Mock Inertia's usePage
vi.mock('@inertiajs/vue3', async () => {
    const actual = await vi.importActual('@inertiajs/vue3');
    return {
        ...actual,
        usePage: vi.fn(() => ({
            props: {
                auth: { user: null },
            },
            url: '/',
        })),
        router: {
            get: vi.fn(),
            post: vi.fn(),
            put: vi.fn(),
            patch: vi.fn(),
            delete: vi.fn(),
            visit: vi.fn(),
            reload: vi.fn(),
        },
        Link: {
            name: 'Link',
            template: '<a :href="href"><slot /></a>',
            props: ['href'],
        },
        Head: {
            name: 'Head',
            template: '<div></div>',
            props: ['title'],
        },
    };
});

// Global component stubs
config.global.stubs = {
    Head: true,
    teleport: true,
};

// Mock global fetch for API calls (e.g., XFeedWidget)
global.fetch = vi.fn((url: string | URL | Request) => {
    let urlString: string;

    if (typeof url === 'string') {
        urlString = url;
    } else if (url instanceof URL) {
        urlString = url.toString();
    } else if (url instanceof Request) {
        urlString = url.url;
    } else {
        urlString = String(url);
    }

    // Mock X feed API endpoint
    if (urlString.includes('/api/x-feed')) {
        return Promise.resolve({
            ok: true,
            status: 200,
            json: async () => [],
            headers: new Headers(),
        } as Response);
    }

    // For other fetch calls, reject with a clear error
    return Promise.reject(new Error(`Unmocked fetch call to: ${urlString}`));
}) as typeof fetch;
