<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const appName = computed(() => (page.props.name as string) || 'Domus');
</script>

<template>
    <div class="flex min-w-0 items-center gap-2.5">
        <AppLogoIcon
            class="size-6 shrink-0 text-primary group-data-[collapsible=icon]:size-5"
        />
        <div
            class="grid min-w-0 flex-1 text-left group-data-[collapsible=icon]:hidden"
        >
            <span
                class="truncate text-[15px] font-semibold tracking-tight text-sidebar-foreground"
            >
                {{ appName }}
            </span>
            <span class="truncate text-[11px] text-muted-foreground">
                Gestão de imóveis
            </span>
        </div>
    </div>
</template>
