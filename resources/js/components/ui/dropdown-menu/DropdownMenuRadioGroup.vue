<script setup lang="ts">
import type { DropdownMenuRadioGroupEmits, DropdownMenuRadioGroupProps } from "reka-ui"
import { computed } from "vue"
import {
  DropdownMenuRadioGroup,
  useForwardPropsEmits,
} from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<DropdownMenuRadioGroupProps>()
const emits = defineEmits<DropdownMenuRadioGroupEmits>()

const filteredProps = computed(() => filterUndefinedProps(props))
const forwarded = useForwardPropsEmits(filteredProps, emits)
</script>

<template>
  <DropdownMenuRadioGroup
    data-slot="dropdown-menu-radio-group"
    v-bind="forwarded"
  >
    <slot />
  </DropdownMenuRadioGroup>
</template>
