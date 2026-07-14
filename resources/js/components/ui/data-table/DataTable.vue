<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div class="overflow-x-auto">
        <table
            :class="
                cn('w-full table-auto text-sm', props.class)
            "
        >
            <slot />
        </table>
    </div>
</template>
