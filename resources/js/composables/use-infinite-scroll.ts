import { useIntersectionObserver } from '@vueuse/core';
import { type Ref, ref, watch } from 'vue';

interface UseInfiniteScrollOptions {
    enabled?: Ref<boolean>;
    threshold?: number;
    rootMargin?: string;
}

export function useInfiniteScroll(
    onLoadMore: () => void | Promise<void>,
    options: UseInfiniteScrollOptions = {},
) {
    const {
        enabled = ref(true),
        threshold = 0.1,
        rootMargin = '100px',
    } = options;

    const sentinelRef = ref<HTMLElement | null>(null);
    const isLoading = ref(false);
    const hasError = ref(false);

    const { stop } = useIntersectionObserver(
        sentinelRef,
        async (entries) => {
            const entry = entries[0];
            if (!entry?.isIntersecting || !enabled.value || isLoading.value) {
                return;
            }

            isLoading.value = true;
            hasError.value = false;

            try {
                await onLoadMore();
            } catch (error) {
                hasError.value = true;
                console.error('Infinite scroll error:', error);
            } finally {
                isLoading.value = false;
            }
        },
        {
            threshold,
            rootMargin,
        },
    );

    watch(enabled, (isEnabled) => {
        if (!isEnabled) {
            isLoading.value = false;
        }
    });

    function reset() {
        isLoading.value = false;
        hasError.value = false;
    }

    return {
        sentinelRef,
        isLoading,
        hasError,
        reset,
        stop,
    };
}
