<script setup lang="ts">
import type { PopoverTriggerProps } from "reka-ui"
import { PopoverTrigger } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<PopoverTriggerProps>()

const filteredProps = filterUndefinedProps(props as Record<string, unknown>)
</script>

<template>
  <PopoverTrigger
    data-slot="popover-trigger"
    v-bind="filteredProps"
  >
    <slot />
  </PopoverTrigger>
</template>
