import type { InertiaLinkProps } from '@inertiajs/vue3';

import { usePage } from '@inertiajs/vue3';
import { computed, readonly } from 'vue';

import { toUrl } from '@/lib/utils';

/**
 * Get the current URL pathname, stripping query strings.
 * Inertia's page.url is a relative URL (pathname + optional query string).
 */
function getCurrentPathname(url: string): string {
    // Split on '?' to remove query string, then take the first part (pathname)
    return url.split('?')[0] ?? url;
}

export function useActiveUrl() {
    const page = usePage();

    const currentUrlReactive = computed(() => getCurrentPathname(page.url));

    function urlIsActive(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        currentUrl?: string,
    ) {
        const urlToCompare = currentUrl ?? currentUrlReactive.value;
        return toUrl(urlToCheck) === urlToCompare;
    }

    return {
        currentUrl: readonly(currentUrlReactive),
        urlIsActive,
    };
}
