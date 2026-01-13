<script setup lang="ts">
import type { NavigationMenuItemProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { computed } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { NavigationMenuItem } from "reka-ui"
import { cn, filterUndefinedProps } from "@/lib/utils"

const props = defineProps<NavigationMenuItemProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")
const filteredProps = computed(() => filterUndefinedProps(delegatedProps))
</script>

<template>
  <NavigationMenuItem
    data-slot="navigation-menu-item"
    v-bind="filteredProps"
    :class="cn('relative', props.class)"
  >
    <slot />
  </NavigationMenuItem>
</template>
