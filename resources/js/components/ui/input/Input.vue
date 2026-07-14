<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed, useAttrs, useTemplateRef } from 'vue';
import InputControl from './InputControl.vue';
import PasswordInput from './PasswordInput.vue';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    type?: HTMLAttributes['type'];
    defaultValue?: string | number;
    modelValue?: string | number;
    class?: HTMLAttributes['class'];
}>();

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void;
}>();

const attrs = useAttrs();

const isPassword = computed(() => {
    const type = props.type ?? (attrs.type as HTMLAttributes['type'] | undefined);

    return type === 'password';
});

const controlRef = useTemplateRef('controlRef');

defineExpose({
    focus: () => controlRef.value?.focus(),
});
</script>

<template>
    <PasswordInput
        v-if="isPassword"
        ref="controlRef"
        :class="props.class"
        :default-value="defaultValue"
        :model-value="modelValue"
        v-bind="attrs"
        @update:model-value="emits('update:modelValue', $event)"
    />
    <InputControl
        v-else
        ref="controlRef"
        :type="type"
        :class="props.class"
        :default-value="defaultValue"
        :model-value="modelValue"
        v-bind="attrs"
        @update:model-value="emits('update:modelValue', $event)"
    />
</template>
