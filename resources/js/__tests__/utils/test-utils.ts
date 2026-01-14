import type { Movie, Tag, User } from '@/types/models';
import type { Component } from 'vue';

import { mount as vueMount } from '@vue/test-utils';
import { vi } from 'vitest';

/**
 * Creates a mock User object
 */
export function createMockUser(overrides: Partial<User> = {}): User {
    return {
        id: 1,
        name: 'Test User',
        email: 'test@example.com',
        is_admin: false,
        created_at: '2024-01-01T00:00:00.000000Z',
        updated_at: '2024-01-01T00:00:00.000000Z',
        ...overrides,
    };
}

/**
 * Creates a mock Tag object
 */
export function createMockTag(overrides: Partial<Tag> = {}): Tag {
    return {
        id: 1,
        name: 'Action',
        slug: 'action',
        type: 'genre',
        created_at: '2024-01-01T00:00:00.000000Z',
        updated_at: '2024-01-01T00:00:00.000000Z',
        ...overrides,
    };
}

/**
 * Creates a mock Movie object
 */
export function createMockMovie(overrides: Partial<Movie> = {}): Movie {
    return {
        id: 1,
        title: 'Test Movie',
        slug: 'test-movie',
        release_year: 2024,
        release_date: '2024-01-01',
        synopsis: 'A test movie synopsis for testing purposes.',
        runtime: 120,
        country: 'USA',
        conflict: 'WWII',
        poster_path: '/posters/test.jpg',
        poster_url: '/storage/posters/test.jpg',
        trailer_url: 'https://youtube.com/watch?v=test',
        imdb_id: 'tt1234567',
        is_upcoming: false,
        status: 'published',
        created_at: '2024-01-01T00:00:00.000000Z',
        updated_at: '2024-01-01T00:00:00.000000Z',
        tags: [],
        is_watchlisted: false,
        ...overrides,
    };
}

/**
 * Creates multiple mock movies
 */
export function createMockMovies(
    count: number,
    overrides: Partial<Movie> = {},
): Movie[] {
    return Array.from({ length: count }, (_, i) =>
        createMockMovie({
            id: i + 1,
            title: `Test Movie ${i + 1}`,
            slug: `test-movie-${i + 1}`,
            ...overrides,
        }),
    );
}

/**
 * Mock Inertia page props
 */
export interface MockPageProps {
    auth?: {
        user?: User | null;
    };
    [key: string]: unknown;
}

/**
 * Sets up mock Inertia page with custom props
 */
export async function mockInertiaPage(props: MockPageProps = {}) {
    const inertia = await import('@inertiajs/vue3');
    const mockedUsePage = vi.mocked(inertia.usePage);
    mockedUsePage.mockReturnValue({
        props: {
            auth: { user: null },
            ...props,
        },
        url: '/',
    } as ReturnType<typeof inertia.usePage>);
}

/**
 * Helper to mount components with common configuration
 */

export function mountWithDefaults(component: Component, options: any = {}) {
    return vueMount(component, {
        global: {
            stubs: {
                Head: true,
                Link: {
                    template: '<a :href="href"><slot /></a>',
                    props: ['href'],
                },
                teleport: true,
            },
            ...options.global,
        },
        ...options,
    });
}

/**
 * Helper to wait for Vue reactivity updates
 */
export async function flushPromises(): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, 0));
}

/**
 * Creates mock router functions
 */
export function createMockRouter() {
    return {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
        visit: vi.fn(),
        reload: vi.fn(),
    };
}
