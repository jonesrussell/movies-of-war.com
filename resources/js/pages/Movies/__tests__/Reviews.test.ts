import type { Review } from '@/types/models';

import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import { createMockMovie } from '@/__tests__/utils/test-utils';

vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(() => ({
        props: {
            auth: { user: null },
        },
        url: '/movies/test-movie/reviews',
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

vi.mock('@/layouts/PublicLayout.vue', () => ({
    default: {
        name: 'PublicLayout',
        template: '<div><slot /></div>',
    },
}));

vi.mock('@/components/public/PublicContainer.vue', () => ({
    default: {
        name: 'PublicContainer',
        template: '<div><slot /></div>',
        props: ['class'],
    },
}));

vi.mock('@/components/public/PublicSection.vue', () => ({
    default: {
        name: 'PublicSection',
        template: '<div><slot /></div>',
        props: ['spacing'],
    },
}));

vi.mock('@/components/primitives', () => ({
    Poster: {
        name: 'Poster',
        template: '<img />',
        props: ['src', 'alt', 'posterPath', 'context'],
    },
}));

vi.mock('@/components/public/SectionHeader.vue', () => ({
    default: {
        name: 'SectionHeader',
        template: '<h2>{{ title }}</h2>',
        props: ['title', 'level'],
    },
}));

vi.mock('@/components/reviews/CuratorReview.vue', () => ({
    default: {
        name: 'CuratorReviewComponent',
        template: '<div data-testid="curator-review">Editorial review</div>',
        props: ['review'],
    },
}));

vi.mock('@/components/reviews/ReviewCard.vue', () => ({
    default: {
        name: 'ReviewCard',
        template: '<article data-testid="review-card"><slot /></article>',
        props: ['review', 'hideSpoilerBlur', 'isCuratorPick'],
    },
}));

vi.mock('@/components/reviews/ReviewList.vue', () => ({
    default: {
        name: 'ReviewList',
        template: '<div data-testid="review-list"><slot /></div>',
        props: ['reviews', 'queryParams', 'movieSlug', 'emptyMessage'],
    },
}));

vi.mock('@/components/reviews/ReviewForm.vue', () => ({
    default: {
        name: 'ReviewForm',
        template: '<form></form>',
        props: ['movie', 'existingReview'],
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button type="button"><slot /></button>',
        props: ['variant', 'class'],
    },
}));

vi.mock('@/routes', () => ({
    login: (opts: { query?: { redirect?: string } }) => ({
        url: `/login${opts?.query?.redirect ? `?redirect=${encodeURIComponent(opts.query.redirect)}` : ''}`,
    }),
    register: () => ({ url: '/register' }),
}));

const defaultPaginationLinks = [{ url: null, label: '1', active: true }] as {
    url: string | null;
    label: string;
    active: boolean;
}[];

const defaultPagination = {
    meta: {
        total: 0,
        last_page: 1,
        current_page: 1,
        from: null,
        to: null,
        path: '/movies/test-movie/reviews',
        per_page: 15,
        links: defaultPaginationLinks,
    },
    links: defaultPaginationLinks,
};

function createMockReview(overrides: Partial<Review> = {}): Review {
    return {
        id: 1,
        user_id: 1,
        movie_id: 1,
        rating: 4,
        title: 'Great film',
        content: 'Full content.',
        content_html: '<p>Full content.</p>',
        content_excerpt: 'Full content.',
        has_spoilers: false,
        is_published: true,
        helpful_count: 0,
        comments_count: 0,
        created_at: '2024-01-01T00:00:00.000000Z',
        updated_at: '2024-01-01T00:00:00.000000Z',
        stars: '★★★★☆',
        is_edited: false,
        formatted_date: '1 day ago',
        user: { id: 1, name: 'Curator' },
        is_curator: true,
        can_edit: false,
        can_delete: false,
        ...overrides,
    };
}

import MoviesReviews from '../Reviews.vue';

describe('Movies/Reviews', () => {
    const defaultMovie = createMockMovie({
        slug: 'test-movie',
        title: 'Test Movie',
    });

    function mountReviews(props: Record<string, unknown> = {}) {
        return mount(MoviesReviews, {
            props: {
                movie: defaultMovie,
                reviews: {
                    data: [],
                    ...defaultPagination,
                },
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

    it('renders compact movie context with back link and movie title', () => {
        const wrapper = mountReviews();
        expect(wrapper.text()).toContain('Back to Test Movie');
        expect(wrapper.text()).toContain('Test Movie');
    });

    it('shows "All reviews" when no curator review', () => {
        const wrapper = mountReviews();
        const headers = wrapper.findAll('h2');
        const allReviewsHeader = headers.find(
            (h) => h.text() === 'All reviews',
        );
        expect(allReviewsHeader).toBeDefined();
    });

    it('shows curator review block and "More reviews" when curator_review and user reviews exist', () => {
        const curatorReview = createMockReview({
            id: 1,
            content_excerpt: 'Curator excerpt.',
        });
        const wrapper = mountReviews({
            curator_review: curatorReview,
            reviews: {
                data: [
                    createMockReview({
                        id: 2,
                        is_curator: false,
                        user: { id: 2, name: 'Other User' },
                    }),
                ],
                ...defaultPagination,
            },
        });
        expect(wrapper.text()).toContain("Curator's review");
        expect(wrapper.find('[data-testid="review-card"]').exists()).toBe(true);
        const headers = wrapper.findAll('h2');
        const moreReviewsHeader = headers.find(
            (h) => h.text() === 'More reviews',
        );
        expect(moreReviewsHeader).toBeDefined();
        expect(wrapper.find('[data-testid="review-list"]').exists()).toBe(true);
    });

    it('shows guest empty state when no reviews at all', () => {
        const wrapper = mountReviews();
        expect(wrapper.text()).toContain('Be the first to review');
        expect(wrapper.text()).toContain('Log in to review');
    });

    it('shows filesystem curator review when curatorReview prop is passed', () => {
        const curatorReview = {
            title: 'Gallipoli',
            year: 1981,
            rating: 3,
            director: 'Peter Weir',
            starring: ['Mark Lee', 'Mel Gibson'],
            runtime: 110,
            slug: 'gallipoli',
            has_spoilers: false,
            content_html: '<p>Editorial content.</p>',
            content_excerpt: 'Editorial excerpt.',
        };
        const wrapper = mountReviews({
            curatorReview,
            reviews: {
                data: [],
                ...defaultPagination,
            },
        });
        expect(wrapper.find('[data-testid="curator-review"]').exists()).toBe(
            true,
        );
        expect(wrapper.text()).toContain('Editorial review');
    });

    it('shows CTA block and no "More reviews" or ReviewList when only curator review', () => {
        const curatorReview = createMockReview();
        const wrapper = mountReviews({
            curator_review: curatorReview,
            reviews: {
                data: [],
                ...defaultPagination,
            },
        });
        expect(wrapper.text()).toContain(
            'No user reviews yet. Be the first to share your thoughts!',
        );
        const moreReviewsHeader = wrapper
            .findAll('h2')
            .find((h) => h.text() === 'More reviews');
        expect(moreReviewsHeader).toBeUndefined();
        expect(wrapper.find('[data-testid="review-list"]').exists()).toBe(
            false,
        );
        expect(wrapper.text()).toContain('Log in to review');
        expect(wrapper.find('a[href*="/register"]').exists()).toBe(true);
    });
});
