<script setup lang="ts">
import type { TooltipRootEmits, TooltipRootProps } from "reka-ui"
import { computed } from "vue"
import { TooltipRoot, useForwardPropsEmits } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<TooltipRootProps>()
const emits = defineEmits<TooltipRootEmits>()

const filteredProps = computed(() => filterUndefinedProps(props))
const forwarded = useForwardPropsEmits(filteredProps, emits)
</script>

<template>
  <TooltipRoot
    v-slot="slotProps"
    data-slot="tooltip"
    v-bind="forwarded"
  >
    <slot v-bind="slotProps" />
  </TooltipRoot>
</template>
