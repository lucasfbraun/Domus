<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
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
    charges: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: '/dashboard' },
            { title: 'Cobranças', href: '/charges' },
        ],
    },
});

const http = useHttp();
const processingIds = ref<Record<number, 'pix' | 'sync' | 'reminder' | null>>({});

function formatCurrency(value?: number): string {
    if (value === undefined || value === null) {
        return '—';
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}

async function createPix(id: number): Promise<void> {
    processingIds.value[id] = 'pix';

    try {
        await http.post(`/charges/${id}/pix`);
        router.reload({ only: ['charges'] });
    } finally {
        processingIds.value[id] = null;
    }
}

async function syncPayment(id: number): Promise<void> {
    processingIds.value[id] = 'sync';

    try {
        await http.post(`/charges/${id}/sync`);
        router.reload({ only: ['charges'] });
    } finally {
        processingIds.value[id] = null;
    }
}
</script>

<template>
    <Head title="Cobranças" />

    <div class="flex flex-col gap-8">
        <Heading
            title="Cobranças"
            description="Gerencie as cobranças e pagamentos"
        />

        <Card>
            <CardHeader>
                <CardTitle>Lista de cobranças</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="charges.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhuma cobrança cadastrada.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Descrição</th>
                                <th class="pb-2 pr-4 font-medium">Inquilino</th>
                                <th class="pb-2 pr-4 font-medium">Valor</th>
                                <th class="pb-2 pr-4 font-medium">Vencimento</th>
                                <th class="pb-2 pr-4 font-medium">Status</th>
                                <th class="pb-2 font-medium">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="charge in charges"
                                :key="charge.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">
                                    {{ charge.description ?? '—' }}
                                </td>
                                <td class="py-3 pr-4">
                                    {{ charge.tenant?.name ?? '—' }}
                                </td>
                                <td class="py-3 pr-4">
                                    {{ formatCurrency(charge.amount) }}
                                </td>
                                <td class="py-3 pr-4">
                                    {{ charge.due_date ?? '—' }}
                                </td>
                                <td class="py-3 pr-4">
                                    <Badge variant="outline">
                                        {{ charge.status ?? '—' }}
                                    </Badge>
                                </td>
                                <td class="py-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <Button
                                            v-if="charge.status !== 'paid'"
                                            size="sm"
                                            variant="outline"
                                            :disabled="!!processingIds[charge.id]"
                                            @click="createPix(charge.id)"
                                        >
                                            {{
                                                processingIds[charge.id] === 'pix'
                                                    ? 'Gerando...'
                                                    : 'Gerar PIX'
                                            }}
                                        </Button>
                                        <Button
                                            v-if="charge.status !== 'paid'"
                                            size="sm"
                                            variant="outline"
                                            :disabled="!!processingIds[charge.id]"
                                            @click="syncPayment(charge.id)"
                                        >
                                            {{
                                                processingIds[charge.id] === 'sync'
                                                    ? 'Sincronizando...'
                                                    : 'Sincronizar'
                                            }}
                                        </Button>
                                        <Form
                                            v-if="charge.status !== 'paid'"
                                            :action="`/charges/${charge.id}/reminder`"
                                            method="post"
                                            #default="{ processing }"
                                        >
                                            <Button
                                                type="submit"
                                                size="sm"
                                                variant="outline"
                                                :disabled="processing"
                                            >
                                                Lembrete
                                            </Button>
                                        </Form>
                                        <Button
                                            v-if="charge.status === 'paid'"
                                            as-child
                                            size="sm"
                                            variant="outline"
                                        >
                                            <a :href="`/charges/${charge.id}/receipt`">
                                                Recibo
                                            </a>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
