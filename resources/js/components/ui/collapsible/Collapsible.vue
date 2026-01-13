<script setup lang="ts">
import type { CollapsibleRootEmits, CollapsibleRootProps } from "reka-ui"
import { computed } from "vue"
import { CollapsibleRoot, useForwardPropsEmits } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<CollapsibleRootProps>()
const emits = defineEmits<CollapsibleRootEmits>()

const filteredProps = computed(() => filterUndefinedProps(props))
const forwarded = useForwardPropsEmits(filteredProps, emits)
</script>

<template>
  <CollapsibleRoot
    v-slot="slotProps"
    data-slot="collapsible"
    v-bind="forwarded"
  >
    <slot v-bind="slotProps" />
  </CollapsibleRoot>
</template>
