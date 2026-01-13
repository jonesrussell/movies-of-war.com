<script setup lang="ts">
import type { TooltipTriggerProps } from "reka-ui"
import { computed } from "vue"
import { TooltipTrigger } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<TooltipTriggerProps>()

const filteredProps = computed(() => filterUndefinedProps(props))
</script>

<template>
  <TooltipTrigger
    data-slot="tooltip-trigger"
    v-bind="filteredProps"
  >
    <slot />
  </TooltipTrigger>
</template>
