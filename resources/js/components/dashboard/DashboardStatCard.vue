<script setup lang="ts">
import type { LucideIcon } from 'lucide-vue-next';
import type { Component } from 'vue';

import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Trend {
    value: number;
    label: string;
}

type Variant = 'default' | 'success' | 'warning' | 'danger';

const props = defineProps<{
    title: string;
    value: string | number;
    icon: LucideIcon | Component;
    href: string | null;
    trend: Trend | null;
    variant: Variant;
}>();

const variantClasses: Record<Variant, string> = {
    default: 'bg-zinc-800/50 text-zinc-400',
    success: 'bg-emerald-500/10 text-emerald-400',
    warning: 'bg-amber-500/10 text-amber-400',
    danger: 'bg-red-500/10 text-red-400',
};

const iconVariantClasses: Record<Variant, string> = {
    default: 'text-zinc-400',
    success: 'text-emerald-400',
    warning: 'text-amber-400',
    danger: 'text-red-400',
};

const containerClass = computed(() => variantClasses[props.variant]);
const iconClass = computed(() => iconVariantClasses[props.variant]);
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href ?? undefined"
        class="group relative overflow-hidden rounded-xl bg-zinc-900 p-5 ring-1 ring-zinc-800/70 transition-all"
        :class="[href && 'hover:bg-zinc-800/70 hover:ring-zinc-700/70']"
    >
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-zinc-400">{{ title }}</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-white">
                    {{
                        typeof value === 'number'
                            ? value.toLocaleString()
                            : value
                    }}
                </p>
                <p
                    v-if="trend"
                    class="mt-2 flex items-center gap-1 text-xs"
                    :class="[
                        trend.value >= 0 ? 'text-emerald-400' : 'text-red-400',
                    ]"
                >
                    <span
                        >{{ trend.value >= 0 ? '+' : ''
                        }}{{ trend.value }}%</span
                    >
                    <span class="text-zinc-500">{{ trend.label }}</span>
                </p>
            </div>
            <div
                class="flex size-11 shrink-0 items-center justify-center rounded-lg"
                :class="containerClass"
            >
                <component :is="icon" class="size-5" :class="iconClass" />
            </div>
        </div>

        <div
            v-if="href"
            class="absolute bottom-0 left-0 h-0.5 w-0 bg-gradient-to-r from-red-500 to-red-600 transition-all duration-300 group-hover:w-full"
        />
    </component>
</template>
