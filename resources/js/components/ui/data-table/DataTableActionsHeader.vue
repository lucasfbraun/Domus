<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <th
        scope="col"
        :class="
            cn(
                'w-[1%] whitespace-nowrap pb-3 pl-6 text-right font-medium text-muted-foreground',
                props.class,
            )
        "
    >
        Ações
    </th>
</template>
