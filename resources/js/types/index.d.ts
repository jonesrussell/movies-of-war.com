import type { User } from './models';
import type { LucideIcon } from 'lucide-vue-next';

import { type InertiaLinkProps } from '@inertiajs/vue3';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href?: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    items?: NavItem[];
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    appUrl: string;
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
};

export type BreadcrumbItemType = BreadcrumbItem;

export * from './models';
