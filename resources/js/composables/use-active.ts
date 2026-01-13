import type { InertiaLinkProps } from '@inertiajs/vue3';

import { useActiveUrl } from '@/composables/use-active-url';

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
