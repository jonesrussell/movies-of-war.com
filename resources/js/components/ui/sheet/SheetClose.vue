<script setup lang="ts">
import type { DialogCloseProps } from "reka-ui"
import { computed } from "vue"
import { DialogClose } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<DialogCloseProps>()

const filteredProps = computed(() => filterUndefinedProps(props))
</script>

<template>
  <DialogClose
    data-slot="sheet-close"
    v-bind="filteredProps"
  >
    <slot />
  </DialogClose>
</template>
