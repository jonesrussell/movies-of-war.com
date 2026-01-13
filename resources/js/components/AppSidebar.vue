<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bookmark, BookOpen, Database, Film, Folder, LayoutGrid, Star } from 'lucide-vue-next';
import { computed } from 'vue';

import AppLogo from './AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';





const page = usePage();
const auth = page.props.auth as { user: any };

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard().url,
        icon: LayoutGrid,
    },
    ...(auth?.user?.is_admin
        ? [
              {
                  title: 'Movies',
                  href: '/dashboard/movies',
                  icon: Film,
              },
          ]
        : []),
    {
        title: 'Watchlist',
        href: '/watchlist',
        icon: Bookmark,
    },
    ...(auth?.user?.is_admin
        ? [
              {
                  title: 'TMDB',
                  href: '/dashboard/tmdb-imports',
                  icon: Database,
              },
              {
                  title: 'Featured Slots',
                  href: '/featured-slots',
                  icon: Star,
              },
          ]
        : []),
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard().url">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
