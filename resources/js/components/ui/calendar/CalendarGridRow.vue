<script lang="ts" setup>
import type { CalendarGridRowProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { computed } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { CalendarGridRow, useForwardProps } from "reka-ui"
import { cn, filterUndefinedProps } from "@/lib/utils"

const props = defineProps<CalendarGridRowProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedPropsRaw = useForwardProps(delegatedProps)

const forwardedProps = computed(() => filterUndefinedProps(forwardedPropsRaw.value as Record<string, unknown>))
</script>

<template>
  <CalendarGridRow
    data-slot="calendar-grid-row"
    :class="cn('flex', props.class)" v-bind="forwardedProps"
  >
    <slot />
  </CalendarGridRow>
</template>
