import { computed } from 'vue';

import { type ResolvedAppearance, useAppearance } from '@/composables/use-appearance';

export type ThemeMode = 'light' | 'dark' | 'system';
export type ResolvedTheme = ResolvedAppearance;

/**
 * Theme composable that extends useAppearance for future-proofing.
 * Currently supports light/dark/system modes.
 * Can be extended for high-contrast mode, seasonal themes, etc.
 */
export function useTheme() {
    const { appearance, resolvedAppearance, updateAppearance } = useAppearance();

    const isDark = computed(() => resolvedAppearance.value === 'dark');
    const isLight = computed(() => resolvedAppearance.value === 'light');

    function setTheme(mode: ThemeMode) {
        updateAppearance(mode);
    }

    return {
        appearance,
        isDark,
        isLight,
        resolvedTheme: resolvedAppearance,
        setTheme,
    };
}
