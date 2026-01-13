import { useMediaQuery } from '@vueuse/core';
import { computed } from 'vue';

export type Breakpoint = 'mobile' | 'tablet' | 'desktop';

export function useBreakpoint() {
    const isMobile = useMediaQuery('(max-width: 639px)');
    const isTablet = useMediaQuery('(min-width: 640px) and (max-width: 1023px)');
    const isDesktop = useMediaQuery('(min-width: 1024px)');

    const currentBreakpoint = computed<Breakpoint>(() => {
        if (isDesktop.value) {
            return 'desktop';
        }

        if (isTablet.value) {
            return 'tablet';
        }

        return 'mobile';
    });

    return {
        currentBreakpoint,
        isDesktop,
        isMobile,
        isTablet,
    };
}
