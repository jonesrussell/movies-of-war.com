<script setup lang="ts">
import type { DropdownMenuTriggerProps } from "reka-ui"
import { computed } from "vue"
import { DropdownMenuTrigger, useForwardProps } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<DropdownMenuTriggerProps>()

const filteredProps = computed(() => filterUndefinedProps(props))
const forwardedProps = useForwardProps(filteredProps)
</script>

<template>
  <DropdownMenuTrigger
    data-slot="dropdown-menu-trigger"
    v-bind="forwardedProps"
  >
    <slot />
  </DropdownMenuTrigger>
</template>
