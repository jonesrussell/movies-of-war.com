<script setup lang="ts">
import type { CollapsibleTriggerProps } from "reka-ui"
import { computed } from "vue"
import { CollapsibleTrigger } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<CollapsibleTriggerProps>()

const filteredProps = computed(() => filterUndefinedProps(props))
</script>

<template>
  <CollapsibleTrigger
    data-slot="collapsible-trigger"
    v-bind="filteredProps"
  >
    <slot />
  </CollapsibleTrigger>
</template>
