<script setup lang="ts">
import { Form, Head, router, useHttp } from '@inertiajs/vue3';
import { Bell, FileText, QrCode, RefreshCw } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
import AppPagination from '@/components/AppPagination.vue';
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
import { formatPhone } from '@/lib/brazilian-masks';
import { useMoney } from '@/composables/useMoney';
import { dashboard } from '@/routes';
import { index, reminder } from '@/routes/admin/charges';
import { pix, receipt, sync } from '@/routes/charges';
import type { Paginated } from '@/types';

defineProps<{
    charges: Paginated<any>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Cobranças', href: index() },
        ],
    },
});

const { formatCurrency } = useMoney();

const http = useHttp();
const processingIds = ref<Record<number, 'pix' | 'sync' | 'reminder' | null>>(
    {},
);

function createPix(charge: { id: number }): void {
    processingIds.value[charge.id] = 'pix';

    http.post(pix.url(charge), {
        onSuccess: () => {
            router.reload({ only: ['charges'] });
        },
        onHttpException: (response) => {
            let message = 'Não foi possível gerar o Pix.';

            try {
                const body =
                    typeof response.data === 'string'
                        ? JSON.parse(response.data)
                        : response.data;

                if (
                    body &&
                    typeof body === 'object' &&
                    'message' in body &&
                    typeof body.message === 'string'
                ) {
                    message = body.message;
                }
            } catch {
                // ignore
            }

            toast.error(message, { richColors: true });
        },
        onFinish: () => {
            processingIds.value[charge.id] = null;
        },
    });
}

function syncPayment(charge: { id: number }): void {
    processingIds.value[charge.id] = 'sync';

    http.post(sync.url(charge), {
        onSuccess: () => {
            router.reload({ only: ['charges'] });
        },
        onFinish: () => {
            processingIds.value[charge.id] = null;
        },
    });
}
</script>

<template>
    <Head title="Cobranças" />

    <div class="flex flex-col gap-8">
        <Heading
            title="Cobranças"
            description="Gerencie as cobranças e pagamentos"
        />

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Lista de cobranças</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="charges.data.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhuma cobrança cadastrada.
                </div>
                <template v-else>
                <DataTable>
                    <thead>
                        <DataTableRow variant="header">
                            <DataTableHeadCell>Descrição</DataTableHeadCell>
                            <DataTableHeadCell>Inquilino</DataTableHeadCell>
                            <DataTableHeadCell>WhatsApp</DataTableHeadCell>
                            <DataTableHeadCell>Valor</DataTableHeadCell>
                            <DataTableHeadCell>Vencimento</DataTableHeadCell>
                            <DataTableHeadCell>Status</DataTableHeadCell>
                            <DataTableActionsHeader />
                        </DataTableRow>
                    </thead>
                    <tbody>
                        <DataTableRow
                            v-for="charge in charges.data"
                            :key="charge.id"
                        >
                            <DataTableCell>
                                {{ charge.description ?? '—' }}
                            </DataTableCell>
                            <DataTableCell>
                                {{ charge.tenant?.name ?? '—' }}
                            </DataTableCell>
                            <DataTableCell>
                                {{
                                    charge.tenant?.whatsapp
                                        ? formatPhone(charge.tenant.whatsapp)
                                        : '—'
                                }}
                            </DataTableCell>
                            <DataTableCell class="tabular-nums">
                                {{ formatCurrency(charge.amount) }}
                                <span
                                    v-if="charge.rateio_amount > 0"
                                    class="block text-xs text-muted-foreground"
                                >
                                    inclui rateio de
                                    {{ formatCurrency(charge.rateio_amount) }}
                                </span>
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
                            <DataTableActionsCell>
                                <TableActionButton
                                    v-if="charge.status !== 'paid'"
                                    label="Gerar PIX"
                                    :icon="QrCode"
                                    :disabled="!!processingIds[charge.id]"
                                    @click="createPix(charge)"
                                />
                                <TableActionButton
                                    v-if="charge.status !== 'paid'"
                                    label="Sincronizar"
                                    :icon="RefreshCw"
                                    :disabled="!!processingIds[charge.id]"
                                    @click="syncPayment(charge)"
                                />
                                <Form
                                    v-if="charge.status !== 'paid'"
                                    v-bind="reminder.form(charge)"
                                    #default="{ processing }"
                                >
                                    <TableActionButton
                                        label="Lembrete"
                                        :icon="Bell"
                                        type="submit"
                                        :disabled="processing"
                                    />
                                </Form>
                                <TableActionButton
                                    v-if="charge.status === 'paid'"
                                    label="Recibo"
                                    as-child
                                >
                                    <a :href="receipt(charge).url">
                                        <FileText />
                                        <span class="sr-only">Recibo</span>
                                    </a>
                                </TableActionButton>
                            </DataTableActionsCell>
                        </DataTableRow>
                    </tbody>
                </DataTable>
                <AppPagination :paginator="charges" />
                </template>
            </CardContent>
        </Card>
    </div>
</template>
