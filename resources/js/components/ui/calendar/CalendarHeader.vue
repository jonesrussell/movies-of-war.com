<script lang="ts" setup>
import type { CalendarHeaderProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { computed } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { CalendarHeader, useForwardProps } from "reka-ui"
import { cn, filterUndefinedProps } from "@/lib/utils"

const props = defineProps<CalendarHeaderProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedPropsRaw = useForwardProps(delegatedProps)

const forwardedProps = computed(() => filterUndefinedProps(forwardedPropsRaw.value as Record<string, unknown>))
</script>

<template>
  <CalendarHeader
    data-slot="calendar-header"
    :class="cn('flex justify-center pt-1 relative items-center w-full px-8', props.class)"
    v-bind="forwardedProps"
  >
    <slot />
  </CalendarHeader>
</template>
