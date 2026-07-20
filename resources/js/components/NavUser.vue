<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ChevronUp } from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import UserInfo from '@/components/UserInfo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { isMobile, state } = useSidebar();
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="h-auto rounded-md px-2 py-2 data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground data-[active=true]:shadow-none"
                        data-test="sidebar-menu-button"
                    >
                        <UserInfo :user="user" show-email />
                        <ChevronUp
                            class="ml-auto size-3.5 shrink-0 text-muted-foreground opacity-70 group-data-[collapsible=icon]:hidden"
                        />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-md border-border/80"
                    :side="
                        isMobile
                            ? 'bottom'
                            : state === 'collapsed'
                              ? 'left'
                              : 'top'
                    "
                    align="start"
                    :side-offset="6"
                >
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
