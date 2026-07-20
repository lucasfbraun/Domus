<script setup lang="ts">
import { Head, Link, useHttp } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
import AppPagination from '@/components/AppPagination.vue';
import PixPaymentPanel from '@/components/PixPaymentPanel.vue';
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
import { pix, receipt } from '@/routes/charges';
import { pix as depositPix } from '@/routes/deposits';
import { show } from '@/routes/contracts';
import { portal } from '@/routes/tenant';
import type { Paginated } from '@/types';

type PortalCharge = {
    id: number;
    description?: string | null;
    amount: number;
    amount_due: number;
    has_penalties?: boolean;
    status: string;
    due_date: string;
    is_paid: boolean;
    property?: string | null;
    pix_qr_code?: string | null;
    pix_qr_code_base64?: string | null;
    pix_expires_at?: string | null;
    has_pix?: boolean;
};

type PixPayload = {
    copyPaste: string;
    qrCodeBase64: string | null;
    expiresAt: string | null;
};

type PixResponse = {
    qr_code?: string;
    copy_paste?: string;
    qr_code_base64?: string;
    expires_at?: string;
};

type PortalDeposit = {
    id: number;
    description?: string | null;
    amount: number;
    status: string;
    due_date: string;
    is_paid: boolean;
    is_refunded: boolean;
    refunded_at?: string | null;
    refunded_amount?: number | null;
    property?: string | null;
    pix_qr_code?: string | null;
    pix_qr_code_base64?: string | null;
    pix_expires_at?: string | null;
    has_pix?: boolean;
};

const props = defineProps<{
    contracts: Paginated<any>;
    charges: Paginated<PortalCharge>;
    deposits: Paginated<PortalDeposit>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Portal Inquilino', href: portal() },
        ],
    },
});

const { formatCurrency } = useMoney();

const http = useHttp<Record<string, never>, PixResponse>();
const payingId = ref<number | null>(null);
const generatedPix = ref<Record<number, PixPayload>>({});

const pixByCharge = computed<Record<number, PixPayload>>(() => {
    const merged: Record<number, PixPayload> = { ...generatedPix.value };

    for (const charge of props.charges.data) {
        if (merged[charge.id] || !charge.pix_qr_code) {
            continue;
        }

        merged[charge.id] = {
            copyPaste: charge.pix_qr_code,
            qrCodeBase64: charge.pix_qr_code_base64 ?? null,
            expiresAt: charge.pix_expires_at ?? null,
        };
    }

    return merged;
});

const payingDepositId = ref<number | null>(null);
const generatedDepositPix = ref<Record<number, PixPayload>>({});

const pixByDeposit = computed<Record<number, PixPayload>>(() => {
    const merged: Record<number, PixPayload> = { ...generatedDepositPix.value };

    for (const deposit of props.deposits.data) {
        if (merged[deposit.id] || !deposit.pix_qr_code) {
            continue;
        }

        merged[deposit.id] = {
            copyPaste: deposit.pix_qr_code,
            qrCodeBase64: deposit.pix_qr_code_base64 ?? null,
            expiresAt: deposit.pix_expires_at ?? null,
        };
    }

    return merged;
});

function pixErrorMessage(response: { data?: unknown }): string {
    try {
        const body =
            typeof response.data === 'string'
                ? JSON.parse(response.data)
                : response.data;

        if (
            body &&
            typeof body === 'object' &&
            'message' in body &&
            typeof body.message === 'string' &&
            body.message.length > 0
        ) {
            return body.message;
        }
    } catch {
        // ignore parse errors
    }

    return 'Não foi possível gerar o Pix. Tente novamente.';
}

function payPix(id: number): void {
    payingId.value = id;

    http.post(pix.url(id), {
        onSuccess: (data) => {
            if (!data) {
                return;
            }

            generatedPix.value[id] = {
                copyPaste:
                    data.copy_paste ??
                    data.qr_code ??
                    'PIX gerado com sucesso.',
                qrCodeBase64: data.qr_code_base64 ?? null,
                expiresAt: data.expires_at ?? null,
            };
        },
        onHttpException: (response) => {
            toast.error(pixErrorMessage(response), { richColors: true });
        },
        onFinish: () => {
            payingId.value = null;
        },
    });
}

function payDepositPix(id: number): void {
    payingDepositId.value = id;

    http.post(depositPix.url(id), {
        onSuccess: (data) => {
            if (!data) {
                return;
            }

            generatedDepositPix.value[id] = {
                copyPaste:
                    data.copy_paste ??
                    data.qr_code ??
                    'PIX gerado com sucesso.',
                qrCodeBase64: data.qr_code_base64 ?? null,
                expiresAt: data.expires_at ?? null,
            };
        },
        onHttpException: (response) => {
            toast.error(pixErrorMessage(response), { richColors: true });
        },
        onFinish: () => {
            payingDepositId.value = null;
        },
    });
}
</script>

