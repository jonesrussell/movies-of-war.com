<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import type { OTPInputEmits, OTPInputProps } from "vue-input-otp"
import { computed } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { useForwardPropsEmits } from "reka-ui"
import { OTPInput } from "vue-input-otp"
import { cn, filterUndefinedProps } from "@/lib/utils"

const props = defineProps<OTPInputProps & { class?: HTMLAttributes["class"] }>()

const emits = defineEmits<OTPInputEmits>()

const delegatedProps = reactiveOmit(props, "class")
const filteredProps = computed(() => {
  const filtered = filterUndefinedProps(delegatedProps);
  // Ensure maxlength is always provided (required by component)
  if (!('maxlength' in filtered) && props.maxlength !== undefined) {
    return { ...filtered, maxlength: props.maxlength };
  }
  return filtered;
})

const forwarded = useForwardPropsEmits(filteredProps, emits)
</script>

<template>
  <OTPInput
    v-slot="slotProps"
    v-bind="forwarded"
    :container-class="cn('flex items-center gap-2 has-disabled:opacity-50', props.class)"
    data-slot="input-otp"
    class="disabled:cursor-not-allowed"
  >
    <slot v-bind="slotProps" />
  </OTPInput>
</template>
