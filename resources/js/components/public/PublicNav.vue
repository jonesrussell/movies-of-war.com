<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import { useActive } from '@/composables/useActive';

interface Props {
    canRegister?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canRegister: false,
});

const page = usePage();
const auth = computed(() => page.props.auth as { user: any });
const { activeClass } = useActive();

interface NavItem {
    href: string;
    label: string;
    isButton?: boolean;
}

const navItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            href: '/movies',
            label: 'Browse Movies',
        },
    ];

    if (auth.value?.user) {
        items.push(
            {
                href: '/watchlist',
                label: 'Watchlist',
            },
            {
                href: '/dashboard',
                label: 'Dashboard',
                isButton: true,
            },
        );
    } else {
        items.push(
            {
                href: '/login',
                label: 'Login',
            },
        );

        if (props.canRegister) {
            items.push({
                href: '/register',
                label: 'Register',
                isButton: true,
            });
        }
    }

    return items;
});
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
