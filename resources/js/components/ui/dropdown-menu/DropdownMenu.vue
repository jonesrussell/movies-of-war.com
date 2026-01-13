<script setup lang="ts">
import type { DropdownMenuRootEmits, DropdownMenuRootProps } from "reka-ui"
import { computed } from "vue"
import { DropdownMenuRoot, useForwardPropsEmits } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<DropdownMenuRootProps>()
const emits = defineEmits<DropdownMenuRootEmits>()

const filteredProps = computed(() => filterUndefinedProps(props))
const forwarded = useForwardPropsEmits(filteredProps, emits)
</script>

<template>
  <DropdownMenuRoot
    v-slot="slotProps"
    data-slot="dropdown-menu"
    v-bind="forwarded"
  >
    <slot v-bind="slotProps" />
  </DropdownMenuRoot>
</template>
