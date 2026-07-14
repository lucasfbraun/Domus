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
                'w-[1%] whitespace-nowrap py-3.5 pl-6 text-right align-middle',
                props.class,
            )
        "
    >
        <div class="flex items-center justify-end gap-1">
            <slot />
        </div>
    </td>
</template>
