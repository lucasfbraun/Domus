<script setup lang="ts">
import { Eye, EyeOff } from '@lucide/vue';
import type { HTMLAttributes } from 'vue';
import { ref, useTemplateRef } from 'vue';
import InputControl from './InputControl.vue';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    defaultValue?: string | number;
    modelValue?: string | number;
    class?: HTMLAttributes['class'];
}>();

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void;
}>();

const showPassword = ref(false);
const inputRef = useTemplateRef('inputRef');

defineExpose({
    focus: () => inputRef.value?.focus(),
});
</script>

<template>
    <div class="relative">
        <InputControl
            ref="inputRef"
            :class="cn('pr-10', props.class)"
            :default-value="defaultValue"
            :model-value="modelValue"
            v-bind="$attrs"
            :type="showPassword ? 'text' : 'password'"
            @update:model-value="emits('update:modelValue', $event)"
        />
        <button
            type="button"
            :class="
                cn(
                    'absolute inset-y-0 right-0 flex items-center rounded-e-xl px-3 text-muted-foreground hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring focus-visible:outline-none',
                )
            "
            :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
            :tabindex="-1"
            @click="showPassword = !showPassword"
        >
            <EyeOff v-if="showPassword" class="size-4" />
            <Eye v-else class="size-4" />
        </button>
    </div>
</template>
