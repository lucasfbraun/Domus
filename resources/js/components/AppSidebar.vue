<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    CreditCard,
    FileText,
    Handshake,
    LayoutGrid,
    MessageSquareWarning,
    PieChart,
    Plug,
    Shield,
    UserCircle,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const page = usePage();

const roles = computed(() => {
    const userRoles = page.props.auth.user?.roles;

    if (Array.isArray(userRoles)) {
        return userRoles as string[];
    }

    return [];
});

const isAdmin = computed(() => roles.value.includes('admin'));
const isTenant = computed(() => roles.value.includes('tenant'));
const isReceiver = computed(() => roles.value.includes('receiver'));

const adminMainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Painel',
        href: '/dashboard',
        icon: LayoutGrid,
    },
]);

const cadastrosNavItems = computed<NavItem[]>(() => [
    {
        title: 'Proprietários',
        href: '/owners',
        icon: UserCircle,
    },
    {
        title: 'Imóveis',
        href: '/properties',
        icon: Building2,
    },
    {
        title: 'Inquilinos',
        href: '/tenants',
        icon: Users,
    },
    {
        title: 'Recebedores',
        href: '/receivers',
        icon: Wallet,
    },
    {
        title: 'Administradores',
        href: '/admins',
        icon: Shield,
    },
]);

const adminOperationsNavItems = computed<NavItem[]>(() => [
    {
        title: 'Contratos',
        href: '/contracts',
        icon: Handshake,
    },
    {
        title: 'Modelos',
        href: '/templates',
        icon: FileText,
    },
    {
        title: 'Cobranças',
        href: '/charges',
        icon: CreditCard,
    },
    {
        title: 'Ocorrências',
        href: '/occurrences',
        icon: MessageSquareWarning,
    },
    {
        title: 'Rateios',
        href: '/rateios',
        icon: PieChart,
    },
    {
        title: 'Integrações',
        href: '/integracoes',
        icon: Plug,
    },
]);

const portalNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (isTenant.value) {
        items.push({
            title: 'Portal Inquilino',
            href: '/inquilino',
            icon: Users,
        });
    }

    if (isReceiver.value) {
        items.push({
            title: 'Portal Recebedor',
            href: '/recebedor',
            icon: Wallet,
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <template v-if="isAdmin">
                <NavMain :items="adminMainNavItems" label="Painel" />
                <NavMain :items="cadastrosNavItems" label="Cadastros" />
                <NavMain :items="adminOperationsNavItems" label="Operações" />
            </template>

            <NavMain
                v-if="portalNavItems.length"
                :items="portalNavItems"
                label="Portal"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
