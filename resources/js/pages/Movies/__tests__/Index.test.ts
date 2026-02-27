import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import {
    createMockMovie,
    createMockMovies,
    createMockTag,
} from '@/__tests__/utils/test-utils';

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(() => ({
        props: {
            auth: { user: null },
        },
        url: '/movies',
    })),
    Head: {
        name: 'Head',
        template: '<div></div>',
        props: ['title'],
    },
    Link: {
        name: 'Link',
        template: '<a :href="href"><slot /></a>',
        props: ['href', 'preserveScroll', 'preserveState'],
    },
    router: {
        get: vi.fn(),
    },
}));

// Mock VueUse - handle different storage keys appropriately
vi.mock('@vueuse/core', async (importOriginal) => {
    const actual = (await importOriginal()) as Record<string, unknown>;
    return {
        ...actual,
        useDebounceFn: vi.fn((fn) => fn),
        useStorage: vi.fn((key: string, defaultValue: unknown) => {
            // Return appropriate default values for different keys
            if (key === 'movies-infinite-scroll') {
                return ref(false); // Disable infinite scroll to enable pagination
            }
            return ref(defaultValue); // Use default value for other keys (like view mode)
        }),
    };
});

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button type="button"><slot /></button>',
        props: ['variant', 'class', 'asChild'],
    },
}));

vi.mock('@/components/ui/sheet', () => ({
    Sheet: {
        name: 'Sheet',
        template: '<div><slot /></div>',
        props: ['open'],
    },
    SheetTrigger: {
        name: 'SheetTrigger',
        template: '<div><slot /></div>',
        props: ['asChild'],
    },
    SheetContent: {
        name: 'SheetContent',
        template: '<div><slot /></div>',
        props: ['side', 'class'],
    },
    SheetHeader: {
        name: 'SheetHeader',
        template: '<div><slot /></div>',
    },
    SheetTitle: {
        name: 'SheetTitle',
        template: '<div><slot /></div>',
    },
}));

// Mock child components
vi.mock('@/components/MovieCard.vue', () => ({
    default: {
        name: 'MovieCard',
        template: '<div data-testid="movie-card">{{ movie.title }}</div>',
        props: ['movie'],
    },
}));

vi.mock('@/layouts/PublicLayout.vue', () => ({
    default: {
        name: 'PublicLayout',
        template: '<div><slot /></div>',
    },
}));

import MoviesIndex from '../Index.vue';

