<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download, Eye } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import AppPagination from '@/components/AppPagination.vue';
import TableActionButton from '@/components/TableActionButton.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
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
import { receipt } from '@/routes/charges';
import { show } from '@/routes/contracts';
import { portal } from '@/routes/receiver';
import type { Paginated } from '@/types';

type PortalCharge = {
    id: number;
    description?: string | null;
    amount: number;
    status: string;
    due_date: string;
    is_paid: boolean;
    tenant?: string | null;
    property?: string | null;
};

defineProps<{
    contracts: Paginated<any>;
    charges: Paginated<PortalCharge>;
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
                            <DataTableActionsHeader />
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
                <AppPagination
                    :paginator="contracts"
                    page-name="contracts"
                    :only="['contracts']"
                />
                </template>
            </CardContent>
        </Card>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Cobranças</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="charges.data.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhuma cobrança encontrada.
                </div>
                <template v-else>
                <div class="space-y-3">
                    <div
                        v-for="charge in charges.data"
                        :key="charge.id"
                        class="rounded-xl border border-border/80 p-4"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-4"
                        >
                            <div class="min-w-0 flex-1 space-y-2">
                                <div
                                    class="flex flex-wrap items-center gap-2"
                                >
                                    <p class="font-medium">
                                        {{
                                            charge.description ?? 'Cobrança'
                                        }}
                                    </p>
                                    <StatusBadge
                                        type="charge"
                                        :status="charge.status"
                                    />
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    {{ charge.property ?? 'Imóvel' }}
                                    <span
                                        v-if="charge.tenant"
                                        class="text-border"
                                    >
                                        ·
                                    </span>
                                    {{ charge.tenant }}
                                </p>
                                <p
                                    class="text-sm tabular-nums text-muted-foreground"
                                >
                                    Vencimento
                                    {{ formatDate(charge.due_date) }}
                                    <span class="mx-1.5 text-border">|</span>
                                    {{ formatCurrency(charge.amount) }}
                                </p>
                            </div>

                            <div
                                v-if="charge.is_paid"
                                class="flex w-full shrink-0 items-center justify-end sm:w-auto"
                            >
                                <Button as-child size="sm" variant="outline">
                                    <a :href="receipt.url(charge.id)">
                                        <Download />
                                        Baixar recibo
                                    </a>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
                <AppPagination
                    :paginator="charges"
                    page-name="charges"
                    :only="['charges']"
                />
                </template>
            </CardContent>
        </Card>
    </div>
</template>
