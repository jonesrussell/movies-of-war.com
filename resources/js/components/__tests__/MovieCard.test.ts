import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { createMockMovie, createMockTag } from '@/__tests__/utils/test-utils';

// Mock Inertia - must be before any imports that use it
vi.mock('@inertiajs/vue3', () => ({
    Link: {
        name: 'Link',
        template: '<a :href="href"><slot /></a>',
        props: ['href'],
    },
}));

import MovieCard from '../MovieCard.vue';

describe('MovieCard', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    const defaultMovie = createMockMovie();

    function mountMovieCard(props = {}) {
        return mount(MovieCard, {
            props: {
                movie: defaultMovie,
                ...props,
            },
            global: {
                stubs: {
                    Link: {
                        template: '<a :href="href"><slot /></a>',
                        props: ['href'],
                    },
                },
            },
        });
    }

    describe('rendering', () => {
        it('renders movie poster', () => {
            const wrapper = mountMovieCard();
            const img = wrapper.find('img');
            expect(img.exists()).toBe(true);
            expect(img.attributes('src')).toBe(defaultMovie.poster_url);
            expect(img.attributes('alt')).toBe(defaultMovie.title);
        });

        it('renders placeholder when no poster_url', () => {
            const movieWithoutPoster = createMockMovie({ poster_url: null });
            const wrapper = mountMovieCard({ movie: movieWithoutPoster });
            const img = wrapper.find('img');
            expect(img.attributes('src')).toBe(
                '/images/placeholders/poster-placeholder.png',
            );
        });

        it('renders movie title in hover overlay', () => {
            const wrapper = mountMovieCard();
            expect(wrapper.text()).toContain(defaultMovie.title);
        });

        it('renders release year', () => {
            const wrapper = mountMovieCard();
            expect(wrapper.text()).toContain(String(defaultMovie.release_year));
        });

        it('renders runtime when provided', () => {
            const wrapper = mountMovieCard();
            expect(wrapper.text()).toContain(`${defaultMovie.runtime} min`);
        });

        it('renders country when provided', () => {
            const wrapper = mountMovieCard();
            expect(wrapper.text()).toContain(defaultMovie.country);
        });

        it('renders synopsis when provided', () => {
            const wrapper = mountMovieCard();
            expect(wrapper.text()).toContain(defaultMovie.synopsis);
        });

        it('links to movie detail page', () => {
            const wrapper = mountMovieCard();
            const link = wrapper.find('a');
            expect(link.attributes('href')).toBe(
                `/movies/${defaultMovie.slug}`,
            );
        });
    });

    describe('tags', () => {
        it('renders tags when provided', () => {
            const tags = [
                createMockTag({ id: 1, name: 'Action', slug: 'action' }),
                createMockTag({ id: 2, name: 'Drama', slug: 'drama' }),
            ];
            const movieWithTags = createMockMovie({ tags });
            const wrapper = mountMovieCard({ movie: movieWithTags });

            expect(wrapper.text()).toContain('Action');
            expect(wrapper.text()).toContain('Drama');
        });

        it('limits displayed tags to 3', () => {
            const tags = [
                createMockTag({ id: 1, name: 'Action' }),
                createMockTag({ id: 2, name: 'Drama' }),
                createMockTag({ id: 3, name: 'War' }),
                createMockTag({ id: 4, name: 'Historical' }),
            ];
            const movieWithManyTags = createMockMovie({ tags });
            const wrapper = mountMovieCard({ movie: movieWithManyTags });

            expect(wrapper.text()).toContain('Action');
            expect(wrapper.text()).toContain('Drama');
            expect(wrapper.text()).toContain('War');
            expect(wrapper.text()).not.toContain('Historical');
        });

        it('does not render tags section when no tags', () => {
            const movieWithoutTags = createMockMovie({ tags: [] });
            const wrapper = mountMovieCard({ movie: movieWithoutTags });
            const tagElements = wrapper.findAll('[class*="bg-zinc-800/80"]');
            expect(tagElements.length).toBe(0);
        });
    });

    describe('upcoming badge', () => {
        it('shows Coming Soon badge for upcoming movies', () => {
            const upcomingMovie = createMockMovie({ is_upcoming: true });
            const wrapper = mountMovieCard({ movie: upcomingMovie });
            expect(wrapper.text()).toContain('Coming Soon');
        });

        it('does not show Coming Soon badge for released movies', () => {
            const releasedMovie = createMockMovie({ is_upcoming: false });
            const wrapper = mountMovieCard({ movie: releasedMovie });
            expect(wrapper.text()).not.toContain('Coming Soon');
        });
    });

    describe('conditional rendering', () => {
        it('does not render runtime when not provided', () => {
            const movieWithoutRuntime = createMockMovie({ runtime: null });
            const wrapper = mountMovieCard({ movie: movieWithoutRuntime });
            expect(wrapper.text()).not.toContain('min');
        });

        it('does not render country when not provided', () => {
            const movieWithoutCountry = createMockMovie({ country: null });
            const wrapper = mountMovieCard({ movie: movieWithoutCountry });
            // Check that USA (default) is not in the text
            const movie = createMockMovie({ country: null });
            expect(wrapper.text()).not.toContain(movie.country);
        });
    });
});
