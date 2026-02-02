import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import {
    createMockMovie,
    createMockMovies,
} from '@/__tests__/utils/test-utils';

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(() => ({
        props: {
            auth: { user: null },
        },
        url: '/',
    })),
    Head: {
        name: 'Head',
        template: '<div></div>',
        props: ['title'],
    },
    Link: {
        name: 'Link',
        template: '<a :href="href"><slot /></a>',
        props: ['href'],
    },
}));

// Mock child components for isolation
vi.mock('@/components/MovieHero.vue', () => ({
    default: {
        name: 'MovieHero',
        template:
            '<div data-testid="movie-hero">{{ movie.title }} - {{ subtitle }}</div>',
        props: ['movie', 'subtitle', 'review'],
    },
}));

vi.mock('@/components/FeaturedMovie.vue', () => ({
    default: {
        name: 'FeaturedMovie',
        template:
            '<div data-testid="featured-movie">{{ movie.title }} - {{ title }}</div>',
        props: ['movie', 'title'],
    },
}));

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

import Welcome from '../Welcome.vue';

describe('Welcome', () => {
    function mountWelcome(props: any = {}) {
        return mount(Welcome, {
            props: {
                latestMovies: [],
                upcomingMovies: [],
                ...props,
            },
            global: {
                stubs: {
                    Head: true,
                },
            },
        });
    }

    describe('pick of week section (hero style)', () => {
        it('renders MovieHero when pickOfWeekMovie is provided', () => {
            const pickOfWeekMovie = createMockMovie({ title: 'Weekly Pick' });
            const wrapper = mountWelcome({ pickOfWeekMovie });

            expect(wrapper.find('[data-testid="movie-hero"]').exists()).toBe(
                true,
            );
            expect(wrapper.text()).toContain('Weekly Pick');
            expect(wrapper.text()).toContain('Pick of the Week');
        });

        it('does not render MovieHero when no pickOfWeekMovie', () => {
            const wrapper = mountWelcome();

            expect(wrapper.find('[data-testid="movie-hero"]').exists()).toBe(
                false,
            );
        });
    });

    describe('featured upcoming release section (card style)', () => {
        it('renders FeaturedMovie when heroMovie is provided', () => {
            const heroMovie = createMockMovie({ title: 'Upcoming Film' });
            const wrapper = mountWelcome({ heroMovie });

            expect(
                wrapper.find('[data-testid="featured-movie"]').exists(),
            ).toBe(true);
            expect(wrapper.text()).toContain('Upcoming Film');
            expect(wrapper.text()).toContain('Featured Upcoming Release');
        });

        it('renders fallback when no heroMovie and no pickOfWeekMovie', () => {
            const wrapper = mountWelcome();

            expect(wrapper.text()).toContain('Movies of War');
            expect(wrapper.text()).toContain('A curated database of war films');
        });
    });

    describe('latest movies section', () => {
        it('renders latest movies grid', () => {
            const latestMovies = createMockMovies(6);
            const wrapper = mountWelcome({ latestMovies });

            const movieCards = wrapper.findAll('[data-testid="movie-card"]');
            expect(movieCards.length).toBe(6);
        });

        it('renders section heading', () => {
            const latestMovies = createMockMovies(3);
            const wrapper = mountWelcome({ latestMovies });

            expect(wrapper.text()).toContain('Latest Releases');
        });

        it('renders View All link', () => {
            const latestMovies = createMockMovies(3);
            const wrapper = mountWelcome({ latestMovies });

            const viewAllLink = wrapper
                .findAll('a')
                .find((a) => a.text().toLowerCase().includes('view all'));
            expect(viewAllLink).toBeDefined();
            expect(viewAllLink?.attributes('href')).toBe('/movies');
        });

        it('renders empty grid when no movies', () => {
            const wrapper = mountWelcome({ latestMovies: [] });

            const movieCards = wrapper.findAll('[data-testid="movie-card"]');
            expect(movieCards.length).toBe(0);
        });
    });

    describe('upcoming movies section', () => {
        it('renders upcoming movies grid when movies are provided', () => {
            const upcomingMovies = createMockMovies(4);
            const wrapper = mountWelcome({ upcomingMovies });

            expect(wrapper.text()).toContain('Upcoming');
            expect(wrapper.text()).toContain('War films on the horizon');
        });

        it('does not render upcoming section when no upcoming movies', () => {
            const wrapper = mountWelcome({ upcomingMovies: [] });

            expect(wrapper.text()).not.toContain('War films on the horizon');
        });

        it('renders View All link for upcoming movies', () => {
            const upcomingMovies = createMockMovies(3);
            const wrapper = mountWelcome({ upcomingMovies });

            const viewAllLinks = wrapper
                .findAll('a')
                .filter((a) => a.attributes('href') === '/movies?upcoming=1');
            expect(viewAllLinks.length).toBeGreaterThan(0);
        });
    });

    describe('call to action section', () => {
        it('renders CTA section', () => {
            const wrapper = mountWelcome();

            expect(wrapper.text()).toContain('Explore the curated collection');
            expect(wrapper.text()).toContain('Filter by conflict');
        });

        it('renders Browse All Movies link', () => {
            const wrapper = mountWelcome();

            const browseLink = wrapper
                .findAll('a')
                .find((a) => a.text().toLowerCase().includes('browse all'));
            expect(browseLink).toBeDefined();
            expect(browseLink?.attributes('href')).toBe('/movies');
        });
    });

    describe('full page rendering', () => {
        it('renders complete page with all sections', () => {
            const heroMovie = createMockMovie({ id: 1, title: 'Hero Movie' });
            const pickOfWeekMovie = createMockMovie({
                id: 2,
                title: 'Pick Movie',
            });
            const latestMovies = createMockMovies(4);

            const wrapper = mountWelcome({
                heroMovie,
                pickOfWeekMovie,
                latestMovies,
            });

            // Pick of week (MovieHero, first)
            expect(wrapper.find('[data-testid="movie-hero"]').exists()).toBe(
                true,
            );
            expect(wrapper.text()).toContain('Pick Movie');

            // Featured upcoming (FeaturedMovie, second)
            expect(
                wrapper.find('[data-testid="featured-movie"]').exists(),
            ).toBe(true);
            expect(wrapper.text()).toContain('Hero Movie');

            // Latest movies
            expect(wrapper.findAll('[data-testid="movie-card"]').length).toBe(
                4,
            );

            // CTA
            expect(wrapper.text()).toContain('Browse all movies');
        });

        it('renders minimal page without optional content', () => {
            const wrapper = mountWelcome();

            // Fallback hero
            expect(wrapper.text()).toContain('Movies of War');

            // No pick of week, no featured upcoming
            expect(wrapper.find('[data-testid="movie-hero"]').exists()).toBe(
                false,
            );
            expect(
                wrapper.find('[data-testid="featured-movie"]').exists(),
            ).toBe(false);

            // No movie cards
            expect(wrapper.findAll('[data-testid="movie-card"]').length).toBe(
                0,
            );

            // CTA still shows
            expect(wrapper.text()).toContain('Browse all movies');
        });
    });
});
