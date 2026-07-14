<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useHttp } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

defineProps<{
    contracts: any[];
    charges: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Portal Inquilino', href: '/inquilino' },
        ],
    },
});

const http = useHttp();
const payingId = ref<number | null>(null);
const pixData = ref<Record<number, string>>({});

function formatCurrency(value?: number): string {
    if (value === undefined || value === null) {
        return '—';
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}

async function payPix(id: number): Promise<void> {
    payingId.value = id;

    try {
        const response = await http.post(`/charges/${id}/pix`);
        const data = response as { qr_code?: string; copy_paste?: string };

        pixData.value[id] = data.copy_paste ?? data.qr_code ?? 'PIX gerado com sucesso.';
    } finally {
        payingId.value = null;
    }
}
</script>

<template>
    <Head title="Portal Inquilino" />

    <div class="flex flex-col gap-8">
        <Heading
            title="Portal Inquilino"
            description="Seus contratos e cobranças"
        />

        <Card>
            <CardHeader>
                <CardTitle>Meus contratos</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="contracts.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhum contrato encontrado.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Imóvel</th>
                                <th class="pb-2 pr-4 font-medium">Valor</th>
                                <th class="pb-2 pr-4 font-medium">Início</th>
                                <th class="pb-2 pr-4 font-medium">Status</th>
                                <th class="pb-2 font-medium">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="contract in contracts"
                                :key="contract.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">
                                    {{ contract.property?.name ?? '—' }}
                                </td>
                                <td class="py-3 pr-4">
                                    {{ formatCurrency(contract.monthly_rent) }}
                                </td>
                                <td class="py-3 pr-4">
                                    {{ contract.starts_at ?? '—' }}
                                </td>
                                <td class="py-3 pr-4">
                                    <Badge variant="outline">
                                        {{ contract.status ?? '—' }}
                                    </Badge>
                                </td>
                                <td class="py-3">
                                    <Button as-child size="sm" variant="outline">
                                        <Link :href="`/contrato/${contract.id}`">
                                            Ver contrato
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Card>
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
                <div v-else class="space-y-4">
                    <div
                        v-for="charge in charges"
                        :key="charge.id"
                        class="rounded-lg border p-4"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="font-medium">
                                    {{ charge.description ?? 'Cobrança' }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ charge.property ?? 'Imóvel' }} ·
                                    Vencimento: {{ charge.due_date ?? '—' }} ·
                                    {{ formatCurrency(charge.amount) }}
                                </p>
                                <Badge class="mt-2" variant="outline">
                                    {{ charge.status ?? '—' }}
                                </Badge>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-if="!charge.is_paid"
                                    size="sm"
                                    :disabled="payingId === charge.id"
                                    @click="payPix(charge.id)"
                                >
                                    {{
                                        payingId === charge.id
                                            ? 'Gerando PIX...'
                                            : 'Pagar com PIX'
                                    }}
                                </Button>
                                <Button
                                    v-if="charge.is_paid"
                                    as-child
                                    size="sm"
                                    variant="outline"
                                >
                                    <a :href="`/charges/${charge.id}/receipt`">
                                        Baixar recibo
                                    </a>
                                </Button>
                            </div>
                        </div>
                        <p
                            v-if="pixData[charge.id]"
                            class="mt-3 break-all rounded bg-muted p-3 text-xs"
                        >
                            {{ pixData[charge.id] }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
