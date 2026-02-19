<script setup lang="ts">
import type { Article } from '@/types/models';

import { Head, Link } from '@inertiajs/vue3';
import { Calendar, ExternalLink, Eye, User } from 'lucide-vue-next';

import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicSection from '@/components/public/PublicSection.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Props {
    article: Article;
}

defineProps<Props>();

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
        <Head :title="article.title" />

        <PublicSection spacing="lg">
            <PublicContainer class="max-w-3xl">
                <!-- Back link -->
                <Link
                    href="/articles"
                    class="mb-6 inline-flex items-center gap-1 font-[family-name:var(--font-mono-display)] text-sm text-[--intel-text-muted] transition-colors hover:text-[--intel-text-primary]"
                >
                    &lt; BACK TO ARTICLES
                </Link>

                <!-- Article header -->
                <header class="mb-8">
                    <h1
                        class="font-[family-name:var(--font-mono-display)] text-3xl font-bold text-[--intel-text-primary]"
                    >
                        {{ article.title }}
                    </h1>

                    <div
                        class="mt-4 flex flex-wrap items-center gap-4 font-[family-name:var(--font-mono-display)] text-xs text-[--intel-text-muted]"
                    >
                        <span
                            v-if="article.published_at"
                            class="flex items-center gap-1"
                        >
                            <Calendar class="size-4" />
                            {{ formatDate(article.published_at) }}
                        </span>
                        <span
                            v-if="article.author"
                            class="flex items-center gap-1"
                        >
                            <User class="size-4" />
                            {{ article.author }}
                        </span>
                        <span
                            v-if="article.view_count"
                            class="flex items-center gap-1"
                        >
                            <Eye class="size-4" />
                            {{ article.view_count }} views
                        </span>
                    </div>

                    <!-- Tags -->
                    <div
                        v-if="article.tags && article.tags.length > 0"
                        class="mt-4 flex flex-wrap gap-2"
                    >
                        <span
                            v-for="tag in article.tags"
                            :key="tag.id"
                            class="rounded-sm border border-[--intel-border] bg-[--intel-bg-elevated] px-2 py-0.5 font-[family-name:var(--font-mono-display)] text-xs text-[--intel-text-muted]"
                        >
                            {{ tag.name }}
                        </span>
                    </div>

                    <!-- Associated movie -->
                    <div
                        v-if="
                            article.articleable &&
                            article.articleable_type?.includes('Movie')
                        "
                        class="mt-4 rounded-md border border-[--intel-border] bg-[--intel-bg-surface] p-4"
                    >
                        <p
                            class="mb-1 font-[family-name:var(--font-mono-display)] text-xs text-[--intel-text-muted] uppercase"
                        >
                            Related movie
                        </p>
                        <Link
                            :href="`/movies/${(article.articleable as any).slug}`"
                            class="text-blue-500 transition-colors hover:text-blue-400"
                        >
                            {{ (article.articleable as any).title }}
                        </Link>
                    </div>
                </header>

                <!-- Featured image -->
                <div
                    v-if="article.image_url"
                    class="mb-8 overflow-hidden rounded-md border border-[--intel-border]"
                >
                    <img
                        :src="article.image_url"
                        :alt="article.title"
                        class="w-full object-cover"
                    />
                </div>

                <!-- Article content (trusted CMS content from backend) -->
                <!-- eslint-disable vue/no-v-html -->
                <div
                    class="prose max-w-none prose-invert prose-headings:font-[family-name:var(--font-mono-display)] prose-headings:text-[--intel-text-primary] prose-p:text-[--intel-text-secondary] prose-a:text-blue-500 prose-a:no-underline hover:prose-a:text-blue-400 prose-strong:text-[--intel-text-primary]"
                    v-html="article.content"
                />
                <!-- eslint-enable vue/no-v-html -->

                <!-- Source link -->
                <div
                    v-if="article.url"
                    class="mt-8 rounded-md border border-[--intel-border] bg-[--intel-bg-surface] p-4"
                >
                    <p
                        class="mb-2 font-[family-name:var(--font-mono-display)] text-xs text-[--intel-text-muted] uppercase"
                    >
                        Original source
                    </p>
                    <a
                        :href="article.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 text-sm text-blue-500 transition-colors hover:text-blue-400"
                    >
                        <ExternalLink class="size-4" />
                        Read original article
                        <span
                            v-if="article.news_source"
                            class="text-[--intel-text-muted]"
                        >
                            on {{ article.news_source.name }}
                        </span>
                    </a>
                </div>
            </PublicContainer>
        </PublicSection>
    </PublicLayout>
</template>
