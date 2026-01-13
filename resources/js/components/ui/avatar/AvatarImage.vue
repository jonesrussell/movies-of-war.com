<script setup lang="ts">
import type { AvatarImageProps } from "reka-ui"
import { computed } from "vue"
import { AvatarImage } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<AvatarImageProps>()

const filteredProps = computed(() => filterUndefinedProps(props))
</script>

<template>
  <AvatarImage
    data-slot="avatar-image"
    v-bind="filteredProps"
    class="aspect-square size-full"
  >
    <slot />
  </AvatarImage>
</template>
