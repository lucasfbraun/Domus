<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    CreditCard,
    DatabaseBackup,
    FileText,
    Handshake,
    LayoutGrid,
    ListChecks,
    MessageSquareWarning,
    PieChart,
    Plug,
    Settings,
    Shield,
    ShieldCheck,
    TrendingUp,
    UserCircle,
    UserPlus,
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
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as admins } from '@/routes/admin/admins';
import { index as backups } from '@/routes/admin/backups';
import { edit as billingSettings } from '@/routes/admin/billing-settings';
import { index as charges } from '@/routes/admin/charges';
import { index as contracts } from '@/routes/admin/contracts';
import { index as deposits } from '@/routes/admin/deposits';
import { index as featureChecks } from '@/routes/admin/feature-checks';
import { index as incomeReport } from '@/routes/admin/income-report';
import { index as integrations } from '@/routes/admin/integrations';
import { index as occurrences } from '@/routes/admin/occurrences';
import { index as owners } from '@/routes/admin/owners';
import { index as properties } from '@/routes/admin/properties';
import { index as rateios } from '@/routes/admin/rateios';
import { index as receivers } from '@/routes/admin/receivers';
import { index as templates } from '@/routes/admin/templates';
import { index as tenants } from '@/routes/admin/tenants';
import { index as tenantPreRegistrations } from '@/routes/admin/tenant-pre-registrations';
import { portal as ownerPortal } from '@/routes/owner';
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
const isOwner = computed(() => roles.value.includes('owner'));

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
        title: 'Pré-cadastros',
        href: tenantPreRegistrations(),
        icon: UserPlus,
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
        title: 'Cauções',
        href: deposits(),
        icon: ShieldCheck,
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
        title: 'Informe de Rendimentos',
        href: incomeReport(),
        icon: TrendingUp,
    },
    {
        title: 'Integrações',
        href: integrations(),
        icon: Plug,
    },
    {
        title: 'Backups',
        href: backups(),
        icon: DatabaseBackup,
    },
    {
        title: 'Configurações',
        href: billingSettings(),
        icon: Settings,
    },
    {
        title: 'Funcionalidades',
        href: featureChecks(),
        icon: ListChecks,
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

    if (isOwner.value) {
        items.push({
            title: 'Portal Proprietário',
            href: ownerPortal(),
            icon: UserCircle,
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <SidebarHeader
            class="h-16 justify-center border-b border-sidebar-border/80 px-4 py-0 group-data-[collapsible=icon]:px-2"
        >
            <Link
                :href="dashboard()"
                class="flex items-center rounded-md outline-none ring-sidebar-ring transition-opacity hover:opacity-80 focus-visible:ring-2"
            >
                <AppLogo />
            </Link>
        </SidebarHeader>

        <SidebarContent class="gap-5 px-2 py-4">
            <template v-if="isAdmin">
                <NavMain :items="adminMainNavItems" />
                <NavMain :items="cadastrosNavItems" label="Cadastros" />
                <NavMain :items="adminOperationsNavItems" label="Operações" />
            </template>

            <NavMain
                v-if="portalNavItems.length"
                :items="portalNavItems"
                label="Portal"
            />
        </SidebarContent>

        <SidebarFooter
            class="border-t border-sidebar-border/80 p-3 group-data-[collapsible=icon]:p-2"
        >
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
