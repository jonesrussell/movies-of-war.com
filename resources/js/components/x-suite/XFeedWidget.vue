<script setup lang="ts">
import { onMounted, ref } from 'vue';

interface XPost {
    id: number;
    content: string | null;
    x_post_id: string | null;
    published_at: string | null;
}

const posts = ref<XPost[]>([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await fetch('/api/x-feed');
        const data = await response.json();
        posts.value = data;
    } catch (error) {
        console.error('Failed to load X feed:', error);
    } finally {
        loading.value = false;
    }
});

function getXPostUrl(tweetId: string): string {
    return `https://x.com/i/web/status/${tweetId}`;
}
</script>

<template>
    <div v-if="!loading && posts.length > 0" class="rounded-lg bg-zinc-900 p-6">
        <h3 class="mb-4 text-lg font-semibold text-white">Latest from X</h3>
        <div class="space-y-4">
            <div
                v-for="post in posts"
                :key="post.id"
                class="rounded-lg bg-zinc-800 p-4"
            >
                <div class="text-sm text-zinc-300">{{ post.content }}</div>
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-xs text-zinc-500">
                        {{
                            post.published_at
                                ? new Date(
                                      post.published_at,
                                  ).toLocaleDateString()
                                : ''
                        }}
                    </span>
                    <a
                        v-if="post.x_post_id"
                        :href="getXPostUrl(post.x_post_id)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-xs text-blue-500 hover:text-blue-400"
                    >
                        View on X
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
