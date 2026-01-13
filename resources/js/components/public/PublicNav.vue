<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { toRef } from 'vue';

import { usePublicNav } from '@/composables/use-public-nav';
import { useActive } from '@/composables/useActive';

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
                item.isButton
                    ? 'rounded-lg bg-red-600 px-4 py-2 font-semibold text-white transition-colors hover:bg-red-700'
                    : activeClass(
                          item.href,
                          'text-red-500',
                          'text-zinc-300 transition-colors hover:text-white',
                      ),
            ]"
        >
            {{ item.label }}
        </Link>
    </nav>
</template>
