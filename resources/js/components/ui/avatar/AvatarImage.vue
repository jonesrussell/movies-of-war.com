<script setup lang="ts">
import type { AvatarImageProps } from "reka-ui"
import { computed } from "vue"
import { AvatarImage } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<AvatarImageProps>()

const filteredProps = computed(() => {
  // Remove src from props to pass separately
  const { src, ...rest } = props;
  return filterUndefinedProps(rest);
})
</script>

<template>
  <AvatarImage
    v-if="props.src"
    data-slot="avatar-image"
    :src="(props.src as string)"
    v-bind="filteredProps"
    class="aspect-square size-full"
  >
    <slot />
  </AvatarImage>
</template>
