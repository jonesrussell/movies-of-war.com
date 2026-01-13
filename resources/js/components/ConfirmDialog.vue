<script setup lang="ts">
import { ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface Props {
    open?: boolean;
    title: string;
    description: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'default' | 'destructive';
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    variant: 'default',
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();

const isOpen = ref(props.open);

watch(() => props.open, (value) => {
    isOpen.value = value;
});

watch(isOpen, (value) => {
    emit('update:open', value);
});

function handleConfirm() {
    emit('confirm');
    isOpen.value = false;
}

function handleCancel() {
    emit('cancel');
    isOpen.value = false;
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="handleCancel">
                    {{ cancelText }}
                </Button>
                <Button :variant="variant" @click="handleConfirm">
                    {{ confirmText }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
