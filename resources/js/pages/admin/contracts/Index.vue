<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Eye, Pencil, Trash2 } from '@lucide/vue';
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
import { dashboard } from '@/routes';
import {
    create,
    destroy,
    edit,
    index,
    show,
} from '@/routes/admin/contracts';
import type { Paginated } from '@/types';

defineProps<{
    contracts: Paginated<any>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Contratos', href: index() },
        ],
    },
});

const { formatCurrency } = useMoney();
</script>

<template>
    <Head title="Contratos" />

    <div class="flex flex-col gap-8">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Contratos"
                description="Gerencie os contratos de locação"
            />
            <Button as-child>
                <Link :href="create()">Novo contrato</Link>
            </Button>
        </div>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Lista de contratos</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="contracts.data.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhum contrato cadastrado.
                </div>
                <template v-else>
                <DataTable>
                    <thead>
                        <DataTableRow variant="header">
                            <DataTableHeadCell>Imóvel</DataTableHeadCell>
                            <DataTableHeadCell>Inquilino</DataTableHeadCell>
                            <DataTableHeadCell>Recebedor</DataTableHeadCell>
                            <DataTableHeadCell>Valor</DataTableHeadCell>
                            <DataTableHeadCell>Início</DataTableHeadCell>
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
                            <DataTableCell>
                                {{ contract.receiver?.name ?? '—' }}
                            </DataTableCell>
                            <DataTableCell class="tabular-nums">
                                {{ formatCurrency(contract.monthly_rent) }}
                            </DataTableCell>
                            <DataTableCell>
                                {{ formatDate(contract.starts_at) }}
                            </DataTableCell>
                            <DataTableCell>
                                <StatusBadge
                                    type="contract"
                                    :status="contract.status"
                                />
                            </DataTableCell>
                            <DataTableActionsCell>
                                <TableActionButton label="Ver" as-child>
                                    <Link :href="show(contract)">
                                        <Eye />
                                        <span class="sr-only">Ver</span>
                                    </Link>
                                </TableActionButton>
                                <TableActionButton label="Editar" as-child>
                                    <Link :href="edit(contract)">
                                        <Pencil />
                                        <span class="sr-only">Editar</span>
                                    </Link>
                                </TableActionButton>
                                <Form
                                    v-bind="destroy.form(contract)"
                                    #default="{ processing }"
                                >
                                    <TableActionButton
                                        label="Excluir"
                                        :icon="Trash2"
                                        type="submit"
                                        variant="destructive"
                                        :disabled="processing"
                                    />
                                </Form>
                            </DataTableActionsCell>
                        </DataTableRow>
                    </tbody>
                </DataTable>
                <AppPagination :paginator="contracts" />
                </template>
            </CardContent>
        </Card>
    </div>
</template>
