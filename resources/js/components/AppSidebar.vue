<script setup lang="ts">
import type { User } from '@/types/models';

import { Link, usePage } from '@inertiajs/vue3';
import {
    Archive,
    BarChart3,
    Bookmark,
    BookOpen,
    Database,
    Download,
    Film,
    Folder,
    LayoutGrid,
    MessageSquare,
    Reply,
    Search,
    Settings,
    Star,
    TrendingUp,
} from 'lucide-vue-next';
import { computed } from 'vue';

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
    SidebarRail,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';

import AppLogo from './AppLogo.vue';

const page = usePage();
const auth = page.props.auth as { user?: User };

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
                  icon: Film,
                  items: [
                      {
                          title: 'Manage',
                          href: '/dashboard/movies',
                          icon: Film,
                      },
                      {
                          title: 'Archived',
                          href: '/dashboard/movies/archived',
                          icon: Archive,
                      },
                  ],
              },
              {
                  title: 'Featured Slots',
                  href: '/dashboard/featured-slots',
                  icon: Star,
              },
              {
                  title: 'Reviews',
                  href: '/dashboard/reviews',
                  icon: BookOpen,
              },
              {
                  title: 'TMDB',
                  icon: Database,
                  items: [
                      {
                          title: 'Imports',
                          href: '/dashboard/tmdb/imports',
                          icon: Download,
                      },
                      {
                          title: 'Search',
                          href: '/dashboard/tmdb/search',
                          icon: Search,
                      },
                  ],
              },
              {
                  title: 'X',
                  icon: MessageSquare,
                  items: [
                      {
                          title: 'Posts',
                          href: '/x-posts',
                          icon: MessageSquare,
                      },
                      {
                          title: 'Analytics',
                          href: '/dashboard/x-analytics',
                          icon: BarChart3,
                      },
                      {
                          title: 'Trends',
                          href: '/dashboard/x-trends',
                          icon: TrendingUp,
                      },
                      {
                          title: 'Auto-Replies',
                          href: '/dashboard/x-auto-replies',
                          icon: Reply,
                      },
                      {
                          title: 'Content Discovery',
                          href: '/dashboard/x-content-discovery',
                          icon: Search,
                      },
                      {
                          title: 'Settings',
                          href: '/dashboard/x-settings',
                          icon: Settings,
                      },
                  ],
              },
          ]
        : [
              { title: 'Browse Movies', href: '/movies', icon: Film },
              { title: 'My Watchlist', href: '/watchlist', icon: Bookmark },
              { title: 'My Reviews', href: '/my-reviews', icon: Star },
          ]),
]);

const footerNavItems = computed<NavItem[]>(() =>
    auth?.user?.is_admin
        ? [
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
          ]
        : [],
);
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
            <NavFooter v-if="footerNavItems.length" :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
        <SidebarRail />
    </Sidebar>
    <slot />
</template>
