<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <header :class="variant === 'small' ? 'space-y-1' : 'space-y-1.5'">
        <h2
            :class="
                variant === 'small'
                    ? 'text-base font-medium tracking-tight'
                    : 'text-2xl font-semibold tracking-tight text-foreground'
            "
        >
            {{ title }}
        </h2>
        <p
            v-if="description"
            class="max-w-2xl text-sm leading-relaxed text-muted-foreground"
        >
            {{ description }}
        </p>
    </header>
</template>
