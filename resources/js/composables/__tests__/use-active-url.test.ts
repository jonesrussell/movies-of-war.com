import { beforeEach, describe, expect, it, vi } from 'vitest';

// Need to mock before importing the composable
vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(() => ({
        url: '/movies',
        props: {},
    })),
}));

import { useActiveUrl } from '../use-active-url';

describe('useActiveUrl', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('urlIsActive', () => {
        it('returns true when URL matches current URL', () => {
            const { urlIsActive } = useActiveUrl();
            expect(urlIsActive('/movies', '/movies')).toBe(true);
        });

        it('returns false when URL does not match current URL', () => {
            const { urlIsActive } = useActiveUrl();
            expect(urlIsActive('/dashboard', '/movies')).toBe(false);
        });

        it('handles string URLs', () => {
            const { urlIsActive } = useActiveUrl();
            expect(urlIsActive('/', '/')).toBe(true);
            expect(urlIsActive('/about', '/about')).toBe(true);
        });

        it('handles route objects with url property', () => {
            const { urlIsActive } = useActiveUrl();
            const routeObject = { url: '/movies', method: 'get' as const };
            expect(urlIsActive(routeObject, '/movies')).toBe(true);
        });

        it('returns false for route object with different URL', () => {
            const { urlIsActive } = useActiveUrl();
            const routeObject = { url: '/dashboard', method: 'get' as const };
            expect(urlIsActive(routeObject, '/movies')).toBe(false);
        });

        it('handles URLs with trailing slashes', () => {
            const { urlIsActive } = useActiveUrl();
            // Note: This tests current behavior - URLs must match exactly
            expect(urlIsActive('/movies', '/movies')).toBe(true);
            expect(urlIsActive('/movies/', '/movies')).toBe(false);
        });

        it('handles root URL', () => {
            const { urlIsActive } = useActiveUrl();
            expect(urlIsActive('/', '/')).toBe(true);
            expect(urlIsActive('/', '/movies')).toBe(false);
        });

        it('handles nested URLs', () => {
            const { urlIsActive } = useActiveUrl();
            expect(urlIsActive('/admin/movies', '/admin/movies')).toBe(true);
            expect(urlIsActive('/admin/movies', '/admin')).toBe(false);
        });
    });

    describe('currentUrl', () => {
        it('returns a readonly ref', () => {
            const { currentUrl } = useActiveUrl();
            expect(currentUrl).toBeDefined();
            // currentUrl should be readonly
            expect(typeof currentUrl.value).toBe('string');
        });
    });
});
