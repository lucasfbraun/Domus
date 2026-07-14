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
                'w-0 whitespace-nowrap pb-3 pr-0 text-right font-medium text-muted-foreground',
                props.class,
            )
        "
    >
        Ações
    </th>
</template>
