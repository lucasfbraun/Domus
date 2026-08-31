<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Download } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DataTable,
    DataTableCell,
    DataTableHeadCell,
    DataTableRow,
} from '@/components/ui/data-table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { buttonVariants } from '@/components/ui/button';
import { formatDate } from '@/lib/dates';
import { useMoney } from '@/composables/useMoney';
import { dashboard } from '@/routes';
import { index, pdf } from '@/routes/admin/income-report';

type MonthRow = {
    reference: string;
    month: number;
    label: string;
    total: number;
    count: number;
};

type PaymentRow = {
    id: number;
    paid_at: string | null;
    net_amount: number;
    amount_paid: number;
    fees: number;
    method: string | null;
    reference: string | null;
    tenant: string | null;
    property: string | null;
    receiver: string | null;
};

type Option = { id: number; name: string };

const props = defineProps<{
    filters: {
        year: number;
        month: number | null;
        owner_id: number | null;
        receiver_id: number | null;
    };
    months: MonthRow[];
    total: number;
    payments: PaymentRow[];
    owners: Option[];
    receivers: Option[];
    availableYears: number[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Informe de Rendimentos', href: index() },
        ],
    },
});

const { formatCurrency } = useMoney();

const ALL = '__all__';

const monthOptions = [
    { value: 1, label: 'Janeiro' },
    { value: 2, label: 'Fevereiro' },
    { value: 3, label: 'Março' },
    { value: 4, label: 'Abril' },
    { value: 5, label: 'Maio' },
    { value: 6, label: 'Junho' },
    { value: 7, label: 'Julho' },
    { value: 8, label: 'Agosto' },
    { value: 9, label: 'Setembro' },
    { value: 10, label: 'Outubro' },
    { value: 11, label: 'Novembro' },
    { value: 12, label: 'Dezembro' },
];

function applyFilters(next: Partial<typeof props.filters>): void {
    const merged = { ...props.filters, ...next };

    router.get(
        index().url,
        {
            year: merged.year,
            month: merged.month ?? undefined,
            owner_id: merged.owner_id ?? undefined,
            receiver_id: merged.receiver_id ?? undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const pdfUrl = computed(() =>
    pdf({
        query: {
            year: props.filters.year,
            ...(props.filters.month ? { month: props.filters.month } : {}),
            ...(props.filters.owner_id
                ? { owner_id: props.filters.owner_id }
                : {}),
            ...(props.filters.receiver_id
                ? { receiver_id: props.filters.receiver_id }
                : {}),
        },
    }).url,
);
</script>

<template>
    <Head title="Informe de Rendimentos" />

    <div class="flex flex-col gap-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Heading
                title="Informe de Rendimentos"
                description="Rendimento líquido efetivamente recebido, por mês"
            />
            <a :href="pdfUrl" :class="buttonVariants({ variant: 'outline' })">
                <Download class="size-4" />
                Baixar PDF
            </a>
        </div>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Filtros</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium">Ano</label>
                        <Select
                            :model-value="String(filters.year)"
                            @update:model-value="
                                (value) =>
                                    applyFilters({
                                        year: Number(value as string),
                                    })
                            "
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="year in availableYears"
                                    :key="year"
                                    :value="String(year)"
                                >
                                    {{ year }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium">Mês</label>
                        <Select
                            :model-value="
                                filters.month ? String(filters.month) : ALL
                            "
                            @update:model-value="
                                (value) =>
                                    applyFilters({
                                        month:
                                            value === ALL
                                                ? null
                                                : Number(value as string),
                                    })
                            "
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="ALL">
                                    Todos os meses
                                </SelectItem>
                                <SelectItem
                                    v-for="option in monthOptions"
                                    :key="option.value"
                                    :value="String(option.value)"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium"
                            >Proprietário</label
                        >
                        <Select
                            :model-value="
                                filters.owner_id
                                    ? String(filters.owner_id)
                                    : ALL
                            "
                            @update:model-value="
                                (value) =>
                                    applyFilters({
                                        owner_id:
                                            value === ALL
                                                ? null
                                                : Number(value as string),
                                    })
                            "
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="ALL">Todos</SelectItem>
                                <SelectItem
                                    v-for="owner in owners"
                                    :key="owner.id"
                                    :value="String(owner.id)"
                                >
                                    {{ owner.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium">Recebedor</label>
                        <Select
                            :model-value="
                                filters.receiver_id
                                    ? String(filters.receiver_id)
                                    : ALL
                            "
                            @update:model-value="
                                (value) =>
                                    applyFilters({
                                        receiver_id:
                                            value === ALL
                                                ? null
                                                : Number(value as string),
                                    })
                            "
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="ALL">Todos</SelectItem>
                                <SelectItem
                                    v-for="receiver in receivers"
                                    :key="receiver.id"
                                    :value="String(receiver.id)"
                                >
                                    {{ receiver.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Total do período</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-3xl font-semibold tabular-nums">
                    {{ formatCurrency(total) }}
                </div>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ payments.length }} pagamento(s) recebido(s)
                </p>
            </CardContent>
        </Card>

        <Card v-if="months.length > 1" class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Rendimento por mês</CardTitle>
            </CardHeader>
            <CardContent>
                <DataTable>
                    <thead>
                        <DataTableRow variant="header">
                            <DataTableHeadCell>Mês</DataTableHeadCell>
                            <DataTableHeadCell>Rendimento</DataTableHeadCell>
                            <DataTableHeadCell
                                >Nº pagamentos</DataTableHeadCell
                            >
                        </DataTableRow>
                    </thead>
                    <tbody>
                        <DataTableRow
                            v-for="monthRow in months"
                            :key="monthRow.reference"
                        >
                            <DataTableCell>{{ monthRow.label }}</DataTableCell>
                            <DataTableCell class="tabular-nums">
                                {{ formatCurrency(monthRow.total) }}
                            </DataTableCell>
                            <DataTableCell>{{ monthRow.count }}</DataTableCell>
                        </DataTableRow>
                    </tbody>
                </DataTable>
            </CardContent>
        </Card>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Detalhamento dos pagamentos</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="payments.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhum pagamento recebido no período selecionado.
                </div>
                <DataTable v-else>
                    <thead>
                        <DataTableRow variant="header">
                            <DataTableHeadCell>Data</DataTableHeadCell>
                            <DataTableHeadCell>Referência</DataTableHeadCell>
                            <DataTableHeadCell>Imóvel</DataTableHeadCell>
                            <DataTableHeadCell>Inquilino</DataTableHeadCell>
                            <DataTableHeadCell>Recebedor</DataTableHeadCell>
                            <DataTableHeadCell
                                >Valor líquido</DataTableHeadCell
                            >
                        </DataTableRow>
                    </thead>
                    <tbody>
                        <DataTableRow
                            v-for="payment in payments"
                            :key="payment.id"
                        >
                            <DataTableCell>{{
                                formatDate(payment.paid_at)
                            }}</DataTableCell>
                            <DataTableCell>{{
                                payment.reference ?? '—'
                            }}</DataTableCell>
                            <DataTableCell>{{
                                payment.property ?? '—'
                            }}</DataTableCell>
                            <DataTableCell>{{
                                payment.tenant ?? '—'
                            }}</DataTableCell>
                            <DataTableCell>{{
                                payment.receiver ?? '—'
                            }}</DataTableCell>
                            <DataTableCell class="tabular-nums">
                                {{ formatCurrency(payment.net_amount) }}
                            </DataTableCell>
                        </DataTableRow>
                    </tbody>
                </DataTable>
            </CardContent>
        </Card>
    </div>
</template>
