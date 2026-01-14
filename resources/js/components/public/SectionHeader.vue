<script setup lang="ts">
import { cn } from '@/lib/utils';

type Align = 'left' | 'center';

interface Props {
    kicker?: string;
    title: string;
    description?: string;
    align?: Align;
    class?: string;
}

withDefaults(defineProps<Props>(), {
    kicker: '',
    description: '',
    align: 'left',
    class: '',
});
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
            <h1
                class="text-3xl font-semibold tracking-tight text-balance text-white sm:text-4xl lg:text-5xl"
                :class="align === 'center' ? 'mx-auto' : ''"
            >
                {{ title }}
            </h1>

            <div v-if="$slots.action" class="shrink-0">
                <slot name="action" />
            </div>
        </div>

        <p
            v-if="description"
            class="max-w-3xl text-base leading-relaxed text-pretty text-zinc-400 sm:text-lg"
            :class="align === 'center' ? 'mx-auto' : ''"
        >
            {{ description }}
        </p>
    </div>
</template>
