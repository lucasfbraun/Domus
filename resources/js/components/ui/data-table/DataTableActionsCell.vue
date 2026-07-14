<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <td
        :class="
            cn(
                'w-0 whitespace-nowrap py-3.5 pr-0 text-right align-middle',
                props.class,
            )
        "
    >
        <div class="inline-flex items-center gap-1 *:shrink-0">
            <slot />
        </div>
    </td>
</template>
