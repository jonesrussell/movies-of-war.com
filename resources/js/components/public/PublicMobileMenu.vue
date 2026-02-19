<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Menu } from 'lucide-vue-next';
import { ref, toRef } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
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
const isOpen = ref(false);

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
        <SheetContent side="right" class="w-[300px] bg-[--intel-bg-surface]">
            <SheetHeader>
                <SheetTitle class="sr-only">Navigation Menu</SheetTitle>
            </SheetHeader>
            <nav class="mt-8 flex flex-col">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    @click="handleLinkClick"
                    :class="[
                        'block border-b border-[--intel-border] px-4 py-3 text-base font-medium transition-colors',
                        item.isButton
                            ? 'bg-blue-600 text-white hover:bg-blue-700'
                            : activeClass(
                                  item.href,
                                  'bg-[--intel-bg-surface] text-blue-500',
                                  'text-[--intel-text-body] hover:bg-[--intel-bg-surface] hover:text-[--intel-text-primary]',
                              ),
                    ]"
                >
                    {{ item.label }}
                </Link>
            </nav>
        </SheetContent>
    </Sheet>
</template>
