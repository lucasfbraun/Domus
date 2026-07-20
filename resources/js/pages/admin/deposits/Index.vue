<script setup lang="ts">
import { Form, router, useHttp, Head } from '@inertiajs/vue3';
import { QrCode, RefreshCw, RotateCcw, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import FormSelect from '@/components/FormSelect.vue';
import Heading from '@/components/Heading.vue';
import AppPagination from '@/components/AppPagination.vue';
import InputError from '@/components/InputError.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/dates';
import { useMoney } from '@/composables/useMoney';
import { dashboard } from '@/routes';
import { destroy, index, refund, store } from '@/routes/admin/deposits';
import { pix, sync } from '@/routes/deposits';
import type { Paginated } from '@/types';

type ContractOption = { id: number; label: string };
type ReceiverOption = { id: number; name: string };

const props = withDefaults(
    defineProps<{
        deposits: Paginated<any>;
        contracts?: ContractOption[];
        receivers?: ReceiverOption[];
    }>(),
    {
        contracts: () => [],
        receivers: () => [],
    },
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Cauções', href: index() },
        ],
    },
});

const { formatCurrency } = useMoney();

const http = useHttp();
const processingIds = ref<Record<number, 'pix' | 'sync' | 'refund' | null>>({});

const contractOptions = props.contracts.map((contract) => ({
    value: String(contract.id),
    label: contract.label,
}));

const receiverOptions = props.receivers.map((receiver) => ({
    value: String(receiver.id),
    label: receiver.name,
}));

function createPix(deposit: { id: number }): void {
    processingIds.value[deposit.id] = 'pix';

    http.post(pix.url(deposit), {
        onSuccess: () => {
            router.reload({ only: ['deposits'] });
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
            processingIds.value[deposit.id] = null;
        },
    });
}

function syncPayment(deposit: { id: number }): void {
    processingIds.value[deposit.id] = 'sync';

    http.post(sync.url(deposit), {
        onSuccess: () => {
            router.reload({ only: ['deposits'] });
        },
        onFinish: () => {
            processingIds.value[deposit.id] = null;
        },
    });
}

function markRefunded(deposit: { id: number }): void {
    processingIds.value[deposit.id] = 'refund';

    http.post(refund.url(deposit), {
        onSuccess: () => {
            router.reload({ only: ['deposits'] });
        },
        onFinish: () => {
            processingIds.value[deposit.id] = null;
        },
    });
}
</script>

<template>
    <Head title="Cauções" />

    <div class="flex flex-col gap-8">
        <Heading
            title="Cauções"
            description="Cadastre e acompanhe o pagamento das cauções dos inquilinos"
        />

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Nova caução</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="store.form()"
                    class="grid gap-5 md:grid-cols-2"
                    #default="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="contract_id">Contrato / inquilino</Label>
                        <FormSelect
                            id="contract_id"
                            name="contract_id"
                            :options="contractOptions"
                            placeholder="Selecione o contrato"
                            required
                        />
                        <InputError :message="errors.contract_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="receiver_id">Recebedor</Label>
                        <FormSelect
                            id="receiver_id"
                            name="receiver_id"
                            :options="receiverOptions"
                            placeholder="Selecione o recebedor"
                            required
                        />
                        <InputError :message="errors.receiver_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="amount">Valor</Label>
                        <Input
                            id="amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            name="amount"
                            placeholder="0,00"
                            required
                        />
                        <InputError :message="errors.amount" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="due_date">Data</Label>
                        <Input id="due_date" type="date" name="due_date" required />
                        <InputError :message="errors.due_date" />
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label for="description">Descritivo</Label>
                        <Input
                            id="description"
                            name="description"
                            placeholder="Ex.: Caução referente ao contrato de locação"
                            required
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="md:col-span-2">
                        <Button type="submit" :disabled="processing">
                            Cadastrar caução
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Cauções cadastradas</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="deposits.data.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhuma caução cadastrada.
                </div>
                <template v-else>
                <DataTable>
                    <thead>
                        <DataTableRow variant="header">
                            <DataTableHeadCell>Descrição</DataTableHeadCell>
                            <DataTableHeadCell>Inquilino</DataTableHeadCell>
                            <DataTableHeadCell>Imóvel</DataTableHeadCell>
                            <DataTableHeadCell>Valor</DataTableHeadCell>
                            <DataTableHeadCell>Data</DataTableHeadCell>
                            <DataTableHeadCell>Status</DataTableHeadCell>
                            <DataTableActionsHeader />
                        </DataTableRow>
                    </thead>
                    <tbody>
                        <DataTableRow
                            v-for="deposit in deposits.data"
                            :key="deposit.id"
                        >
                            <DataTableCell>
                                {{ deposit.description ?? '—' }}
                            </DataTableCell>
                            <DataTableCell>
                                {{ deposit.tenant?.name ?? '—' }}
                            </DataTableCell>
                            <DataTableCell>
                                {{ deposit.property?.name ?? '—' }}
                            </DataTableCell>
                            <DataTableCell class="tabular-nums">
                                {{ formatCurrency(deposit.amount) }}
                            </DataTableCell>
                            <DataTableCell>
                                {{ formatDate(deposit.due_date) }}
                            </DataTableCell>
                            <DataTableCell>
                                <StatusBadge
                                    type="deposit"
                                    :status="deposit.status"
                                />
                                <div
                                    v-if="deposit.status === 'refunded' && deposit.refunded_amount"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ formatCurrency(deposit.refunded_amount) }}
                                    em {{ formatDate(deposit.refunded_at) }}
                                </div>
                            </DataTableCell>
                            <DataTableActionsCell>
                                <TableActionButton
                                    v-if="deposit.status !== 'paid' && deposit.status !== 'refunded'"
                                    label="Gerar PIX"
                                    :icon="QrCode"
                                    :disabled="!!processingIds[deposit.id]"
                                    @click="createPix(deposit)"
                                />
                                <TableActionButton
                                    v-if="deposit.status !== 'paid' && deposit.status !== 'refunded'"
                                    label="Sincronizar"
                                    :icon="RefreshCw"
                                    :disabled="!!processingIds[deposit.id]"
                                    @click="syncPayment(deposit)"
                                />
                                <TableActionButton
                                    v-if="deposit.status === 'paid'"
                                    label="Marcar devolvida"
                                    :icon="RotateCcw"
                                    :disabled="!!processingIds[deposit.id]"
                                    @click="markRefunded(deposit)"
                                />
                                <Form
                                    v-bind="destroy.form(deposit)"
                                    #default="{ processing: deleting }"
                                >
                                    <TableActionButton
                                        label="Excluir"
                                        :icon="Trash2"
                                        type="submit"
                                        variant="destructive"
                                        :disabled="deleting"
                                    />
                                </Form>
                            </DataTableActionsCell>
                        </DataTableRow>
                    </tbody>
                </DataTable>
                <AppPagination :paginator="deposits" />
                </template>
            </CardContent>
        </Card>
    </div>
</template>
