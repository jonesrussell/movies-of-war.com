<script lang="ts" setup>
import type { CalendarCellProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { computed, toRaw } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { CalendarCell, useForwardProps } from "reka-ui"
import { cn, filterUndefinedProps } from "@/lib/utils"

const props = defineProps<CalendarCellProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedPropsRaw = useForwardProps(delegatedProps)

const forwardedProps = computed(() => {
    const filtered = filterUndefinedProps(forwardedPropsRaw.value as Record<string, unknown>);
    // Merge with delegatedProps to ensure required props (like 'date') are preserved
    const delegated = toRaw(delegatedProps);
    const delegatedFiltered = filterUndefinedProps(delegated as Record<string, unknown>);
    // Merge filtered props over delegated, but ensure required props like 'date' are preserved
    const result = { ...delegatedFiltered, ...filtered };
    // Explicitly preserve required props that might be filtered out
    if ('date' in delegated && delegated.date !== undefined) {
        (result as Record<string, unknown>).date = delegated.date;
    }
    return result as Record<string, unknown>;
})
</script>

<template>
  <CalendarCell
    data-slot="calendar-cell"
    :class="cn('relative p-0 text-center text-sm focus-within:relative focus-within:z-20 [&:has([data-selected])]:rounded-md [&:has([data-selected])]:bg-accent', props.class)"
    v-bind="forwardedProps as any"
  >
    <slot />
  </CalendarCell>
</template>
