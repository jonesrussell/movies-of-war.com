import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export interface NavItem {
    href: string;
    label: string;
    isButton?: boolean;
}

export function usePublicNav(canRegister = false) {
    const page = usePage();
    const auth = computed(() => page.props.auth as { user?: import('@/types/models').User });

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
            items.push({
                href: '/login',
                label: 'Login',
            });

            if (canRegister) {
                items.push({
                    href: '/register',
                    label: 'Register',
                    isButton: true,
                });
            }
        }

        return items;
    });

    return {
        auth,
        navItems,
    };
}
