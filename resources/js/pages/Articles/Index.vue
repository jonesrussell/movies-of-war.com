<script setup lang="ts">
import type { Article } from '@/types/models';

import { Head, Link, router } from '@inertiajs/vue3';
import { Clock, Search as SearchIcon } from 'lucide-vue-next';
import { ref, toRef } from 'vue';

import PublicLayout from '@/layouts/PublicLayout.vue';

interface Props {
    articles: {
        data: Article[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
    };
    queryParams: {
        search?: string;
        tag?: string;
    };
}

const props = defineProps<Props>();
const queryParams = toRef(props, 'queryParams');

const search = ref(queryParams.value.search ?? '');

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const debouncedSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            '/articles',
            {
                search: search.value || undefined,
                tag: queryParams.value.tag || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    }, 300);
};

const formatDate = (dateStr: string | null): string => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <PublicLayout>
        <Head title="Articles" />

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white">Articles</h1>
                <p class="mt-2 text-zinc-400">
                    War film analysis, news, and features
                </p>
            </div>

            <!-- Search -->
            <div class="relative mb-8">
                <SearchIcon
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-500"
                />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search articles..."
                    class="w-full rounded-lg border border-zinc-800 bg-zinc-900 py-2.5 pr-4 pl-10 text-sm text-white placeholder:text-zinc-500 focus:border-red-500 focus:ring-1 focus:ring-red-500 focus:outline-none"
                    @input="debouncedSearch"
                />
            </div>

            <!-- Articles list -->
            <div v-if="articles.data.length > 0" class="space-y-6">
                <article
                    v-for="article in articles.data"
                    :key="article.id"
                    class="group rounded-xl border border-zinc-800 bg-zinc-900/50 p-6 transition-colors hover:border-zinc-700"
                >
                    <Link :href="`/articles/${article.slug}`" class="block">
                        <h2
                            class="text-lg font-semibold text-white transition-colors group-hover:text-red-500"
                        >
                            {{ article.title }}
                        </h2>

                        <p
                            v-if="article.excerpt"
                            class="mt-2 line-clamp-2 text-sm text-zinc-400"
                        >
                            {{ article.excerpt }}
                        </p>

                        <div
                            class="mt-3 flex flex-wrap items-center gap-3 text-xs text-zinc-500"
                        >
                            <span
                                v-if="article.published_at"
                                class="flex items-center gap-1"
                            >
                                <Clock class="size-3" />
                                {{ formatDate(article.published_at) }}
                            </span>
                            <span v-if="article.author"
                                >by {{ article.author }}</span
                            >
                            <span
                                v-if="article.news_source"
                                class="text-zinc-600"
                            >
                                {{ article.news_source.name }}
                            </span>
                        </div>

                        <div
                            v-if="article.tags && article.tags.length > 0"
                            class="mt-3 flex flex-wrap gap-1"
                        >
                            <span
                                v-for="tag in article.tags.slice(0, 4)"
                                :key="tag.id"
                                class="rounded-full bg-zinc-800 px-2 py-0.5 text-xs text-zinc-300"
                            >
                                {{ tag.name }}
                            </span>
                        </div>

                        <div
                            v-if="
                                article.articleable &&
                                article.articleable_type?.includes('Movie')
                            "
                            class="mt-3 text-xs text-red-500"
                        >
                            Related to: {{ (article.articleable as any).title }}
                        </div>
                    </Link>
                </article>
            </div>

            <div v-else class="py-16 text-center text-zinc-500">
                <p>No articles found.</p>
            </div>

            <!-- Pagination -->
            <nav
                v-if="articles.last_page > 1"
                class="mt-8 flex justify-center gap-1"
            >
                <template v-for="link in articles.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg px-3 py-2 text-sm transition-colors"
                        :class="
                            link.active
                                ? 'bg-red-600 text-white'
                                : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'
                        "
                    >
                        <span v-html="link.label" />
                    </Link>
                    <span
                        v-else
                        class="rounded-lg px-3 py-2 text-sm text-zinc-600"
                    >
                        <span v-html="link.label" />
                    </span>
                </template>
            </nav>
        </div>
    </PublicLayout>
</template>
