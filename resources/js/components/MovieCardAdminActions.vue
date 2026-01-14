<script setup lang="ts">
import type { Movie } from '@/types';

import { router } from '@inertiajs/vue3';
import { Archive, CheckCircle, XCircle } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';

interface Props {
    movie: Movie;
}

const props = defineProps<Props>();

const isPublished = props.movie.status === 'published';

function handlePublish(e: Event) {
    e.preventDefault();
    e.stopPropagation();

    router.post(
        `/movies/${props.movie.id}/publish`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function handleUnpublish(e: Event) {
    e.preventDefault();
    e.stopPropagation();

    router.post(
        `/movies/${props.movie.id}/unpublish`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function handleArchive(e: Event) {
    e.preventDefault();
    e.stopPropagation();

    if (confirm(`Archive "${props.movie.title}"?`)) {
        router.post(
            `/movies/${props.movie.id}/archive`,
            {},
            {
                preserveScroll: true,
            },
        );
    }
}
</script>

<template>
    <div class="flex gap-2">
        <Button
            v-if="isPublished"
            @click="handleUnpublish"
            variant="outline"
            size="sm"
            class="flex-1"
        >
            <XCircle class="size-3" />
            Unpublish
        </Button>
        <Button
            v-else
            @click="handlePublish"
            variant="default"
            size="sm"
            class="flex-1"
        >
            <CheckCircle class="size-3" />
            Publish
        </Button>
        <Button
            @click="handleArchive"
            variant="outline"
            size="sm"
            class="flex-1"
        >
            <Archive class="size-3" />
            Archive
        </Button>
    </div>
</template>
