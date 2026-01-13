import type { InertiaLinkProps } from '@inertiajs/vue3';

import { computed } from 'vue';

import { useActiveUrl } from '@/composables/useActiveUrl';

export function useActive() {
    const { urlIsActive } = useActiveUrl();

    function isActive(url: NonNullable<InertiaLinkProps['href']>): boolean {
        return urlIsActive(url);
    }

    function activeClass(
        url: NonNullable<InertiaLinkProps['href']>,
        activeClass = 'text-red-500',
        inactiveClass = 'text-zinc-300',
    ): string {
        return isActive(url) ? activeClass : inactiveClass;
    }

    return {
        activeClass,
        isActive,
    };
}
