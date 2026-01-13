<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/ui/card';

interface Props {
    title: string;
    value: string | number;
}

defineProps<Props>();
</script>

<template>
    <Card>
        <CardHeader>
            <div class="text-sm font-medium text-muted-foreground">
                {{ title }}
            </div>
        </CardHeader>
        <CardContent>
            <div class="text-3xl font-bold">{{ value }}</div>
        </CardContent>
    </Card>
</template>
