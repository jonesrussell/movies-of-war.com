<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface ConnectionStatus {
    connected: boolean;
    username: string | null;
    name: string | null;
    connected_at: string | null;
    expires_at: string | null;
}

interface Props {
    connectionStatus: ConnectionStatus;
    oauth2CallbackUrl: string;
}

defineProps<Props>();

const page = usePage();
const flash = computed(
    () => page.props.flash as { success?: string; error?: string },
);

function disconnect() {
    if (
        confirm(
            'Are you sure you want to disconnect your X account? You will need to reconnect to post tweets.',
        )
    ) {
        router.post(
            '/x-settings/disconnect',
            {},
            {
                preserveScroll: true,
            },
        );
    }
}

function formatDate(dateString: string | null): string {
    if (!dateString) {
        return 'Never expires';
    }
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="X Settings" />

        <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white">X Settings</h1>
                <p class="mt-2 text-zinc-400">
                    Manage your X.com account connection
                </p>
            </div>

            <!-- Flash Messages -->
            <div
                v-if="flash?.success"
                class="mb-6 rounded-lg bg-green-900/50 p-4 text-green-300 ring-1 ring-green-800"
            >
                {{ flash.success }}
            </div>

            <div
                v-if="flash?.error"
                class="mb-6 rounded-lg bg-red-900/50 p-4 text-red-300 ring-1 ring-red-800"
            >
                {{ flash.error }}
            </div>

            <!-- Connection Status Card -->
            <div class="rounded-lg bg-zinc-900 p-6 ring-1 ring-zinc-800/70">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-white">
                            Account Connection
                        </h2>
                        <p class="mt-1 text-sm text-zinc-400">
                            Connect your X account to enable posting
                        </p>
                    </div>
                    <div
                        v-if="connectionStatus.connected"
                        class="flex items-center gap-2 text-green-400"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span class="font-medium">Connected</span>
                    </div>
                    <div v-else class="flex items-center gap-2 text-zinc-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        <span class="font-medium">Not Connected</span>
                    </div>
                </div>

                <!-- Connected State -->
                <div v-if="connectionStatus.connected" class="space-y-4">
                    <div
                        v-if="
                            connectionStatus.username || connectionStatus.name
                        "
                        class="rounded-lg bg-zinc-800/50 p-4"
                    >
                        <div class="space-y-2 text-sm">
                            <div
                                v-if="connectionStatus.username"
                                class="flex items-center gap-2"
                            >
                                <span class="text-zinc-400">Username:</span>
                                <span class="font-medium text-white">
                                    @{{ connectionStatus.username }}
                                </span>
                            </div>
                            <div
                                v-if="connectionStatus.name"
                                class="flex items-center gap-2"
                            >
                                <span class="text-zinc-400">Name:</span>
                                <span class="font-medium text-white">
                                    {{ connectionStatus.name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2 text-sm text-zinc-400">
                        <div class="flex items-center justify-between">
                            <span>Connected:</span>
                            <span class="text-white">
                                {{ formatDate(connectionStatus.connected_at) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Token Expires:</span>
                            <span class="text-white">
                                {{ formatDate(connectionStatus.expires_at) }}
                            </span>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button
                            @click="disconnect"
                            class="rounded-lg bg-red-600/90 px-4 py-2 text-sm font-medium text-white hover:bg-red-600"
                        >
                            Disconnect Account
                        </button>
                    </div>
                </div>

                <!-- Not Connected State -->
                <div v-else class="space-y-4">
                    <p class="text-zinc-400">
                        You need to connect your X.com account to enable posting
                        tweets. First add the callback URL below in the X
                        Developer Portal, then click Connect.
                    </p>

                    <div class="rounded-lg bg-zinc-800/50 p-4">
                        <p class="mb-2 text-sm font-medium text-zinc-300">
                            Callback URI (add this in X Developer Portal)
                        </p>
                        <p class="font-mono text-sm break-all text-white">
                            {{ oauth2CallbackUrl }}
                        </p>
                        <p class="mt-2 text-xs text-zinc-500">
                            In the portal: your app &rarr; User authentication
                            settings &rarr; Callback URI / Redirect URL. Must match
                            exactly (including https and path). If you use a
                            development app, sign in to X with the app owner
                            account before connecting.
                        </p>
                    </div>

                    <div>
                        <a
                            href="/x-oauth2/redirect"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                            Connect X Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>
