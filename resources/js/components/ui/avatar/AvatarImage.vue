<script setup lang="ts">
import type { AvatarImageProps } from "reka-ui"
import { computed } from "vue"
import { AvatarImage } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<AvatarImageProps>()

const filteredProps = computed(() => {
  const filtered = filterUndefinedProps(props);
  // Ensure src is always a string if provided (required by component)
  if (props.src !== undefined && !('src' in filtered)) {
    return { ...filtered, src: props.src };
  }
  return filtered;
})
</script>

<template>
  <AvatarImage
    v-if="props.src"
    data-slot="avatar-image"
    v-bind="filteredProps"
    class="aspect-square size-full"
  >
    <slot />
  </AvatarImage>
</template>
