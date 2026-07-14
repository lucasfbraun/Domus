<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        fill="none"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            d="M20 5 35 18v15a2 2 0 0 1-2 2H24V24a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v11H7a2 2 0 0 1-2-2V18L20 5Z"
        />
        <path
            fill="currentColor"
            fill-opacity="0.35"
            d="M20 5 5 18h6l9-7.5L29 18h6L20 5Z"
        />
    </svg>
</template>
