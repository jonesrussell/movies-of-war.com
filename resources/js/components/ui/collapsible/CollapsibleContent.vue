<script setup lang="ts">
import type { CollapsibleContentProps } from "reka-ui"
import { computed } from "vue"
import { CollapsibleContent } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<CollapsibleContentProps>()

const filteredProps = computed(() => filterUndefinedProps(props))
</script>

<template>
  <CollapsibleContent
    data-slot="collapsible-content"
    v-bind="filteredProps"
  >
    <slot />
  </CollapsibleContent>
</template>
