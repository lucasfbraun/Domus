<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { onMounted, ref, useTemplateRef } from 'vue';
import { Input } from '@/components/ui/input';
import {
    applyBrazilianMask,
    type BrazilianMask,
} from '@/lib/brazilian-masks';

const props = defineProps<{
    mask: BrazilianMask;
    defaultValue?: string | number | null;
    class?: HTMLAttributes['class'];
}>();

const displayValue = ref('');
const inputRef = useTemplateRef<{ focus: () => void }>('inputRef');

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
        inputmode="numeric"
        autocomplete="off"
        @update:model-value="onUpdate"
    />
</template>
