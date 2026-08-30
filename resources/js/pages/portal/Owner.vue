<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppPagination from '@/components/AppPagination.vue';
import Heading from '@/components/Heading.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DataTable,
    DataTableCell,
    DataTableHeadCell,
    DataTableRow,
} from '@/components/ui/data-table';
import { useMoney } from '@/composables/useMoney';
import { portal } from '@/routes/owner';
import type { Paginated } from '@/types';

type PortalProperty = {
    id: number;
    name: string;
    address: string | null;
    type_label: string | null;
    status: string | null;
};

type PortalContract = {
    id: number;
    status: string;
    monthly_rent: number;
    starts_at: string | null;
    ends_at: string | null;
    property: { id: number; name: string } | null;
    tenant: { id: number; name: string } | null;
};

defineProps<{
    properties: Paginated<PortalProperty>;
    contracts: Paginated<PortalContract>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Portal Proprietário', href: portal() }],
    },
});

const { formatCurrency } = useMoney();
</script>

<template>
    <Head title="Portal Proprietário" />

    <div class="flex flex-col gap-8">
        <Heading
            title="Portal Proprietário"
            description="Imóveis e contratos vinculados a você"
        />

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Imóveis</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="properties.data.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhum imóvel encontrado.
                </div>
                <template v-else>
                    <DataTable>
                        <thead>
                            <DataTableRow variant="header">
                                <DataTableHeadCell>Nome</DataTableHeadCell>
                                <DataTableHeadCell>Endereço</DataTableHeadCell>
                                <DataTableHeadCell>Tipo</DataTableHeadCell>
                                <DataTableHeadCell>Status</DataTableHeadCell>
                            </DataTableRow>
                        </thead>
                        <tbody>
                            <DataTableRow
                                v-for="property in properties.data"
                                :key="property.id"
                            >
                                <DataTableCell>
                                    {{ property.name }}
                                </DataTableCell>
                                <DataTableCell>
                                    {{ property.address ?? '—' }}
                                </DataTableCell>
                                <DataTableCell>
                                    {{ property.type_label ?? '—' }}
                                </DataTableCell>
                                <DataTableCell>
                                    <StatusBadge
                                        type="property"
                                        :status="property.status"
                                    />
                                </DataTableCell>
                            </DataTableRow>
                        </tbody>
                    </DataTable>
                    <AppPagination
                        :paginator="properties"
                        page-name="properties"
                        :only="['properties']"
                    />
                </template>
            </CardContent>
        </Card>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Contratos</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="contracts.data.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhum contrato encontrado.
                </div>
                <template v-else>
                    <DataTable>
                        <thead>
                            <DataTableRow variant="header">
                                <DataTableHeadCell>Imóvel</DataTableHeadCell>
                                <DataTableHeadCell>Inquilino</DataTableHeadCell>
                                <DataTableHeadCell>Valor</DataTableHeadCell>
                                <DataTableHeadCell>Status</DataTableHeadCell>
                            </DataTableRow>
                        </thead>
                        <tbody>
                            <DataTableRow
                                v-for="contract in contracts.data"
                                :key="contract.id"
                            >
                                <DataTableCell>
                                    {{ contract.property?.name ?? '—' }}
                                </DataTableCell>
                                <DataTableCell>
                                    {{ contract.tenant?.name ?? '—' }}
                                </DataTableCell>
                                <DataTableCell class="tabular-nums">
                                    {{ formatCurrency(contract.monthly_rent) }}
                                </DataTableCell>
                                <DataTableCell>
                                    <StatusBadge
                                        type="contract"
                                        :status="contract.status"
                                    />
                                </DataTableCell>
                            </DataTableRow>
                        </tbody>
                    </DataTable>
                    <AppPagination
                        :paginator="contracts"
                        page-name="contracts"
                        :only="['contracts']"
                    />
                </template>
            </CardContent>
        </Card>
    </div>
</template>
