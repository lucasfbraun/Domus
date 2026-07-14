<script setup lang="ts">
import type { Component, HTMLAttributes } from 'vue';
import type { ButtonVariants } from '@/components/ui/button';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<{
        label: string;
        icon?: Component;
        variant?: ButtonVariants['variant'];
        disabled?: boolean;
        asChild?: boolean;
        type?: 'button' | 'submit' | 'reset';
        class?: HTMLAttributes['class'];
    }>(),
    {
        variant: 'outline',
        disabled: false,
        asChild: false,
        type: 'button',
    },
);
</script>

<template>
    <TooltipProvider :delay-duration="0">
        <Tooltip>
            <TooltipTrigger as-child>
                <Button
                    v-bind="$attrs"
                    :as-child="asChild"
                    :type="asChild ? undefined : type"
                    size="icon-sm"
                    :variant="variant"
                    :disabled="disabled"
                    :aria-label="label"
                    :class="props.class"
                >
                    <slot>
                        <component :is="icon" />
                        <span class="sr-only">{{ label }}</span>
                    </slot>
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                <p>{{ label }}</p>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
