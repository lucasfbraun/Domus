<script setup lang="ts">
import { Head, Link, useHttp } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
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
import { show } from '@/routes/contracts';
import { portal } from '@/routes/tenant';

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

const props = defineProps<{
    contracts: any[];
    charges: PortalCharge[];
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

    for (const charge of props.charges) {
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
                    v-if="contracts.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhum contrato encontrado.
                </div>
                <DataTable v-else>
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
                            v-for="contract in contracts"
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
            </CardContent>
        </Card>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Minhas cobranças</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="charges.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhuma cobrança pendente.
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="charge in charges"
                        :key="charge.id"
                        class="rounded-xl border border-border/80 p-4"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 space-y-2">
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

                            <div class="flex shrink-0 flex-wrap gap-2">
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
            </CardContent>
        </Card>
    </div>
</template>
