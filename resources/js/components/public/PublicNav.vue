<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { toRef } from 'vue';

import { useActive } from '@/composables/use-active';
import { usePublicNav } from '@/composables/use-public-nav';

interface Props {
    canRegister?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canRegister: false,
});

const { activeClass } = useActive();
const canRegisterRef = toRef(props, 'canRegister');
const { navItems } = usePublicNav(canRegisterRef.value);
</script>

<template>
    <nav class="hidden items-center gap-6 lg:flex">
        <Link
            v-for="item in navItems"
            :key="item.href"
            :href="item.href"
            :class="[
                'tracking-wide',
                item.isButton
                    ? 'rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white transition-colors hover:bg-blue-700'
                    : activeClass(
                          item.href,
                          'border-b-2 border-blue-500 text-white',
                          'text-[--intel-text-body] transition-colors hover:text-[--intel-text-primary]',
                      ),
            ]"
        >
            {{ item.label }}
        </Link>
    </nav>
</template>
