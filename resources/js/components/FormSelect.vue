<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type FormSelectOption = {
    value: string | number;
    label: string;
};

const props = withDefaults(
    defineProps<{
        name: string;
        id?: string;
        options?: FormSelectOption[];
        defaultValue?: string | number | null;
        placeholder?: string;
        required?: boolean;
        disabled?: boolean;
    }>(),
    {
        options: () => [],
        placeholder: 'Selecione',
    },
);

function normalize(value?: string | number | null): string {
    if (value === undefined || value === null || value === '') {
        return '';
    }

    return String(value);
}

const selected = ref(normalize(props.defaultValue));

watch(
    () => props.defaultValue,
    (value) => {
        selected.value = normalize(value);
    },
);

const model = computed({
    get: () => selected.value || undefined,
    set: (value: string | number | null | undefined) => {
        selected.value = normalize(value);
    },
});
</script>

<template>
    <div class="w-full">
        <input
            type="hidden"
            :name="name"
            :value="selected"
            :required="required"
        />
        <Select
            :model-value="model"
            :disabled="disabled"
            @update:model-value="model = $event as string"
        >
            <SelectTrigger :id="id" class="w-full">
                <SelectValue :placeholder="placeholder" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="option in options"
                    :key="String(option.value)"
                    :value="String(option.value)"
                >
                    {{ option.label }}
                </SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>
