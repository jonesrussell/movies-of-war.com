<script setup lang="ts">
import type { TooltipProviderProps } from "reka-ui"
import { computed } from "vue"
import { TooltipProvider } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = withDefaults(defineProps<TooltipProviderProps>(), {
  delayDuration: 0,
})

const filteredProps = computed(() => filterUndefinedProps(props))
</script>

<template>
  <TooltipProvider v-bind="filteredProps">
    <slot />
  </TooltipProvider>
</template>
