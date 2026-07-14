<script setup lang="ts">
import { getLocalTimeZone, parseDate, today } from '@internationalized/date';
import { CalendarIcon } from '@lucide/vue';
import type { DateValue } from 'reka-ui';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { formatDateMedium } from '@/lib/dates';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        name: string;
        id?: string;
        defaultValue?: string | null;
        required?: boolean;
        placeholder?: string;
        disabled?: boolean;
    }>(),
    {
        placeholder: 'Selecione a data',
    },
);

function parseValue(value?: string | null): DateValue | undefined {
    if (!value) {
        return undefined;
    }

    try {
        return parseDate(String(value).slice(0, 10)) as DateValue;
    } catch {
        return undefined;
    }
}

const date = ref<DateValue | undefined>(parseValue(props.defaultValue));
const open = ref(false);
const defaultPlaceholder = today(getLocalTimeZone());

const isoValue = computed(() => date.value?.toString() ?? '');

watch(
    () => props.defaultValue,
    (value) => {
        date.value = parseValue(value);
    },
);

function onDateSelect(value: DateValue | DateValue[] | null | undefined): void {
    date.value = Array.isArray(value) ? value[0] : (value ?? undefined);
    open.value = false;
}
</script>

<template>
    <div class="w-full">
        <input
            type="hidden"
            :name="name"
            :value="isoValue"
            :required="required"
        />
        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button
                    :id="id"
                    type="button"
                    variant="outline"
                    :disabled="disabled"
                    :class="
                        cn(
                            'w-full justify-start text-left font-normal',
                            !date && 'text-muted-foreground',
                        )
                    "
                >
                    <CalendarIcon class="size-4 opacity-70" />
                    {{
                        date
                            ? formatDateMedium(isoValue)
                            : placeholder
                    }}
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-0" align="start">
                <Calendar
                    :model-value="date as any"
                    locale="pt-BR"
                    layout="month-and-year"
                    :default-placeholder="defaultPlaceholder"
                    @update:model-value="onDateSelect"
                />
            </PopoverContent>
        </Popover>
    </div>
</template>
