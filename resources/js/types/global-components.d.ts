import type { Button } from '@/components/ui/button';
import type { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import type { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';

declare module 'vue' {
    export interface GlobalComponents {
        Link: typeof Link;
        Button: typeof Button;
        Sheet: typeof Sheet;
        SheetContent: typeof SheetContent;
        SheetHeader: typeof SheetHeader;
        SheetTitle: typeof SheetTitle;
        SheetTrigger: typeof SheetTrigger;
    }
}