<template>
    <Head title="Portal Inquilino" />

    <div class="flex flex-col gap-8">
        <Heading
            title="Portal Inquilino"
            description="Seus contratos e cobranças"
        />

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Meus contratos</CardTitle>
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
                <CardTitle>Minhas cobranças</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="charges.data.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhuma cobrança pendente.
                </div>
                <template v-else>
                <div class="space-y-3">
                    <div
                        v-for="charge in charges.data"
                        :key="charge.id"
                        class="rounded-xl border border-border/80 p-4"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="min-w-0 flex-1 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium">
                                        {{ charge.description ?? 'Cobrança' }}
                                    </p>
                                    <StatusBadge
                                        type="charge"
                                        :status="charge.status"
                                    />
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    {{ charge.property ?? 'Imóvel' }}
                                </p>
                                <p class="text-sm tabular-nums text-muted-foreground">
                                    Vencimento {{ formatDate(charge.due_date) }}
                                    <span class="mx-1.5 text-border">|</span>
                                    {{ formatCurrency(charge.amount_due) }}
                                    <span
                                        v-if="charge.has_penalties"
                                        class="ms-1 text-xs"
                                    >
                                        (com juros/multa; original
                                        {{ formatCurrency(charge.amount) }})
                                    </span>
                                </p>
                            </div>

                            <div class="flex w-full shrink-0 flex-wrap items-center justify-end gap-2 sm:w-auto">
                                <Button
                                    v-if="!charge.is_paid && !pixByCharge[charge.id]"
                                    size="sm"
                                    :disabled="payingId === charge.id"
                                    @click="payPix(charge.id)"
                                >
                                    {{
                                        payingId === charge.id
                                            ? 'Gerando Pix...'
                                            : 'Pagar com Pix'
                                    }}
                                </Button>
                                <Button
                                    v-if="charge.is_paid"
                                    as-child
                                    size="sm"
                                    variant="outline"
                                >
                                    <a :href="receipt.url(charge.id)">
                                        Baixar recibo
                                    </a>
                                </Button>
                            </div>
                        </div>

                        <PixPaymentPanel
                            v-if="pixByCharge[charge.id]"
                            :copy-paste="pixByCharge[charge.id].copyPaste"
                            :qr-code-base64="
                                pixByCharge[charge.id].qrCodeBase64
                            "
                            :expires-at="pixByCharge[charge.id].expiresAt"
                            :refreshing="payingId === charge.id"
                            @refresh="payPix(charge.id)"
                        />
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

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Minha caução</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="deposits.data.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhuma caução registrada.
                </div>
                <template v-else>
                <div class="space-y-3">
                    <div
                        v-for="deposit in deposits.data"
                        :key="deposit.id"
                        class="rounded-xl border border-border/80 p-4"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="min-w-0 flex-1 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium">
                                        {{ deposit.description ?? 'Caução' }}
                                    </p>
                                    <StatusBadge
                                        type="deposit"
                                        :status="deposit.status"
                                    />
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    {{ deposit.property ?? 'Imóvel' }}
                                </p>
                                <p class="text-sm tabular-nums text-muted-foreground">
                                    Vencimento {{ formatDate(deposit.due_date) }}
                                    <span class="mx-1.5 text-border">|</span>
                                    {{ formatCurrency(deposit.amount) }}
                                </p>
                                <p
                                    v-if="deposit.is_refunded"
                                    class="text-sm text-muted-foreground"
                                >
                                    Devolvida em {{ formatDate(deposit.refunded_at) }}
                                    <span v-if="deposit.refunded_amount">
                                        ({{ formatCurrency(deposit.refunded_amount) }})
                                    </span>
                                </p>
                            </div>

                            <div class="flex w-full shrink-0 flex-wrap items-center justify-end gap-2 sm:w-auto">
                                <Button
                                    v-if="!deposit.is_paid && !deposit.is_refunded && !pixByDeposit[deposit.id]"
                                    size="sm"
                                    :disabled="payingDepositId === deposit.id"
                                    @click="payDepositPix(deposit.id)"
                                >
                                    {{
                                        payingDepositId === deposit.id
                                            ? 'Gerando Pix...'
                                            : 'Pagar com Pix'
                                    }}
                                </Button>
                            </div>
                        </div>

                        <PixPaymentPanel
                            v-if="pixByDeposit[deposit.id]"
                            :copy-paste="pixByDeposit[deposit.id].copyPaste"
                            :qr-code-base64="
                                pixByDeposit[deposit.id].qrCodeBase64
                            "
                            :expires-at="pixByDeposit[deposit.id].expiresAt"
                            :refreshing="payingDepositId === deposit.id"
                            @refresh="payDepositPix(deposit.id)"
                        />
                    </div>
                </div>
                <AppPagination
                    :paginator="deposits"
                    page-name="deposits"
                    :only="['deposits']"
                />
                </template>
            </CardContent>
        </Card>
    </div>
</template>
