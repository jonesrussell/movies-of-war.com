import type { NavItem } from '@/types';

import { usePage } from '@inertiajs/vue3';
import {
    Download,
    FileText,
    Film,
    type LucideIcon,
    MessageCircle,
    MessageSquare,
    Star,
    Users,
} from 'lucide-vue-next';
import { computed, type ComputedRef } from 'vue';

interface NorthcloudNavItem {
    title: string;
    href: string;
    icon: string;
}

const iconMap: Record<string, LucideIcon> = {
    Download,
    FileText,
    Film,
    MessageCircle,
    MessageSquare,
    Star,
    Users,
};

export function useNorthcloudNav(): { items: ComputedRef<NavItem[]> } {
    const page = usePage();

    const items = computed<NavItem[]>(() => {
        const northcloud = page.props.northcloud as
            | { navigation?: NorthcloudNavItem[] }
            | undefined;
        const nav = northcloud?.navigation ?? [];

        return nav.map((item): NavItem => {
            const icon = iconMap[item.icon];
            return {
                title: item.title,
                href: item.href,
                ...(icon !== undefined && { icon }),
            };
        });
    });

    return { items };
}
