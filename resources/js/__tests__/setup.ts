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
