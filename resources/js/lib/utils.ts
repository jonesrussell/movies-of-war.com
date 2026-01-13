import { type InertiaLinkProps } from '@inertiajs/vue3';
import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export function filterUndefinedProps<T extends Record<string, unknown>>(
    props: T,
): Partial<T> {
    return Object.fromEntries(
        Object.entries(props).filter(([, value]) => value !== undefined),
    ) as Partial<T>;
}

export function filterUndefinedReactive<T extends Record<string, unknown>>(
    props: T,
): T {
    const result = {} as T;
    for (const key in props) {
        if (props[key] !== undefined) {
            result[key] = props[key];
        }
    }
    return result;
}
