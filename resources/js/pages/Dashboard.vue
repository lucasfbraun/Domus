<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    CreditCard,
    FileSignature,
    TriangleAlert,
    Users,
    Wallet,
} from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDate } from '@/lib/dates';
import { useMoney } from '@/composables/useMoney';
import { dashboard } from '@/routes';
import { index as chargesIndex } from '@/routes/admin/charges';

interface Charge {
    id: number;
    description?: string;
    amount?: number;
    status?: string;
    due_date?: string;
    tenant?: { name: string };
    property?: { name: string };
}

interface ReceiverSummary {
    name: string;
    expected: number;
    received: number;
    open: number;
}

interface ContractSummary {
    id: number;
    status?: string;
    monthly_rent?: number;
    property?: string;
    tenant?: string;
    receiver?: string;
}

defineProps<{
    stats: {
        expected: number;
        received: number;
        open: number;
        overdue: number;
        openCharges: number;
        activeContracts: number;
        tenantsCount: number;
    };
    byReceiver: ReceiverSummary[];
    recentCharges: Charge[];
    activeContracts: ContractSummary[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Painel',
                href: dashboard(),
            },
        ],
    },
});

const { formatCurrency } = useMoney();
</script>

<template>
    <Head title="Painel" />

    <div class="flex flex-col gap-10">
        <Heading
            title="Painel"
            description="Resumo financeiro e operacional dos aluguéis"
        />

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <Card class="overflow-hidden border-0 bg-stat-sky shadow-none ring-1 ring-stat-sky-fg/10">
                <CardHeader class="flex flex-row items-start justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium text-stat-sky-fg/80">
                        Esperado
                    </CardTitle>
                    <Wallet class="size-5 text-stat-sky-fg" />
                </CardHeader>
                <CardContent class="pt-2">
                    <p class="text-3xl font-semibold tracking-tight text-stat-sky-fg">
                        {{ formatCurrency(stats.expected) }}
                    </p>
                </CardContent>
            </Card>

            <Card class="overflow-hidden border-0 bg-stat-violet shadow-none ring-1 ring-stat-violet-fg/10">
                <CardHeader class="flex flex-row items-start justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium text-stat-violet-fg/80">
                        Recebido
                    </CardTitle>
                    <CreditCard class="size-5 text-stat-violet-fg" />
                </CardHeader>
                <CardContent class="pt-2">
                    <p class="text-3xl font-semibold tracking-tight text-stat-violet-fg">
                        {{ formatCurrency(stats.received) }}
                    </p>
                </CardContent>
            </Card>

            <Card class="overflow-hidden border-0 bg-stat-amber shadow-none ring-1 ring-stat-amber-fg/10">
                <CardHeader class="flex flex-row items-start justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium text-stat-amber-fg/80">
                        Em aberto
                    </CardTitle>
                    <FileSignature class="size-5 text-stat-amber-fg" />
                </CardHeader>
                <CardContent class="pt-2">
                    <p class="text-3xl font-semibold tracking-tight text-stat-amber-fg">
                        {{ formatCurrency(stats.open) }}
                    </p>
                    <p class="mt-1 text-xs text-stat-amber-fg/70">
                        {{ stats.openCharges }} cobranças
                    </p>
                </CardContent>
            </Card>

            <Card class="overflow-hidden border-0 bg-red-50 shadow-none ring-1 ring-red-500/15">
                <CardHeader class="flex flex-row items-start justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium text-red-700/80">
                        Vencido
                    </CardTitle>
                    <TriangleAlert class="size-5 text-red-700" />
                </CardHeader>
                <CardContent class="pt-2">
                    <p class="text-3xl font-semibold tracking-tight text-red-700">
                        {{ formatCurrency(stats.overdue) }}
                    </p>
                    <p class="mt-1 text-xs text-red-700/70">
                        {{ stats.tenantsCount }} inquilinos · {{ stats.activeContracts }} contratos
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <Card class="border-border/80 shadow-sm">
                <CardHeader>
                    <CardTitle class="text-lg">Por recebedor</CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="byReceiver.length === 0"
                        class="rounded-xl bg-muted/50 px-6 py-10 text-center text-sm text-muted-foreground"
                    >
                        Sem movimentação financeira.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="pb-3 pr-4 font-medium">Recebedor</th>
                                    <th class="pb-3 pr-4 font-medium">Esperado</th>
                                    <th class="pb-3 pr-4 font-medium">Recebido</th>
                                    <th class="pb-3 font-medium">Aberto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in byReceiver"
                                    :key="row.name"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-3 pr-4 font-medium">{{ row.name }}</td>
                                    <td class="py-3 pr-4 tabular-nums">
                                        {{ formatCurrency(row.expected) }}
                                    </td>
                                    <td class="py-3 pr-4 tabular-nums">
                                        {{ formatCurrency(row.received) }}
                                    </td>
                                    <td class="py-3 tabular-nums">
                                        {{ formatCurrency(row.open) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <Card class="border-border/80 shadow-sm">
                <CardHeader class="flex flex-row items-center justify-between gap-4">
                    <CardTitle class="text-lg">Contratos ativos</CardTitle>
                    <Users class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div
                        v-if="activeContracts.length === 0"
                        class="rounded-xl bg-muted/50 px-6 py-10 text-center text-sm text-muted-foreground"
                    >
                        Nenhum contrato ativo.
                    </div>
                    <ul v-else class="space-y-3 text-sm">
                        <li
                            v-for="contract in activeContracts"
                            :key="contract.id"
                            class="flex items-start justify-between gap-3 border-b border-border/60 pb-3 last:border-0 last:pb-0"
                        >
                            <div>
                                <p class="font-medium">{{ contract.property ?? 'Imóvel' }}</p>
                                <p class="text-muted-foreground">
                                    {{ contract.tenant }} · {{ contract.receiver }}
                                </p>
                            </div>
                            <div class="text-right">
                                <StatusBadge
                                    type="contract"
                                    :status="contract.status"
                                />
                                <p class="mt-1 tabular-nums">
                                    {{ formatCurrency(contract.monthly_rent) }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>

        <Card class="border-border/80 shadow-sm">
            <CardHeader class="flex flex-row items-center justify-between gap-4 pb-2">
                <div class="space-y-1">
                    <CardTitle class="text-lg">Cobranças recentes</CardTitle>
                    <p class="text-sm text-muted-foreground">
                        Últimas movimentações financeiras
                    </p>
                </div>
                <Link
                    :href="chargesIndex()"
                    class="shrink-0 text-sm font-medium text-primary underline-offset-4 hover:underline"
                >
                    Ver todas
                </Link>
            </CardHeader>
            <CardContent class="pt-4">
                <div
                    v-if="recentCharges.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhuma cobrança recente.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border/80 text-left text-muted-foreground">
                                <th class="pb-3 pr-6 font-medium">Descrição</th>
                                <th class="pb-3 pr-6 font-medium">Inquilino</th>
                                <th class="pb-3 pr-6 font-medium">Valor</th>
                                <th class="pb-3 pr-6 font-medium">Vencimento</th>
                                <th class="pb-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="charge in recentCharges"
                                :key="charge.id"
                                class="border-b border-border/60 last:border-0"
                            >
                                <td class="py-4 pr-6 font-medium">
                                    {{ charge.description ?? '-' }}
                                </td>
                                <td class="py-4 pr-6 text-muted-foreground">
                                    {{ charge.tenant?.name ?? '-' }}
                                </td>
                                <td class="py-4 pr-6 tabular-nums">
                                    {{ formatCurrency(charge.amount) }}
                                </td>
                                <td class="py-4 pr-6 text-muted-foreground tabular-nums">
                                    {{ formatDate(charge.due_date, '-') }}
                                </td>
                                <td class="py-4">
                                    <StatusBadge
                                        type="charge"
                                        :status="charge.status"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
