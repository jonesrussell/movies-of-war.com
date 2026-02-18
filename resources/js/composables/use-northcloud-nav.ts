import type { NavItem } from '@/types';

import { usePage } from '@inertiajs/vue3';
import { FileText, type LucideIcon, Users } from 'lucide-vue-next';
import { computed, type ComputedRef } from 'vue';

interface NorthcloudNavItem {
    title: string;
    href: string;
    icon: string;
}

const iconMap: Record<string, LucideIcon> = {
    FileText,
    Users,
};

export function useNorthcloudNav(): { items: ComputedRef<NavItem[]> } {
    const page = usePage();

    const items = computed<NavItem[]>(() => {
        const northcloud = page.props.northcloud as
            | { navigation?: NorthcloudNavItem[] }
            | undefined;
        const nav = northcloud?.navigation ?? [];

        return nav
            .map((item) => ({
                title: item.title,
                href: item.href,
                icon: iconMap[item.icon],
            }))
            .filter((item) => item.icon !== undefined);
    });

    return { items };
}
