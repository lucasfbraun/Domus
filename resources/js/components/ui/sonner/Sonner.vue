<script lang="ts" setup>
import type { ToasterProps } from 'vue-sonner';
import {
    CircleCheckIcon,
    InfoIcon,
    Loader2Icon,
    OctagonXIcon,
    TriangleAlertIcon,
    XIcon,
} from '@lucide/vue';
import { Toaster as Sonner } from 'vue-sonner';
import { cn } from '@/lib/utils';

import 'vue-sonner/style.css';

const props = withDefaults(defineProps<ToasterProps>(), {
    richColors: true,
    closeButton: true,
    closeButtonAriaLabel: 'Fechar',
    theme: 'light',
});
</script>

<template>
    <Sonner
        v-bind="props"
        theme="light"
        :class="cn('toaster group', props.class)"
        :rich-colors="true"
        :close-button="true"
        close-button-aria-label="Fechar"
    >
        <template #success-icon>
            <CircleCheckIcon class="size-4" />
        </template>
        <template #info-icon>
            <InfoIcon class="size-4" />
        </template>
        <template #warning-icon>
            <TriangleAlertIcon class="size-4" />
        </template>
        <template #error-icon>
            <OctagonXIcon class="size-4" />
        </template>
        <template #loading-icon>
            <div>
                <Loader2Icon class="size-4 animate-spin" />
            </div>
        </template>
        <template #close-icon>
            <XIcon class="size-4" />
        </template>
    </Sonner>
</template>
