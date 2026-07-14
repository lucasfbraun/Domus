<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
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
            { title: 'Portal Recebedor', href: '/recebedor' },
        ],
    },
});

function formatCurrency(value?: number): string {
    if (value === undefined || value === null) {
        return '—';
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}
</script>

<template>
    <Head title="Portal Recebedor" />

    <div class="flex flex-col gap-8">
        <Heading
            title="Portal Recebedor"
            description="Contratos e cobranças vinculados a você"
        />

        <Card>
            <CardHeader>
                <CardTitle>Contratos</CardTitle>
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
                                <th class="pb-2 pr-4 font-medium">Inquilino</th>
                                <th class="pb-2 pr-4 font-medium">Valor</th>
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
                                    {{ contract.tenant?.name ?? '—' }}
                                </td>
                                <td class="py-3 pr-4">
                                    {{ formatCurrency(contract.monthly_rent) }}
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
                <CardTitle>Cobranças</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="charges.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhuma cobrança encontrada.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Descrição</th>
                                <th class="pb-2 pr-4 font-medium">Inquilino</th>
                                <th class="pb-2 pr-4 font-medium">Valor</th>
                                <th class="pb-2 pr-4 font-medium">Vencimento</th>
                                <th class="pb-2 font-medium">Status</th>
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
                                <td class="py-3">
                                    <Badge variant="outline">
                                        {{ charge.status ?? '—' }}
                                    </Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
