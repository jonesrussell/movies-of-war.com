<script setup lang="ts">
import type { PopoverAnchorProps } from "reka-ui"
import { PopoverAnchor } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<PopoverAnchorProps>()

const filteredProps = filterUndefinedProps(props as Record<string, unknown>)
</script>

<template>
  <PopoverAnchor
    data-slot="popover-anchor"
    v-bind="filteredProps"
  >
    <slot />
  </PopoverAnchor>
</template>
