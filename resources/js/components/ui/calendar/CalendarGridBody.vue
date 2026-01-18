<script lang="ts" setup>
import type { CalendarGridBodyProps } from "reka-ui"
import { CalendarGridBody } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<CalendarGridBodyProps>()

const filteredProps = filterUndefinedProps(props as Record<string, unknown>)
</script>

<template>
  <CalendarGridBody
    data-slot="calendar-grid-body"
    v-bind="filteredProps"
  >
    <slot />
  </CalendarGridBody>
</template>
