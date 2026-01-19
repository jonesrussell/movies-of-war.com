import { useStorage } from '@vueuse/core';
import { computed } from 'vue';

export type ViewMode = 'grid' | 'list';

const STORAGE_KEY = 'movies-view-mode';

export function useViewMode() {
    const viewMode = useStorage<ViewMode>(STORAGE_KEY, 'grid');

    const isGridView = computed(() => viewMode.value === 'grid');
    const isListView = computed(() => viewMode.value === 'list');

    function setGridView() {
        viewMode.value = 'grid';
    }

    function setListView() {
        viewMode.value = 'list';
    }

    function toggleViewMode() {
        viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid';
    }

    return {
        viewMode,
        isGridView,
        isListView,
        setGridView,
        setListView,
        toggleViewMode,
    };
}
