import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import { createMockMovie, createMockTag } from '@/__tests__/utils/test-utils';

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(() => ({
        props: {
            auth: { user: null },
        },
        url: '/',
    })),
    Link: {
        name: 'Link',
        template: '<a :href="href"><slot /></a>',
        props: ['href'],
    },
}));

import MovieHero from '../MovieHero.vue';

describe('MovieHero', () => {
    const defaultMovie = createMockMovie();

    function mountMovieHero(props = {}) {
        return mount(MovieHero, {
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
        it('renders movie title', () => {
            const wrapper = mountMovieHero();
            expect(wrapper.text()).toContain(defaultMovie.title);
        });

        it('renders movie synopsis', () => {
            const wrapper = mountMovieHero();
            expect(wrapper.text()).toContain(defaultMovie.synopsis);
        });

        it('renders release year', () => {
            const wrapper = mountMovieHero();
            expect(wrapper.text()).toContain(String(defaultMovie.release_year));
        });

        it('renders runtime when provided', () => {
            const wrapper = mountMovieHero();
            expect(wrapper.text()).toContain(`${defaultMovie.runtime} min`);
        });

        it('renders conflict when provided', () => {
            const wrapper = mountMovieHero();
            expect(wrapper.text()).toContain(defaultMovie.conflict);
        });

        it('renders poster image', () => {
            const wrapper = mountMovieHero();
            const images = wrapper.findAll('img');
            // Should have at least one poster image
            const posterImg = images.find(
                (img) => img.attributes('alt') === defaultMovie.title,
            );
            expect(posterImg).toBeDefined();
            expect(posterImg?.attributes('src')).toBe(defaultMovie.poster_url);
        });

        it('renders placeholder when no poster_url', () => {
            const movieWithoutPoster = createMockMovie({ poster_url: null });
            const wrapper = mountMovieHero({ movie: movieWithoutPoster });
            const images = wrapper.findAll('img');
            const posterImg = images.find(
                (img) => img.attributes('alt') === movieWithoutPoster.title,
            );
            expect(posterImg?.attributes('src')).toBe(
                '/images/placeholders/poster-placeholder.png',
            );
        });
    });

    describe('subtitle', () => {
        it('renders subtitle when provided', () => {
            const wrapper = mountMovieHero({ subtitle: 'Featured Film' });
            expect(wrapper.text()).toContain('Featured Film');
        });

        it('does not render subtitle when not provided', () => {
            const wrapper = mountMovieHero();
            // Check the HTML does not contain a subtitle element
            const subtitleElement = wrapper.find('.text-red-500.uppercase');
            expect(subtitleElement.exists()).toBe(false);
        });
    });

    describe('tags', () => {
        it('renders tags when provided', () => {
            const tags = [
                createMockTag({ id: 1, name: 'Action' }),
                createMockTag({ id: 2, name: 'War' }),
            ];
            const movieWithTags = createMockMovie({ tags });
            const wrapper = mountMovieHero({ movie: movieWithTags });

            expect(wrapper.text()).toContain('Action');
            expect(wrapper.text()).toContain('War');
        });

        it('does not render tags section when no tags', () => {
            const movieWithoutTags = createMockMovie({ tags: [] });
            const wrapper = mountMovieHero({ movie: movieWithoutTags });
            // Find all tag-like elements
            const tagElements = wrapper.findAll(
                '.rounded-full.bg-zinc-800\\/80',
            );
            expect(tagElements.length).toBe(0);
        });
    });

    describe('links', () => {
        it('links to movie detail page', () => {
            const wrapper = mountMovieHero();
            const detailsLink = wrapper
                .findAll('a')
                .find((a) => a.text().includes('VIEW DOSSIER'));
            expect(detailsLink).toBeDefined();
            expect(detailsLink?.attributes('href')).toBe(
                `/movies/${defaultMovie.slug}`,
            );
        });

        it('renders trailer link when trailer_url provided', () => {
            const wrapper = mountMovieHero();
            const trailerLink = wrapper
                .findAll('a')
                .find((a) => a.text().includes('Watch Trailer'));
            expect(trailerLink).toBeDefined();
            expect(trailerLink?.attributes('href')).toBe(
                defaultMovie.trailer_url,
            );
            expect(trailerLink?.attributes('target')).toBe('_blank');
        });

        it('does not render trailer link when no trailer_url', () => {
            const movieWithoutTrailer = createMockMovie({ trailer_url: null });
            const wrapper = mountMovieHero({ movie: movieWithoutTrailer });
            const trailerLink = wrapper
                .findAll('a')
                .find((a) => a.text().includes('Watch Trailer'));
            expect(trailerLink).toBeUndefined();
        });
    });

    describe('review (pick of week)', () => {
        it('renders rating and excerpt with read full review link when review provided', () => {
            const review = {
                rating: 4,
                content_excerpt:
                    'A stellar war film that captures the essence of combat.',
            };
            const wrapper = mountMovieHero({ review });

            expect(wrapper.text()).toContain(
                'A stellar war film that captures the essence of combat.',
            );
            expect(wrapper.text()).toContain('Read full review');
            const readMoreLink = wrapper
                .findAll('a')
                .find((a) => a.text().includes('Read full review'));
            expect(readMoreLink).toBeDefined();
            expect(readMoreLink?.attributes('href')).toBe(
                `/movies/${defaultMovie.slug}/reviews`,
            );
        });

        it('does not render review block when review not provided', () => {
            const wrapper = mountMovieHero();
            expect(wrapper.text()).not.toContain('Read full review');
        });

        it('links "Your review" to movie reviews page when userReview provided', () => {
            const userReview = { rating: 3 };
            const wrapper = mountMovieHero({ userReview });
            const yourReviewLink = wrapper
                .findAll('a')
                .find((a) => a.text().includes('Your review'));
            expect(yourReviewLink).toBeDefined();
            expect(yourReviewLink?.attributes('href')).toBe(
                `/movies/${defaultMovie.slug}/reviews`,
            );
        });
    });

    describe('conditional rendering', () => {
        it('does not render runtime when not provided', () => {
            const movieWithoutRuntime = createMockMovie({ runtime: null });
            const wrapper = mountMovieHero({ movie: movieWithoutRuntime });
            expect(wrapper.text()).not.toContain('min');
        });

        it('does not render conflict when not provided', () => {
            const movieWithoutConflict = createMockMovie({ conflict: null });
            const wrapper = mountMovieHero({ movie: movieWithoutConflict });
            // Should not have conflict badge
            expect(wrapper.text()).not.toContain('WWII');
        });
    });
});
