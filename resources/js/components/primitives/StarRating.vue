<script setup lang="ts">
import { computed, ref } from 'vue';

const MAX_STARS = 4;

interface Props {
    rating: number;
    maxStars?: number;
    interactive?: boolean;
    size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    maxStars: MAX_STARS,
    interactive: false,
    size: 'md',
});

const emit = defineEmits<{
    'update:rating': [value: number];
}>();

const hoverRating = ref<number | null>(null);

const displayRating = computed(() =>
    props.interactive && hoverRating.value !== null
        ? hoverRating.value
        : props.rating,
);

const sizeClass = computed(
    () =>
        ({
            sm: 'text-base',
            md: 'text-lg',
            lg: 'text-xl',
        })[props.size],
);

const stars = computed(() => {
    const full = Math.floor(displayRating.value);
    return Array.from({ length: props.maxStars }, (_, i) =>
        i < full ? 'full' : 'empty',
    );
});

function handleClick(index: number) {
    if (!props.interactive) return;
    const value = index + 1;
    emit('update:rating', Math.min(value, props.maxStars));
}

function handleMouseEnter(index: number) {
    if (!props.interactive) return;
    hoverRating.value = index + 1;
}

function handleMouseLeave() {
    if (!props.interactive) return;
    hoverRating.value = null;
}
</script>

<template>
    <div
        class="inline-flex gap-0.5"
        :class="[sizeClass, interactive && 'cursor-pointer select-none']"
        role="img"
        :aria-label="
            interactive
                ? `Rate ${displayRating} out of ${maxStars} stars`
                : `${rating} out of ${maxStars} stars`
        "
    >
        <button
            v-for="(star, index) in stars"
            :key="index"
            type="button"
            class="p-0.5 transition-colors focus:ring-2 focus:ring-[--intel-alert]/50 focus:ring-offset-2 focus:ring-offset-[--intel-bg-base] focus:outline-none disabled:pointer-events-none"
            :class="[
                interactive
                    ? 'rounded hover:scale-110'
                    : 'pointer-events-none cursor-default',
            ]"
            :disabled="!interactive"
            :aria-label="`${index + 1} star${index + 1 > 1 ? 's' : ''}`"
            @click="handleClick(index)"
            @mouseenter="handleMouseEnter(index)"
            @mouseleave="handleMouseLeave"
        >
            <span
                v-if="star === 'full'"
                class="text-[--intel-alert]"
                aria-hidden="true"
                >★</span
            >
            <span v-else class="text-[--intel-text-faint]" aria-hidden="true"
                >☆</span
            >
        </button>
    </div>
</template>
