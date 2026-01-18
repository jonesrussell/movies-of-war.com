<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useActiveUrl } from '@/composables/use-active-url';
import { type NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { urlIsActive } = useActiveUrl();

const isItemActive = (item: NavItem): boolean => {
    if (item.href) {
        return urlIsActive(item.href);
    }
    if (item.items) {
        return item.items.some((subItem) => isItemActive(subItem));
    }
    return false;
};
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <SidebarMenuItem v-if="!item.items && item.href">
                    <SidebarMenuButton
                        as-child
                        :is-active="urlIsActive(item.href)"
                        :tooltip="item.title"
                    >
                        <Link :href="item.href">
                            <component v-if="item.icon" :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                <SidebarMenuItem v-else-if="item.items">
                    <Collapsible :default-open="isItemActive(item)">
                        <template #default="{ open }">
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton
                                    :is-active="isItemActive(item)"
                                    :tooltip="item.title"
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                    />
                                    <span>{{ item.title }}</span>
                                    <ChevronRight
                                        class="ml-auto transition-transform duration-200"
                                        :class="{ 'rotate-90': open }"
                                    />
                                </SidebarMenuButton>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <SidebarMenuSub>
                                    <SidebarMenuSubItem
                                        v-for="subItem in item.items"
                                        :key="subItem.title"
                                    >
                                        <SidebarMenuSubButton
                                            as-child
                                            :is-active="
                                                subItem.href
                                                    ? urlIsActive(subItem.href)
                                                    : false
                                            "
                                        >
                                            <Link
                                                v-if="subItem.href"
                                                :href="subItem.href"
                                            >
                                                <component
                                                    v-if="subItem.icon"
                                                    :is="subItem.icon"
                                                />
                                                <span>{{ subItem.title }}</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </template>
                    </Collapsible>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
