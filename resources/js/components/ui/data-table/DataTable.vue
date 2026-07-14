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
            style="table-layout: fixed"
            :class="
                cn('w-full text-sm', props.class)
            "
        >
            <slot />
        </table>
    </div>
</template>
