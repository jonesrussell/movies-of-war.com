<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Menu } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
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
const isOpen = ref(false);

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

function handleLinkClick() {
    isOpen.value = false;
}
</script>

<template>
    <Sheet v-model:open="isOpen">
        <SheetTrigger as-child>
            <Button variant="ghost" size="icon" class="lg:hidden">
                <Menu class="size-6" />
                <span class="sr-only">Open menu</span>
            </Button>
        </SheetTrigger>
        <SheetContent side="right" class="w-[300px] bg-zinc-900">
            <SheetHeader>
                <SheetTitle class="sr-only">Navigation Menu</SheetTitle>
            </SheetHeader>
            <nav class="mt-8 flex flex-col gap-4">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    @click="handleLinkClick"
                    :class="[
                        'block rounded-lg px-4 py-3 text-base font-medium transition-colors',
                        item.isButton
                            ? 'bg-red-600 text-white hover:bg-red-700'
                            : activeClass(
                                  item.href,
                                  'bg-zinc-800 text-red-500',
                                  'text-zinc-300 hover:bg-zinc-800 hover:text-white',
                              ),
                    ]"
                >
                    {{ item.label }}
                </Link>
            </nav>
        </SheetContent>
    </Sheet>
</template>
