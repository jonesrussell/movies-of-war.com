<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import type { DateValue } from '@internationalized/date';
import { fromDate, toCalendarDate, getLocalTimeZone, today } from '@internationalized/date';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { CalendarIcon } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

interface Props {
    modelValue?: string;
    class?: string;
    id?: string;
}

const props = withDefaults(defineProps<Props>(), {
    class: '',
});

const emits = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const datePickerOpen = ref(false);

// Parse the datetime-local value into date and time
const parseDateTime = (value: string | undefined): { date: DateValue | null; time: string } => {
    if (!value) {
        return { date: null, time: '00:00' };
    }

    const dateTime = new Date(value);
    if (isNaN(dateTime.getTime())) {
        return { date: null, time: '00:00' };
    }

    const date = fromDate(dateTime, getLocalTimeZone());
    const hours = dateTime.getHours().toString().padStart(2, '0');
    const minutes = dateTime.getMinutes().toString().padStart(2, '0');
    const time = `${hours}:${minutes}`;

    return { date, time };
};

const parsedDateTime = computed(() => {
    return parseDateTime(props.modelValue);
});

const dateModel = ref<DateValue | null>(parsedDateTime.value.date);
const timeModel = ref<string>(parsedDateTime.value.time);

// Watch for external changes
watch(
    () => props.modelValue,
    (newValue) => {
        const parsed = parseDateTime(newValue);
        dateModel.value = parsed.date;
        timeModel.value = parsed.time;
    },
    { immediate: true },
);

// Combine date and time into datetime-local format
const combineDateTime = (selectedDate: DateValue | null, selectedTime: string): string => {
    if (!selectedDate) {
        return '';
    }

    const dateObj = selectedDate.toDate(getLocalTimeZone());
    const [hours = '00', minutes = '00'] = selectedTime.split(':');

    dateObj.setHours(parseInt(hours, 10));
    dateObj.setMinutes(parseInt(minutes, 10));
    dateObj.setSeconds(0);
    dateObj.setMilliseconds(0);

    // Format as YYYY-MM-DDTHH:mm for datetime-local input
    const year = dateObj.getFullYear();
    const month = (dateObj.getMonth() + 1).toString().padStart(2, '0');
    const day = dateObj.getDate().toString().padStart(2, '0');
    const hourStr = hours.padStart(2, '0');
    const minuteStr = minutes.padStart(2, '0');

    return `${year}-${month}-${day}T${hourStr}:${minuteStr}`;
};

// Watch for changes and emit
watch([dateModel, timeModel], ([newDate, newTime]) => {
    if (newDate) {
        const combined = combineDateTime(newDate, newTime);
        if (combined) {
            emits('update:modelValue', combined);
        }
    }
});

const displayValue = computed(() => {
    if (!dateModel.value) {
        return 'Pick a date and time';
    }

    const dateObj = dateModel.value.toDate(getLocalTimeZone());
    const dateStr = dateObj.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });

    return `${dateStr} at ${timeModel.value}`;
});
</script>

<template>
    <div :class="cn('flex gap-4', props.class)">
        <!-- Date Picker -->
        <div class="flex-1">
            <Label v-if="id" :for="`${id}-date`" class="sr-only">Date</Label>
            <Popover v-model:open="datePickerOpen">
                <PopoverTrigger as-child>
                    <Button
                        :id="id ? `${id}-date` : undefined"
                        variant="outline"
                        :class="
                            cn(
                                'w-full justify-start text-left font-normal border-zinc-700 bg-zinc-900 text-white hover:bg-zinc-800',
                                !dateModel && 'text-zinc-500',
                            )
                        "
                    >
                        <CalendarIcon class="mr-2 size-4 text-zinc-400" />
                        {{ displayValue }}
                    </Button>
                </PopoverTrigger>
                <PopoverContent class="w-auto p-0" align="start">
                    <Calendar
                        :model-value="dateModel ?? undefined"
                        layout="month-and-year"
                        :default-placeholder="today(getLocalTimeZone())"
                        @update:model-value="
                            (value) => {
                                if (value && !Array.isArray(value)) {
                                    dateModel = value;
                                    datePickerOpen = false;
                                }
                            }
                        "
                    />
                </PopoverContent>
            </Popover>
        </div>

        <!-- Time Picker -->
        <div class="w-32">
            <Label v-if="id" :for="`${id}-time`" class="sr-only">Time</Label>
            <Input
                :id="id ? `${id}-time` : undefined"
                v-model="timeModel"
                type="time"
                step="60"
                :class="cn('border-zinc-700 bg-zinc-900 text-white focus:border-red-500 focus:ring-red-500')"
            />
        </div>
    </div>
</template>
