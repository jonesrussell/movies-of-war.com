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
        class="review-content prose prose-lg leading-relaxed break-words text-[--intel-text-body] prose-neutral prose-headings:text-[--intel-text-primary] prose-p:text-[--intel-text-body] prose-a:text-[--intel-alert] prose-a:no-underline hover:prose-a:underline prose-strong:text-[--intel-text-primary] prose-code:rounded prose-code:bg-[--intel-bg-elevated] prose-code:px-1 prose-code:text-[--intel-text-body] prose-code:before:content-none prose-code:after:content-none"
    >
        <div v-if="collapsed && contentExcerpt" class="whitespace-pre-wrap">
            {{ contentExcerpt }}
        </div>
        <!-- eslint-disable-next-line vue/no-v-html -- contentHtml is server-rendered Markdown (MarkdownRenderer with html_input strip, no raw HTML) -->
        <div v-else v-html="contentHtml" />
    </div>
</template>
