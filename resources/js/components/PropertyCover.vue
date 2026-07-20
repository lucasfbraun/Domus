<script setup lang="ts">
import { Building2 } from '@lucide/vue';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    src?: string | null;
    alt: string;
    class?: HTMLAttributes['class'];
}>();

const hasImage = computed(() => Boolean(props.src));
</script>

<template>
    <div
        :class="
            cn(
                'relative shrink-0 overflow-hidden bg-muted',
                props.class,
            )
        "
    >
        <img
            v-if="hasImage"
            :src="src!"
            :alt="alt"
            class="absolute inset-0 size-full object-cover"
        />
        <div
            v-else
            class="absolute inset-0 flex items-center justify-center bg-muted"
            aria-hidden="true"
        >
            <Building2 class="size-8 text-muted-foreground/35" />
        </div>
    </div>
</template>
