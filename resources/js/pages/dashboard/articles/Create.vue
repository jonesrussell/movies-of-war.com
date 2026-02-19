<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';

import ArticleForm from '@/components/admin/ArticleForm.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';

interface FieldDefinition {
    name: string;
    type: string;
    label: string;
    required?: boolean;
    [key: string]: unknown;
}

interface Props {
    fields: FieldDefinition[];
    relationOptions: Record<string, { id: number; name: string }[]>;
}

const props = defineProps<Props>();

const routePrefix = '/dashboard/articles';
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Articles', href: routePrefix },
    { title: 'Create', href: `${routePrefix}/create` },
];

// Initialize form data from field definitions
const initFormData = (): Record<string, unknown> => {
    const data: Record<string, unknown> = {};
    for (const field of props.fields) {
        switch (field.type) {
            case 'checkbox': {
                data[field.name] = false;
                break;
            }
            case 'belongs-to-many': {
                data[field.name] = [];
                break;
            }
            case 'belongs-to': {
                // Default to first available option
                const options =
                    props.relationOptions.news_sources ??
                    props.relationOptions[field.name] ??
                    [];
                data[field.name] = options[0]?.id ?? null;

                break;
            }
            default:
                data[field.name] = '';
        }
    }
    return data;
};

const form = ref<Record<string, unknown>>(initFormData());
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const handleSubmit = (publish: boolean = false) => {
    processing.value = true;
    errors.value = {};

    const data = {
        ...form.value,
        published_at: publish ? new Date().toISOString() : null,
    };

    router.post(routePrefix, data, {
        preserveScroll: true,
        onError: (err) => {
            errors.value = err;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <Head title="Create Article - Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6"
        >
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Button
                        variant="ghost"
                        size="sm"
                        as="a"
                        :href="routePrefix"
                        class="mb-2"
                    >
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Articles
                    </Button>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Create Article
                    </h1>
                    <p class="mt-1 text-muted-foreground">
                        Add a new article to your collection
                    </p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="handleSubmit(false)">
                <ArticleForm
                    :fields="fields"
                    v-model="form"
                    :errors="errors"
                    :relation-options="relationOptions"
                />

                <!-- Actions -->
                <div class="mt-6 flex gap-3 border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        as="a"
                        :href="routePrefix"
                        :disabled="processing"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        variant="outline"
                        :disabled="processing"
                    >
                        {{ processing ? 'Saving...' : 'Save as Draft' }}
                    </Button>
                    <Button
                        type="button"
                        @click="handleSubmit(true)"
                        :disabled="processing"
                    >
                        {{ processing ? 'Publishing...' : 'Publish' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
