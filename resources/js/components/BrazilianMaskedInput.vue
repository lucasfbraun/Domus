<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed, onMounted, ref, useTemplateRef } from 'vue';
import { Input } from '@/components/ui/input';
import {
    applyBrazilianMask,
    brazilianMaskMaxLength,
    type BrazilianMask,
} from '@/lib/brazilian-masks';

const props = defineProps<{
    mask: BrazilianMask;
    defaultValue?: string | number | null;
    class?: HTMLAttributes['class'];
}>();

const displayValue = ref('');
const inputRef = useTemplateRef<{ focus: () => void }>('inputRef');
const maxLength = computed(() => brazilianMaskMaxLength[props.mask]);

onMounted(() => {
    if (props.defaultValue != null && props.defaultValue !== '') {
        displayValue.value = applyBrazilianMask(
            String(props.defaultValue),
            props.mask,
        );
    }
});

function onUpdate(value: string | number): void {
    displayValue.value = applyBrazilianMask(String(value), props.mask);
}

defineExpose({
    focus: () => inputRef.value?.focus(),
});
</script>

<template>
    <Input
        ref="inputRef"
        :model-value="displayValue"
        :class="props.class"
        :maxlength="maxLength"
        inputmode="numeric"
        autocomplete="off"
        @update:model-value="onUpdate"
    />
</template>
