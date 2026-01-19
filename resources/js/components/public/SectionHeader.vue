<script setup lang="ts">
import { computed } from 'vue';

import { cn } from '@/lib/utils';

type Align = 'left' | 'center';
type HeadingLevel = 1 | 2 | 3 | 4 | 5 | 6;

interface Props {
    kicker?: string;
    title: string;
    description?: string;
    align?: Align;
    level?: HeadingLevel;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    kicker: '',
    description: '',
    align: 'left',
    level: 1,
    class: '',
});

const headingTag = computed(() => `h${props.level}` as const);
</script>

<template>
    <div
        :class="
            cn(
                align === 'center' ? 'text-center' : 'text-left',
                'flex flex-col gap-2',
                $props.class,
            )
        "
    >
        <p
            v-if="kicker"
            class="text-xs font-semibold tracking-[0.2em] text-red-500 uppercase"
        >
            {{ kicker }}
        </p>

        <div class="flex items-end justify-between gap-4">
            <component
                :is="headingTag"
                class="text-3xl font-semibold tracking-tight text-balance text-white sm:text-4xl lg:text-5xl"
                :class="align === 'center' ? 'mx-auto' : ''"
            >
                {{ title }}
            </component>

            <div v-if="$slots.action" class="shrink-0">
                <slot name="action" />
            </div>
        </div>

        <p
            v-if="description"
            class="max-w-prose text-base leading-relaxed text-pretty text-zinc-300 sm:text-lg"
            :class="align === 'center' ? 'mx-auto' : ''"
        >
            {{ description }}
        </p>
    </div>
</template>
