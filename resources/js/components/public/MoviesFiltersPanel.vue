<script setup lang="ts">
import type { Tag } from '@/types';

type MaybeNumber = number | string;

interface Props {
    years: number[];
    countries: string[];
    conflicts: string[];
    tags: Tag[];
    year: MaybeNumber;
    country: string;
    conflict: string;
    tag: string;
    class?: string;
}

withDefaults(defineProps<Props>(), { class: '' });

defineEmits<{
    'update:year': [value: MaybeNumber];
    'update:country': [value: string];
    'update:conflict': [value: string];
    'update:tag': [value: string];
}>();
</script>

<template>
    <div
        :class="[
            'grid gap-4 rounded-2xl bg-zinc-950 p-4 ring-1 ring-zinc-800/70 sm:grid-cols-2 lg:grid-cols-4',
            $props.class,
        ]"
    >
        <div class="flex flex-col gap-2">
            <label class="text-xs font-semibold tracking-wide text-zinc-300">
                Year
            </label>
            <select
                :value="year"
                class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-3 py-2 text-white focus:border-red-600 focus:ring-2 focus:ring-red-600/40 focus:outline-none"
                @change="
                    $emit(
                        'update:year',
                        ($event.target as HTMLSelectElement).value,
                    )
                "
            >
                <option value="">All years</option>
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>

        <div class="flex flex-col gap-2">
            <label class="text-xs font-semibold tracking-wide text-zinc-300">
                Country
            </label>
            <select
                :value="country"
                class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-3 py-2 text-white focus:border-red-600 focus:ring-2 focus:ring-red-600/40 focus:outline-none"
                @change="
                    $emit(
                        'update:country',
                        ($event.target as HTMLSelectElement).value,
                    )
                "
            >
                <option value="">All countries</option>
                <option v-for="c in countries" :key="c" :value="c">
                    {{ c }}
                </option>
            </select>
        </div>

        <div class="flex flex-col gap-2">
            <label class="text-xs font-semibold tracking-wide text-zinc-300">
                Conflict
            </label>
            <select
                :value="conflict"
                class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-3 py-2 text-white focus:border-red-600 focus:ring-2 focus:ring-red-600/40 focus:outline-none"
                @change="
                    $emit(
                        'update:conflict',
                        ($event.target as HTMLSelectElement).value,
                    )
                "
            >
                <option value="">All conflicts</option>
                <option v-for="c in conflicts" :key="c" :value="c">
                    {{ c }}
                </option>
            </select>
        </div>

        <div class="flex flex-col gap-2">
            <label class="text-xs font-semibold tracking-wide text-zinc-300">
                Tag
            </label>
            <select
                :value="tag"
                class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-3 py-2 text-white focus:border-red-600 focus:ring-2 focus:ring-red-600/40 focus:outline-none"
                @change="
                    $emit(
                        'update:tag',
                        ($event.target as HTMLSelectElement).value,
                    )
                "
            >
                <option value="">All tags</option>
                <option v-for="t in tags" :key="t.id" :value="t.slug">
                    {{ t.name }}
                </option>
            </select>
        </div>
    </div>
</template>
