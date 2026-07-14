<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import {
    getStatusClassName,
    getStatusLabel,
    type StatusType,
} from '@/lib/status-badges';

const props = defineProps<{
    type: StatusType;
    status?: string | null;
    class?: HTMLAttributes['class'];
}>();

const label = computed(() => getStatusLabel(props.type, props.status));
const className = computed(() =>
    cn(getStatusClassName(props.type, props.status), props.class),
);
</script>

<template>
    <Badge variant="outline" :class="className">
        {{ label }}
    </Badge>
</template>
