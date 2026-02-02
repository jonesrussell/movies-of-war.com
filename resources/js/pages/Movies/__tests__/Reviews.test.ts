import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import { createMockMovie } from '@/__tests__/utils/test-utils';
import type { Review } from '@/types/models';

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

const defaultPagination = {
    meta: { total: 0, last_page: 1, current_page: 1 },
    links: [{ url: null, label: '1', active: true }],
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
    const defaultMovie = createMockMovie({ slug: 'test-movie', title: 'Test Movie' });

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
        const allReviewsHeader = headers.find((h) => h.text() === 'All reviews');
        expect(allReviewsHeader).toBeDefined();
    });

    it('shows curator review block and "More reviews" when curator_review provided', () => {
        const curatorReview = createMockReview({ id: 1, content_excerpt: 'Curator excerpt.' });
        const wrapper = mountReviews({
            curator_review: curatorReview,
        });
        expect(wrapper.text()).toContain("Curator's review");
        expect(wrapper.find('[data-testid="review-card"]').exists()).toBe(true);
        const headers = wrapper.findAll('h2');
        const moreReviewsHeader = headers.find((h) => h.text() === 'More reviews');
        expect(moreReviewsHeader).toBeDefined();
    });

    it('shows guest empty state when no reviews at all', () => {
        const wrapper = mountReviews();
        expect(wrapper.text()).toContain('Be the first to review');
        expect(wrapper.text()).toContain('Log in to review');
    });

    it('renders ReviewList with empty message when only curator review', () => {
        const curatorReview = createMockReview();
        const wrapper = mountReviews({
            curator_review: curatorReview,
            reviews: {
                data: [],
                ...defaultPagination,
            },
        });
        const reviewList = wrapper.findComponent({ name: 'ReviewList' });
        expect(reviewList.exists()).toBe(true);
        expect(reviewList.props('emptyMessage')).toBe('No other reviews yet.');
    });
});
