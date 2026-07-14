<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import TableActionButton from '@/components/TableActionButton.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DataTable,
    DataTableActionsCell,
    DataTableActionsHeader,
    DataTableCell,
    DataTableHeadCell,
    DataTableRow,
} from '@/components/ui/data-table';
import { formatDate } from '@/lib/dates';
import { useMoney } from '@/composables/useMoney';
import { show } from '@/routes/contracts';
import { portal } from '@/routes/receiver';

defineProps<{
    contracts: any[];
    charges: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Portal Recebedor', href: portal() },
        ],
    },
});

const { formatCurrency } = useMoney();
</script>

<template>
    <Head title="Portal Recebedor" />

    <div class="flex flex-col gap-8">
        <Heading
            title="Portal Recebedor"
            description="Contratos e cobranças vinculados a você"
        />

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Contratos</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="contracts.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhum contrato encontrado.
                </div>
                <DataTable v-else>
                    <thead>
                        <DataTableRow variant="header">
                            <DataTableHeadCell>Imóvel</DataTableHeadCell>
                            <DataTableHeadCell>Inquilino</DataTableHeadCell>
                            <DataTableHeadCell>Valor</DataTableHeadCell>
                            <DataTableHeadCell>Status</DataTableHeadCell>
                            <DataTableActionsHeader />
                        </DataTableRow>
                    </thead>
                    <tbody>
                        <DataTableRow
                            v-for="contract in contracts"
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
                            <DataTableActionsCell>
                                <TableActionButton
                                    label="Ver contrato"
                                    as-child
                                >
                                    <Link :href="show(contract)">
                                        <Eye />
                                        <span class="sr-only">Ver contrato</span>
                                    </Link>
                                </TableActionButton>
                            </DataTableActionsCell>
                        </DataTableRow>
                    </tbody>
                </DataTable>
            </CardContent>
        </Card>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Cobranças</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="charges.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhuma cobrança encontrada.
                </div>
                <DataTable v-else>
                    <thead>
                        <DataTableRow variant="header">
                            <DataTableHeadCell>Descrição</DataTableHeadCell>
                            <DataTableHeadCell>Inquilino</DataTableHeadCell>
                            <DataTableHeadCell>Valor</DataTableHeadCell>
                            <DataTableHeadCell>Vencimento</DataTableHeadCell>
                            <DataTableHeadCell>Status</DataTableHeadCell>
                        </DataTableRow>
                    </thead>
                    <tbody>
                        <DataTableRow
                            v-for="charge in charges"
                            :key="charge.id"
                        >
                            <DataTableCell>
                                {{ charge.description ?? '—' }}
                            </DataTableCell>
                            <DataTableCell>
                                {{ charge.tenant?.name ?? '—' }}
                            </DataTableCell>
                            <DataTableCell class="tabular-nums">
                                {{ formatCurrency(charge.amount) }}
                            </DataTableCell>
                            <DataTableCell>
                                {{ formatDate(charge.due_date) }}
                            </DataTableCell>
                            <DataTableCell>
                                <StatusBadge
                                    type="charge"
                                    :status="charge.status"
                                />
                            </DataTableCell>
                        </DataTableRow>
                    </tbody>
                </DataTable>
            </CardContent>
        </Card>
    </div>
</template>
