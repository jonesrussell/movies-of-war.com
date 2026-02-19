<script setup lang="ts">
import type { Tag } from '@/types';

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

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

const emit = defineEmits<{
    'update:year': [value: MaybeNumber];
    'update:country': [value: string];
    'update:conflict': [value: string];
    'update:tag': [value: string];
}>();
</script>

<template>
    <div
        :class="[
            'filters-panel grid gap-4 rounded-xl border border-[--intel-border] bg-[--intel-bg-surface] p-4',
            $props.class,
        ]"
        style="container-type: inline-size"
    >
        <div class="flex flex-col gap-2">
            <label
                class="text-xs font-semibold tracking-wide text-[--intel-text-muted]"
            >
                Year
            </label>
            <Select
                :model-value="String(year)"
                @update:model-value="emit('update:year', $event as MaybeNumber)"
            >
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="All years" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">All years</SelectItem>
                    <SelectItem v-for="y in years" :key="y" :value="String(y)">
                        {{ y }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="flex flex-col gap-2">
            <label
                class="text-xs font-semibold tracking-wide text-[--intel-text-muted]"
            >
                Country
            </label>
            <Select
                :model-value="country"
                @update:model-value="emit('update:country', $event as string)"
            >
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="All countries" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">All countries</SelectItem>
                    <SelectItem v-for="c in countries" :key="c" :value="c">
                        {{ c }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="flex flex-col gap-2">
            <label
                class="text-xs font-semibold tracking-wide text-[--intel-text-muted]"
            >
                Conflict
            </label>
            <Select
                :model-value="conflict"
                @update:model-value="emit('update:conflict', $event as string)"
            >
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="All conflicts" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">All conflicts</SelectItem>
                    <SelectItem v-for="c in conflicts" :key="c" :value="c">
                        {{ c }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="flex flex-col gap-2">
            <label
                class="text-xs font-semibold tracking-wide text-[--intel-text-muted]"
            >
                Tag
            </label>
            <Select
                :model-value="tag"
                @update:model-value="
                    emit('update:tag', $event != null ? String($event) : '')
                "
            >
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="All tags" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">All tags</SelectItem>
                    <SelectItem v-for="t in tags" :key="t.id" :value="t.slug">
                        {{ t.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
    </div>
</template>
