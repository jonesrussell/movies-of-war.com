<script setup lang="ts">
interface Props {
    /** Rendered HTML from Markdown (server-sanitized). */
    contentHtml: string;
    /** Optional plain-text excerpt for collapsed preview (e.g. "Read more"). */
    contentExcerpt?: string | null;
    /** When true, show excerpt only (collapsed state). */
    collapsed?: boolean;
    /** Size: 'sm' for cards, default for full review. */
    size?: 'sm' | 'default';
}

withDefaults(defineProps<Props>(), {
    contentExcerpt: null,
    collapsed: false,
    size: 'default',
});
</script>

<template>
    <div
        class="review-content prose prose-lg break-words text-zinc-300 leading-relaxed prose-neutral dark:prose-invert prose-headings:text-white prose-p:text-zinc-300 prose-a:text-red-500 prose-a:no-underline hover:prose-a:underline prose-strong:text-white prose-code:rounded prose-code:bg-zinc-800 prose-code:px-1 prose-code:text-zinc-300 prose-code:before:content-none prose-code:after:content-none"
    >
        <div v-if="collapsed && contentExcerpt" class="whitespace-pre-wrap">
            {{ contentExcerpt }}
        </div>
        <!-- eslint-disable-next-line vue/no-v-html -- contentHtml is server-rendered Markdown (MarkdownRenderer with html_input strip, no raw HTML) -->
        <div v-else v-html="contentHtml" />
    </div>
</template>
