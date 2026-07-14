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
import { dashboard } from '@/routes';
import { index as admins } from '@/routes/admin/admins';
import { index as charges } from '@/routes/admin/charges';
import { index as contracts } from '@/routes/admin/contracts';
import { index as integrations } from '@/routes/admin/integrations';
import { index as occurrences } from '@/routes/admin/occurrences';
import { index as owners } from '@/routes/admin/owners';
import { index as properties } from '@/routes/admin/properties';
import { index as rateios } from '@/routes/admin/rateios';
import { index as receivers } from '@/routes/admin/receivers';
import { index as templates } from '@/routes/admin/templates';
import { index as tenants } from '@/routes/admin/tenants';
import { portal as receiverPortal } from '@/routes/receiver';
import { portal as tenantPortal } from '@/routes/tenant';
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
        href: dashboard(),
        icon: LayoutGrid,
    },
]);

const cadastrosNavItems = computed<NavItem[]>(() => [
    {
        title: 'Proprietários',
        href: owners(),
        icon: UserCircle,
    },
    {
        title: 'Imóveis',
        href: properties(),
        icon: Building2,
    },
    {
        title: 'Inquilinos',
        href: tenants(),
        icon: Users,
    },
    {
        title: 'Recebedores',
        href: receivers(),
        icon: Wallet,
    },
    {
        title: 'Administradores',
        href: admins(),
        icon: Shield,
    },
]);

const adminOperationsNavItems = computed<NavItem[]>(() => [
    {
        title: 'Contratos',
        href: contracts(),
        icon: Handshake,
    },
    {
        title: 'Modelos',
        href: templates(),
        icon: FileText,
    },
    {
        title: 'Cobranças',
        href: charges(),
        icon: CreditCard,
    },
    {
        title: 'Ocorrências',
        href: occurrences(),
        icon: MessageSquareWarning,
    },
    {
        title: 'Rateios',
        href: rateios(),
        icon: PieChart,
    },
    {
        title: 'Integrações',
        href: integrations(),
        icon: Plug,
    },
]);

const portalNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (isTenant.value) {
        items.push({
            title: 'Portal Inquilino',
            href: tenantPortal(),
            icon: Users,
        });
    }

    if (isReceiver.value) {
        items.push({
            title: 'Portal Recebedor',
            href: receiverPortal(),
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
                        <Link :href="dashboard()">
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
