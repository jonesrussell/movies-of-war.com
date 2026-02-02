<script setup lang="ts">
import type { Review, ReviewComment } from '@/types/models';

import { Form, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    destroy as destroyComment,
    update as updateComment,
} from '@/routes/review-comments';
import { store as storeComment } from '@/routes/reviews/comments';

interface Props {
    review: Review;
    /** When true, show the add-comment form (user is authenticated). */
    canComment?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canComment: false,
});

const emit = defineEmits<{
    'comment-added': [];
    'comment-updated': [comment: ReviewComment];
    'comment-deleted': [comment: ReviewComment];
}>();

const editingCommentId = ref<number | null>(null);

const comments = computed(() => props.review.comments ?? []);

const commentFormAction = computed(() =>
    storeComment.url({ review: props.review.id }),
);

function startEdit(comment: ReviewComment) {
    editingCommentId.value = comment.id;
}

function cancelEdit() {
    editingCommentId.value = null;
}

function deleteComment(comment: ReviewComment) {
    if (!confirm('Delete this comment?')) return;
    router.delete(destroyComment.url({ comment: comment.id }), {
        preserveScroll: true,
        onSuccess: () => emit('comment-deleted', comment),
    });
}
</script>

<template>
    <div class="mt-6 border-t border-zinc-800 pt-6">
        <h3 class="mb-4 text-sm font-semibold text-zinc-300">
            {{ review.comments_count }} comment{{
                review.comments_count !== 1 ? 's' : ''
            }}
        </h3>

        <div class="space-y-4">
            <div
                v-for="comment in comments"
                :key="comment.id"
                class="ml-4 border-l-2 border-zinc-800 pl-4"
            >
                <div v-if="editingCommentId === comment.id" class="space-y-2">
                    <Form
                        :action="updateComment.url({ comment })"
                        method="patch"
                        v-slot="{ errors, processing }"
                        @success="editingCommentId = null"
                    >
                        <textarea
                            name="content"
                            rows="3"
                            required
                            minlength="10"
                            :default-value="comment.content"
                            class="w-full rounded-md border border-zinc-800 bg-zinc-900 px-3 py-2 text-sm text-white focus:ring-2 focus:ring-red-500 focus:outline-none"
                        />
                        <InputError :message="errors.content" />
                        <div class="mt-2 flex gap-2">
                            <Button
                                type="submit"
                                :disabled="processing"
                                size="sm"
                            >
                                <Spinner
                                    v-if="processing"
                                    class="mr-1 size-3"
                                />
                                Save
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="cancelEdit"
                            >
                                Cancel
                            </Button>
                        </div>
                    </Form>
                </div>
                <div v-else>
                    <p class="text-sm text-zinc-300">
                        <span class="font-medium text-zinc-200">
                            {{ comment.user?.name ?? 'Unknown' }}
                        </span>
                        <span class="text-zinc-500"> · </span>
                        <span class="text-zinc-500">
                            {{
                                new Date(
                                    comment.created_at,
                                ).toLocaleDateString()
                            }}
                        </span>
                    </p>
                    <p class="mt-1 text-sm whitespace-pre-wrap text-zinc-400">
                        {{ comment.content }}
                    </p>
                    <div
                        v-if="comment.can_edit || comment.can_delete"
                        class="mt-2 flex gap-2"
                    >
                        <button
                            v-if="comment.can_edit"
                            type="button"
                            class="text-xs text-zinc-500 hover:text-white"
                            @click="startEdit(comment)"
                        >
                            Edit
                        </button>
                        <button
                            v-if="comment.can_delete"
                            type="button"
                            class="text-xs text-zinc-500 hover:text-red-500"
                            @click="deleteComment(comment)"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <Form
            v-if="canComment"
            :action="commentFormAction"
            method="post"
            v-slot="{ errors, processing }"
            class="mt-6 space-y-2"
            @success="emit('comment-added')"
        >
            <Label for="comment-content">Add a comment</Label>
            <textarea
                id="comment-content"
                name="content"
                rows="3"
                required
                minlength="10"
                placeholder="Share your thoughts..."
                class="w-full rounded-md border border-zinc-800 bg-zinc-900 px-3 py-2 text-sm text-white placeholder:text-zinc-500 focus:ring-2 focus:ring-red-500 focus:outline-none"
            />
            <InputError :message="errors.content" />
            <Button type="submit" :disabled="processing" size="sm">
                <Spinner v-if="processing" class="mr-1 size-3" />
                Post comment
            </Button>
        </Form>
    </div>
</template>
