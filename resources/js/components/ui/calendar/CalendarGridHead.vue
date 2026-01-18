<script lang="ts" setup>
import type { CalendarGridHeadProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { CalendarGridHead } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<CalendarGridHeadProps & { class?: HTMLAttributes["class"] }>()

const filteredProps = filterUndefinedProps(props as Record<string, unknown>)
</script>

<template>
  <CalendarGridHead
    data-slot="calendar-grid-head"
    v-bind="filteredProps"
  >
    <slot />
  </CalendarGridHead>
</template>
