<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Home } from 'lucide-vue-next';
</script>

<template>
    <Head title="Page Not Found - Movies of War" />

    <div
        class="flex min-h-screen items-center justify-center bg-[--intel-bg-base] px-4"
    >
        <div class="text-center">
            <img
                src="/images/illustrations/404.png"
                alt="404 - Page not found"
                class="mx-auto mb-8 h-64 w-auto"
            />

            <h1 class="mb-4 text-6xl font-bold text-[--intel-text-primary]">
                404
            </h1>
            <h2 class="mb-2 text-2xl font-semibold text-[--intel-text-body]">
                Page Not Found
            </h2>
            <p class="mb-8 text-[--intel-text-muted]">
                The page you're looking for has gone AWOL.
            </p>

            <div
                class="flex flex-col items-center gap-4 sm:flex-row sm:justify-center"
            >
                <Link
                    href="/"
                    class="inline-flex items-center gap-2 rounded-lg bg-[--intel-alert] px-6 py-3 font-semibold text-white transition-colors hover:bg-[--intel-alert-dim]"
                >
                    <Home class="size-5" />
                    Return to Base
                </Link>

                <Link
                    href="/movies"
                    class="inline-flex items-center gap-2 rounded-lg border border-[--intel-border-bright] bg-[--intel-bg-surface] px-6 py-3 font-semibold text-[--intel-text-primary] transition-colors hover:bg-[--intel-bg-elevated]"
                >
                    Browse Movies
                </Link>
            </div>
        </div>
    </div>
</template>
