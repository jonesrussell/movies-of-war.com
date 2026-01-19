import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { createMockMovie } from '@/__tests__/utils/test-utils';
import { MovieStatus } from '@/types/enums';

const postMock = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
    router: {
        post: (...args: unknown[]) => postMock(...args),
    },
}));

import MovieCardAdminActions from '../MovieCardAdminActions.vue';

describe('MovieCardAdminActions', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        postMock.mockClear();
    });

    function mountAdminActions(
        status: MovieStatus.Draft | MovieStatus.Published,
        id = 1,
    ) {
        const movie = createMockMovie({ id, status });

        const wrapper = mount(MovieCardAdminActions, {
            props: { movie },
            global: {
                stubs: {
                    Button: {
                        template:
                            '<button type="button" @click="$emit(\'click\', $event)"><slot /></button>',
                    },
                },
            },
        });

        return { wrapper, movie };
    }

    function getButton(wrapper: ReturnType<typeof mount>, label: string) {
        const button = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === label);

        expect(button, `Expected a "${label}" button`).toBeDefined();
        return button!;
    }

    it('shows Unpublish for published movies', () => {
        const { wrapper } = mountAdminActions(MovieStatus.Published);
        expect(wrapper.text()).toContain('Unpublish');
        expect(wrapper.text()).not.toContain('Publish');
        expect(wrapper.text()).toContain('Archive');
    });

    it('shows Publish for draft movies', () => {
        const { wrapper } = mountAdminActions(MovieStatus.Draft);
        expect(wrapper.text()).toContain('Publish');
        expect(wrapper.text()).not.toContain('Unpublish');
        expect(wrapper.text()).toContain('Archive');
    });

    it('posts to publish route when Publish clicked', async () => {
        const { wrapper, movie } = mountAdminActions(MovieStatus.Draft, 123);
        await getButton(wrapper, 'Publish').trigger('click');

        expect(postMock).toHaveBeenCalledWith(
            `/movies/${movie.id}/publish`,
            {},
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('posts to unpublish route when Unpublish clicked', async () => {
        const { wrapper, movie } = mountAdminActions(
            MovieStatus.Published,
            456,
        );
        await getButton(wrapper, 'Unpublish').trigger('click');

        expect(postMock).toHaveBeenCalledWith(
            `/movies/${movie.id}/unpublish`,
            {},
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('posts to archive route when confirmed', async () => {
        const { wrapper, movie } = mountAdminActions(
            MovieStatus.Published,
            789,
        );

        const confirmMock = vi
            .spyOn(window, 'confirm')
            .mockImplementation(() => true);

        await getButton(wrapper, 'Archive').trigger('click');

        expect(postMock).toHaveBeenCalledWith(
            `/movies/${movie.id}/archive`,
            {},
            expect.objectContaining({ preserveScroll: true }),
        );

        confirmMock.mockRestore();
    });

    it('does not archive when not confirmed', async () => {
        const { wrapper } = mountAdminActions(MovieStatus.Published, 999);

        const confirmMock = vi
            .spyOn(window, 'confirm')
            .mockImplementation(() => false);

        await getButton(wrapper, 'Archive').trigger('click');

        expect(postMock).not.toHaveBeenCalled();

        confirmMock.mockRestore();
    });
});
