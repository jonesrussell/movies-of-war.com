<script setup lang="ts">
import type { DropdownMenuGroupProps } from "reka-ui"
import { computed } from "vue"
import { DropdownMenuGroup } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<DropdownMenuGroupProps>()

const filteredProps = computed(() => filterUndefinedProps(props))
</script>

<template>
  <DropdownMenuGroup
    data-slot="dropdown-menu-group"
    v-bind="filteredProps"
  >
    <slot />
  </DropdownMenuGroup>
</template>
