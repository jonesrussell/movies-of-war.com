<script setup lang="ts">
import type { PaginatedXAutoReplyRules } from '../../../types/x-suite';

import { Head, router } from '@inertiajs/vue3';

import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface Props {
    rules: PaginatedXAutoReplyRules;
}

defineProps<Props>();

function toggleActive(rule: any) {
    router.post(
        `/x-auto-replies/${rule.id}/toggle`,
        {},
        { preserveScroll: true },
    );
}

function deleteRule(rule: any) {
    if (confirm(`Delete rule: ${rule.name}?`)) {
        router.delete(`/x-auto-replies/${rule.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="Auto-Replies" />

        <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">
                        Auto-Reply Rules
                    </h1>
                    <p class="mt-2 text-zinc-400">
                        Manage automatic replies to mentions
                    </p>
                </div>
                <button
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    Create Rule
                </button>
            </div>

            <div class="rounded-lg bg-zinc-900 p-6">
                <div
                    v-if="rules.data.length === 0"
                    class="text-center text-zinc-400"
                >
                    No auto-reply rules configured yet.
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="rule in rules.data"
                        :key="rule.id"
                        class="rounded-lg bg-zinc-800 p-4"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="font-medium text-white">
                                    {{ rule.name }}
                                </div>
                                <div class="mt-1 text-sm text-zinc-400">
                                    Trigger: {{ rule.trigger_type }} | Keywords:
                                    {{ rule.trigger_keywords?.join(', ') }}
                                </div>
                                <div class="mt-2 text-sm text-white">
                                    {{ rule.reply_template }}
                                </div>
                            </div>
                            <div class="ml-4 flex gap-2">
                                <button
                                    @click="toggleActive(rule)"
                                    :class="
                                        rule.is_active
                                            ? 'text-green-500'
                                            : 'text-zinc-500'
                                    "
                                >
                                    {{ rule.is_active ? 'Active' : 'Inactive' }}
                                </button>
                                <button
                                    @click="deleteRule(rule)"
                                    class="text-red-500 hover:text-red-400"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>
