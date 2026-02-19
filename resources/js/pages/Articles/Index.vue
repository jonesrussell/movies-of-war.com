<script setup lang="ts">
import type { Article } from '@/types/models';

import { Head, Link, router } from '@inertiajs/vue3';
import { Clock, Search as SearchIcon } from 'lucide-vue-next';
import { ref, toRef } from 'vue';

import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicSection from '@/components/public/PublicSection.vue';
import SectionHeader from '@/components/public/SectionHeader.vue';
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

        <PublicSection spacing="lg">
            <PublicContainer>
                <SectionHeader
                    kicker="Intelligence"
                    title="War Film Articles"
                    description="Analysis and coverage of war cinema"
                />

                <!-- Search -->
                <div class="relative mt-8">
                    <SearchIcon
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-[--intel-text-muted]"
                    />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search articles..."
                        class="w-full rounded-md border border-[--intel-border] bg-[--intel-bg-surface] py-2.5 pr-4 pl-10 text-sm text-[--intel-text-primary] placeholder:text-[--intel-text-muted] focus:border-[--intel-accent] focus:ring-1 focus:ring-[--intel-accent] focus:outline-none"
                        @input="debouncedSearch"
                    />
                </div>

                <!-- Articles list -->
                <div v-if="articles.data.length > 0" class="mt-8 space-y-6">
                    <article
                        v-for="article in articles.data"
                        :key="article.id"
                        class="group rounded-md border border-[--intel-border] bg-[--intel-bg-surface] p-4 transition-colors hover:border-[--intel-border-bright]"
                    >
                        <Link :href="`/articles/${article.slug}`" class="block">
                            <h2
                                class="font-[family-name:var(--font-mono-display)] text-lg font-semibold text-[--intel-text-primary] transition-colors group-hover:text-blue-500"
                            >
                                {{ article.title }}
                            </h2>

                            <p
                                v-if="article.excerpt"
                                class="mt-2 line-clamp-2 text-sm text-[--intel-text-body]"
                            >
                                {{ article.excerpt }}
                            </p>

                            <div
                                class="mt-3 flex flex-wrap items-center gap-3 font-[family-name:var(--font-mono-display)] text-xs text-[--intel-text-muted]"
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
                                <span v-if="article.news_source">
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
                                    class="rounded-sm border border-[--intel-border] bg-[--intel-bg-elevated] px-2 py-0.5 font-[family-name:var(--font-mono-display)] text-xs text-[--intel-text-muted]"
                                >
                                    {{ tag.name }}
                                </span>
                            </div>

                            <div
                                v-if="
                                    article.articleable &&
                                    article.articleable_type?.includes('Movie')
                                "
                                class="mt-3 text-xs text-blue-500"
                            >
                                Related to:
                                {{ (article.articleable as any).title }}
                            </div>
                        </Link>
                    </article>
                </div>

                <div
                    v-else
                    class="mt-8 rounded-md border border-[--intel-border] bg-[--intel-bg-surface] p-12 text-center"
                >
                    <p
                        class="font-[family-name:var(--font-mono-display)] text-lg text-[--intel-text-primary]"
                    >
                        NO INTELLIGENCE REPORTS FOUND
                    </p>
                    <p class="mt-2 text-sm text-[--intel-text-muted]">
                        No articles match your current search criteria.
                    </p>
                </div>

                <!-- Pagination -->
                <nav
                    v-if="articles.last_page > 1"
                    class="mt-8 flex justify-center gap-1 font-[family-name:var(--font-mono-display)]"
                >
                    <template v-for="link in articles.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded-md px-3 py-2 text-sm transition-colors"
                            :class="
                                link.active
                                    ? 'bg-blue-600 text-white'
                                    : 'text-[--intel-text-muted] hover:bg-[--intel-bg-elevated] hover:text-[--intel-text-primary]'
                            "
                        >
                            {{ link.label }}
                        </Link>
                        <span
                            v-else
                            class="rounded-md px-3 py-2 text-sm text-[--intel-text-muted]/50"
                        >
                            {{ link.label }}
                        </span>
                    </template>
                </nav>
            </PublicContainer>
        </PublicSection>
    </PublicLayout>
</template>
