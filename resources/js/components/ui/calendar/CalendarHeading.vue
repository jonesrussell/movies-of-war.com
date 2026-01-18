<script lang="ts" setup>
import type { CalendarHeadingProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { computed } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { CalendarHeading, useForwardProps } from "reka-ui"
import { cn, filterUndefinedProps } from "@/lib/utils"

const props = defineProps<CalendarHeadingProps & { class?: HTMLAttributes["class"] }>()

defineSlots<{
  default: (props: { headingValue: string }) => any
}>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedPropsRaw = useForwardProps(delegatedProps)

const forwardedProps = computed(() => filterUndefinedProps(forwardedPropsRaw.value as Record<string, unknown>))
</script>

<template>
  <CalendarHeading
    v-slot="{ headingValue }"
    data-slot="calendar-heading"
    :class="cn('text-sm font-medium', props.class)"
    v-bind="forwardedProps"
  >
    <slot :heading-value>
      {{ headingValue }}
    </slot>
  </CalendarHeading>
</template>
