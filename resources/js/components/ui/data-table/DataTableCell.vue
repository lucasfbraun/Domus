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
                'py-3.5 pr-6 align-middle last:pr-0',
                props.class,
            )
        "
    >
        <slot />
    </td>
</template>
