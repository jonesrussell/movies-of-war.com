<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Database } from 'lucide-vue-next';

interface Props {
    draftCount: number;
}

defineProps<Props>();
</script>

<template>
    <Link href="/dashboard/tmdb-imports" class="group block">
        <Card class="transition-colors hover:bg-accent">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Database class="size-5" />
                    TMDB Imports
                </CardTitle>
                <CardDescription>
                    {{ draftCount }} {{ draftCount === 1 ? 'movie' : 'movies' }} awaiting review
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="text-sm font-medium text-primary group-hover:underline flex items-center gap-1">
                    Manage Imports
                    <ArrowRight class="size-4" />
                </div>
            </CardContent>
        </Card>
    </Link>
</template>
