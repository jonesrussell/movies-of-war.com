<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Film, Bookmark } from 'lucide-vue-next';
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
                    <div class="text-sm font-medium text-primary group-hover:underline flex items-center gap-1">
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
                    <CardDescription>
                        View your saved movies
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="text-sm font-medium text-primary group-hover:underline flex items-center gap-1">
                        View Watchlist
                        <ArrowRight class="size-4" />
                    </div>
                </CardContent>
            </Card>
        </Link>
    </div>
</template>
