<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types';

withDefaults(
    defineProps<{
        items: NavItem[];
        label?: string;
    }>(),
    {
        label: undefined,
    },
);

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="gap-1 p-0 px-1">
        <SidebarGroupLabel
            v-if="label"
            class="mb-1 h-auto px-2.5 py-1 text-[11px] font-medium tracking-wide text-muted-foreground/90"
        >
            {{ label }}
        </SidebarGroupLabel>
        <SidebarMenu class="gap-0.5">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                    :class="
                        isCurrentUrl(item.href)
                            ? 'bg-sidebar-accent text-primary hover:bg-sidebar-accent hover:text-primary'
                            : 'text-sidebar-foreground'
                    "
                >
                    <Link :href="item.href">
                        <component
                            :is="item.icon"
                            :class="
                                cn(
                                    'size-[1.05rem] shrink-0',
                                    isCurrentUrl(item.href)
                                        ? 'text-primary'
                                        : 'text-sidebar-foreground',
                                )
                            "
                        />
                        <span
                            :class="
                                isCurrentUrl(item.href)
                                    ? 'font-semibold text-primary'
                                    : 'font-semibold text-sidebar-foreground'
                            "
                        >
                            {{ item.title }}
                        </span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
