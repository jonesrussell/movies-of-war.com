<script setup lang="ts">
import type { SelectValueProps } from "reka-ui"
import { SelectValue } from "reka-ui"

const props = withDefaults(defineProps<SelectValueProps>(), {
  placeholder: "",
  as: "span",
})
</script>

<template>
  <SelectValue
    data-slot="select-value"
    v-bind="props as SelectValueProps"
  >
    <slot />
  </SelectValue>
</template>