describe('Movies/Index', () => {
    const defaultFilters = {
        conflicts: ['WWI', 'WWII', 'Vietnam'],
        countries: ['USA', 'UK', 'Germany'],
        eraTags: [
            createMockTag({ id: 10, name: 'WWII', slug: 'wwii' }),
            createMockTag({ id: 11, name: 'Vietnam War', slug: 'vietnam-war' }),
        ],
        tags: [
            createMockTag({ id: 1, name: 'Action', slug: 'action' }),
            createMockTag({ id: 2, name: 'Drama', slug: 'drama' }),
        ],
        years: [2024, 2023, 2022, 2021],
    };

    const defaultPaginatedMovies = {
        data: [],
        links: {
            first: null,
            last: null,
            prev: null,
            next: null,
        },
        meta: {
            current_page: 1,
            from: null,
            last_page: 1,
            links: [],
            path: '/movies',
            per_page: 12,
            to: null,
            total: 0,
        },
    };

    const defaultQueryParams = {};

    function mountMoviesIndex(props = {}) {
        return mount(MoviesIndex, {
            props: {
                filters: defaultFilters,
                movies: defaultPaginatedMovies,
                queryParams: defaultQueryParams,
                ...props,
            },
            global: {
                stubs: {
                    Head: true,
                },
            },
        });
    }

    describe('page header', () => {
        it('renders page title and kicker', () => {
            const wrapper = mountMoviesIndex();
            expect(wrapper.text()).toContain('Browse');
            expect(wrapper.text()).toContain('War Films');
        });

        it('displays total movie count', () => {
            const movies = {
                ...defaultPaginatedMovies,
                meta: { ...defaultPaginatedMovies.meta, total: 42 },
            };
            const wrapper = mountMoviesIndex({ movies });
            expect(wrapper.text()).toContain('OF 42');
        });
    });

    describe('search functionality', () => {
        it('renders search input', () => {
            const wrapper = mountMoviesIndex();
            const searchInput = wrapper.find('input[type="text"]');
            expect(searchInput.exists()).toBe(true);
            expect(searchInput.attributes('placeholder')).toContain(
                'SEARCH FILMS...',
            );
        });

        it('displays existing search value from queryParams', () => {
            const wrapper = mountMoviesIndex({
                queryParams: { search: 'saving private' },
            });
            const searchInput = wrapper.find('input[type="text"]');
            expect((searchInput.element as HTMLInputElement).value).toBe(
                'saving private',
            );
        });
    });

    describe('filter panel', () => {
        it('renders filter toggle button', () => {
            const wrapper = mountMoviesIndex();
            const filterButton = wrapper
                .findAll('button')
                .find((b) => b.text().includes('Filters'));
            expect(filterButton).toBeDefined();
        });

        it('shows filter panel when toggled', async () => {
            const wrapper = mountMoviesIndex();
            const filterButton = wrapper
                .findAll('button')
                .find((b) => b.text().includes('Filters'));

            await filterButton?.trigger('click');

            // Should show filter panel with shadcn Select trigger buttons
            const triggers = wrapper.findAll('[data-slot="select-trigger"]');
            expect(triggers.length).toBeGreaterThan(0);
        });

        it('renders year filter trigger', async () => {
            const wrapper = mountMoviesIndex();
            const filterButton = wrapper
                .findAll('button')
                .find((b) => b.text().includes('Filters'));
            await filterButton?.trigger('click');

            // Verify the Year label and its Select trigger exist
            expect(wrapper.text()).toContain('Year');
            const triggers = wrapper.findAll('[data-slot="select-trigger"]');
            const yearTrigger = triggers.find((t) =>
                t.text().includes('All years'),
            );
            expect(yearTrigger).toBeDefined();
        });

        it('renders country filter trigger', async () => {
            const wrapper = mountMoviesIndex();
            const filterButton = wrapper
                .findAll('button')
                .find((b) => b.text().includes('Filters'));
            await filterButton?.trigger('click');

            // Verify the Country label and its Select trigger exist
            expect(wrapper.text()).toContain('Country');
            const triggers = wrapper.findAll('[data-slot="select-trigger"]');
            const countryTrigger = triggers.find((t) =>
                t.text().includes('All countries'),
            );
            expect(countryTrigger).toBeDefined();
        });

        it('renders conflict filter trigger', async () => {
            const wrapper = mountMoviesIndex();
            const filterButton = wrapper
                .findAll('button')
                .find((b) => b.text().includes('Filters'));
            await filterButton?.trigger('click');

            // Verify the Conflict label and its Select trigger exist
            expect(wrapper.text()).toContain('Conflict');
            const triggers = wrapper.findAll('[data-slot="select-trigger"]');
            const conflictTrigger = triggers.find((t) =>
                t.text().includes('All conflicts'),
            );
            expect(conflictTrigger).toBeDefined();
        });

        it('renders tag filter trigger', async () => {
            const wrapper = mountMoviesIndex();
            const filterButton = wrapper
                .findAll('button')
                .find((b) => b.text().includes('Filters'));
            await filterButton?.trigger('click');

            // Verify the Tag label and its Select trigger exist
            expect(wrapper.text()).toContain('Tag');
            const triggers = wrapper.findAll('[data-slot="select-trigger"]');
            const tagTrigger = triggers.find((t) =>
                t.text().includes('All tags'),
            );
            expect(tagTrigger).toBeDefined();
        });
    });

    describe('active filters indicator', () => {
        it('shows active filter count badge', () => {
            const wrapper = mountMoviesIndex({
                queryParams: { search: 'test', year: 2024 },
            });
            // The badge should show the count of active filters
            expect(wrapper.text()).toContain('2');
        });

        it('shows clear button when filters are active', () => {
            const wrapper = mountMoviesIndex({
                queryParams: { search: 'test' },
            });
            const clearButton = wrapper
                .findAll('button')
                .find((b) => b.text().includes('Clear'));
            expect(clearButton).toBeDefined();
        });

        it('does not show clear button in header when no filters active', () => {
            // When there are movies but no filters, the Clear button in the header area shouldn't show
            const movies = {
                ...defaultPaginatedMovies,
                data: createMockMovies(3),
                meta: { ...defaultPaginatedMovies.meta, total: 3 },
            };
            const wrapper = mountMoviesIndex({
                queryParams: {},
                movies,
            });
            // The header clear button is next to the Filters button and has zinc styling
            // It should not appear when no filters are active
            const headerClearButton = wrapper
                .findAll('button')
                .find(
                    (b) =>
                        b.text().includes('Clear') &&
                        b.classes().some((c) => c.includes('zinc')),
                );
            expect(headerClearButton).toBeUndefined();
        });
    });

    describe('movie grid', () => {
        it('renders movies when data is available', () => {
            const movieData = createMockMovies(6);
            const movies = {
                ...defaultPaginatedMovies,
                data: movieData,
                meta: { ...defaultPaginatedMovies.meta, total: 6 },
            };
            const wrapper = mountMoviesIndex({ movies });

            const movieCards = wrapper.findAll('[data-testid="movie-card"]');
            expect(movieCards.length).toBe(6);
        });

        it('renders correct movie titles', () => {
            const movieData = [
                createMockMovie({ id: 1, title: 'Saving Private Ryan' }),
                createMockMovie({ id: 2, title: 'Apocalypse Now' }),
            ];
            const movies = {
                ...defaultPaginatedMovies,
                data: movieData,
                meta: { ...defaultPaginatedMovies.meta, total: 2 },
            };
            const wrapper = mountMoviesIndex({ movies });

            expect(wrapper.text()).toContain('Saving Private Ryan');
            expect(wrapper.text()).toContain('Apocalypse Now');
        });
    });

    describe('empty state', () => {
        it('shows empty state when no movies', () => {
            const wrapper = mountMoviesIndex({
                movies: defaultPaginatedMovies,
            });

            expect(wrapper.text()).toContain('No movies found');
        });

        it('shows clear filters option in empty state', () => {
            const wrapper = mountMoviesIndex({
                movies: defaultPaginatedMovies,
            });

            const clearButton = wrapper
                .findAll('button')
                .find((b) => b.text().includes('Clear'));
            expect(clearButton).toBeDefined();
        });

        it('shows empty state image', () => {
            const wrapper = mountMoviesIndex({
                movies: defaultPaginatedMovies,
            });

            const emptyImage = wrapper.find('img[src*="no-movies-found"]');
            expect(emptyImage.exists()).toBe(true);
        });
    });

    describe('pagination', () => {
        it('renders pagination when multiple pages exist', () => {
            const movies = {
                ...defaultPaginatedMovies,
                data: createMockMovies(12),
                meta: {
                    ...defaultPaginatedMovies.meta,
                    current_page: 1,
                    last_page: 3,
                    total: 36,
                    links: [
                        { url: null, label: '&laquo; Previous', active: false },
                        { url: '/movies?page=1', label: '1', active: true },
                        { url: '/movies?page=2', label: '2', active: false },
                        { url: '/movies?page=3', label: '3', active: false },
                        {
                            url: '/movies?page=2',
                            label: 'Next &raquo;',
                            active: false,
                        },
                    ],
                },
            };
            const wrapper = mountMoviesIndex({ movies });

            // Should have pagination links
            const paginationLinks = wrapper.findAll('a').filter((a) => {
                const href = a.attributes('href');
                return href?.includes('page=') || href === '#';
            });
            expect(paginationLinks.length).toBeGreaterThan(0);
        });

        it('does not render pagination for single page', () => {
            const movies = {
                ...defaultPaginatedMovies,
                data: createMockMovies(5),
                meta: {
                    ...defaultPaginatedMovies.meta,
                    current_page: 1,
                    last_page: 1,
                    total: 5,
                    links: [],
                },
            };
            const wrapper = mountMoviesIndex({ movies });

            // Should not have page number links
            const pageLinks = wrapper.findAll('a').filter((a) => {
                const href = a.attributes('href');
                return href?.includes('page=');
            });
            expect(pageLinks.length).toBe(0);
        });
    });
});
