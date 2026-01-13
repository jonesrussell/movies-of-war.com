<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Bookmark, Film } from 'lucide-vue-next';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
</script>

<template>
    <div class="grid gap-6 sm:grid-cols-2">
        <Link href="/movies" class="group block">
            <Card class="transition-colors hover:bg-accent">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Film class="size-5" />
                        Browse Movies
                    </CardTitle>
                    <CardDescription>
                        Explore our collection of war films
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        class="flex items-center gap-1 text-sm font-medium text-primary group-hover:underline"
                    >
                        View All Movies
                        <ArrowRight class="size-4" />
                    </div>
                </CardContent>
            </Card>
        </Link>

        <Link href="/watchlist" class="group block">
            <Card class="transition-colors hover:bg-accent">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Bookmark class="size-5" />
                        My Watchlist
                    </CardTitle>
                    <CardDescription> View your saved movies </CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        class="flex items-center gap-1 text-sm font-medium text-primary group-hover:underline"
                    >
                        View Watchlist
                        <ArrowRight class="size-4" />
                    </div>
                </CardContent>
            </Card>
        </Link>
    </div>
</template>
