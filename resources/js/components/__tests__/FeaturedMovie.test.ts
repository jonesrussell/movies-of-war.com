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

import FeaturedMovie from '../FeaturedMovie.vue';

describe('FeaturedMovie', () => {
    const defaultMovie = createMockMovie();

    function mountFeaturedMovie(props = {}) {
        return mount(FeaturedMovie, {
            props: {
                movie: defaultMovie,
                title: 'Pick of the Week',
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
        it('renders the title badge', () => {
            const wrapper = mountFeaturedMovie();
            expect(wrapper.text()).toContain('Pick of the Week');
        });

        it('renders custom title', () => {
            const wrapper = mountFeaturedMovie({ title: 'Editors Choice' });
            expect(wrapper.text()).toContain('Editors Choice');
        });

        it('renders movie title', () => {
            const wrapper = mountFeaturedMovie();
            expect(wrapper.text()).toContain(defaultMovie.title);
        });

        it('renders movie synopsis', () => {
            const wrapper = mountFeaturedMovie();
            expect(wrapper.text()).toContain(defaultMovie.synopsis);
        });

        it('renders release year', () => {
            const wrapper = mountFeaturedMovie();
            expect(wrapper.text()).toContain(String(defaultMovie.release_year));
        });

        it('renders runtime when provided', () => {
            const wrapper = mountFeaturedMovie();
            expect(wrapper.text()).toContain(`${defaultMovie.runtime} min`);
        });

        it('renders country when provided', () => {
            const wrapper = mountFeaturedMovie();
            expect(wrapper.text()).toContain(defaultMovie.country);
        });

        it('renders poster image', () => {
            const wrapper = mountFeaturedMovie();
            const img = wrapper.find('img');
            expect(img.exists()).toBe(true);
            expect(img.attributes('src')).toBe(defaultMovie.poster_url);
            expect(img.attributes('alt')).toBe(defaultMovie.title);
        });

        it('renders placeholder when no poster_url', () => {
            const movieWithoutPoster = createMockMovie({ poster_url: null });
            const wrapper = mountFeaturedMovie({ movie: movieWithoutPoster });
            const img = wrapper.find('img');
            expect(img.attributes('src')).toBe(
                '/images/placeholders/poster-placeholder.png',
            );
        });
    });

    describe('tags', () => {
        it('renders tags when provided', () => {
            const tags = [
                createMockTag({ id: 1, name: 'Action' }),
                createMockTag({ id: 2, name: 'War' }),
            ];
            const movieWithTags = createMockMovie({ tags });
            const wrapper = mountFeaturedMovie({ movie: movieWithTags });

            expect(wrapper.text()).toContain('Action');
            expect(wrapper.text()).toContain('War');
        });

        it('limits displayed tags to 3', () => {
            const tags = [
                createMockTag({ id: 1, name: 'Action' }),
                createMockTag({ id: 2, name: 'Drama' }),
                createMockTag({ id: 3, name: 'War' }),
                createMockTag({ id: 4, name: 'Historical' }),
            ];
            const movieWithManyTags = createMockMovie({ tags });
            const wrapper = mountFeaturedMovie({ movie: movieWithManyTags });

            expect(wrapper.text()).toContain('Action');
            expect(wrapper.text()).toContain('Drama');
            expect(wrapper.text()).toContain('War');
            expect(wrapper.text()).not.toContain('Historical');
        });

        it('does not render tags section when no tags', () => {
            const movieWithoutTags = createMockMovie({ tags: [] });
            const wrapper = mountFeaturedMovie({ movie: movieWithoutTags });
            const tagElements = wrapper.findAll('.bg-zinc-800');
            // Filter to only tag elements (small rounded ones)
            const smallTags = tagElements.filter((el) =>
                el.classes().includes('text-xs'),
            );
            expect(smallTags.length).toBe(0);
        });
    });

    describe('links', () => {
        it('links to movie detail page', () => {
            const wrapper = mountFeaturedMovie();
            const detailsLink = wrapper
                .findAll('a')
                .find((a) => a.text().includes('Details'));
            expect(detailsLink).toBeDefined();
            expect(detailsLink?.attributes('href')).toBe(
                `/movies/${defaultMovie.slug}`,
            );
        });

        it('renders trailer link when trailer_url provided', () => {
            const wrapper = mountFeaturedMovie();
            const trailerLink = wrapper
                .findAll('a')
                .find((a) => a.text().includes('Trailer'));
            expect(trailerLink).toBeDefined();
            expect(trailerLink?.attributes('href')).toBe(
                defaultMovie.trailer_url,
            );
            expect(trailerLink?.attributes('target')).toBe('_blank');
        });

        it('does not render trailer link when no trailer_url', () => {
            const movieWithoutTrailer = createMockMovie({ trailer_url: null });
            const wrapper = mountFeaturedMovie({ movie: movieWithoutTrailer });
            const trailerLink = wrapper
                .findAll('a')
                .find((a) => a.text().includes('Trailer'));
            expect(trailerLink).toBeUndefined();
        });
    });

    describe('conditional rendering', () => {
        it('does not render runtime when not provided', () => {
            const movieWithoutRuntime = createMockMovie({ runtime: null });
            const wrapper = mountFeaturedMovie({ movie: movieWithoutRuntime });
            expect(wrapper.text()).not.toContain('min');
        });

        it('does not render country when not provided', () => {
            const movieWithoutCountry = createMockMovie({ country: null });
            const wrapper = mountFeaturedMovie({ movie: movieWithoutCountry });
            // Should not contain country separator and value
            expect(wrapper.html()).not.toContain('• USA');
        });
    });
});
