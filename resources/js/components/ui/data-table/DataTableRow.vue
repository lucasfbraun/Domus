<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        variant?: 'header' | 'body';
        class?: HTMLAttributes['class'];
    }>(),
    {
        variant: 'body',
    },
);
</script>

<template>
    <tr
        :class="
            cn(
                props.variant === 'header'
                    ? 'border-b border-border/80 text-left text-muted-foreground'
                    : 'group border-b border-border/60 transition-colors last:border-0 hover:bg-muted/40',
                props.class,
            )
        "
    >
        <slot />
    </tr>
</template>
