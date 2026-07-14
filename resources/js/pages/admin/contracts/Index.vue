<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
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
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: '/dashboard' },
            { title: 'Contratos', href: '/contracts' },
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
    <Head title="Contratos" />

    <div class="flex flex-col gap-8">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Contratos"
                description="Gerencie os contratos de locação"
            />
            <Button as-child>
                <Link href="/contracts/create">Novo contrato</Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Lista de contratos</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="contracts.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhum contrato cadastrado.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Imóvel</th>
                                <th class="pb-2 pr-4 font-medium">Inquilino</th>
                                <th class="pb-2 pr-4 font-medium">Recebedor</th>
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
                                    {{ contract.tenant?.name ?? '—' }}
                                </td>
                                <td class="py-3 pr-4">
                                    {{ contract.receiver?.name ?? '—' }}
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
                                    <div class="flex items-center gap-2">
                                        <Button as-child size="sm" variant="outline">
                                            <Link :href="`/contracts/${contract.id}`">
                                                Ver
                                            </Link>
                                        </Button>
                                        <Button as-child size="sm" variant="outline">
                                            <Link
                                                :href="`/contracts/${contract.id}/edit`"
                                            >
                                                Editar
                                            </Link>
                                        </Button>
                                        <Form
                                            :action="`/contracts/${contract.id}`"
                                            method="delete"
                                            #default="{ processing }"
                                        >
                                            <Button
                                                type="submit"
                                                size="sm"
                                                variant="destructive"
                                                :disabled="processing"
                                            >
                                                Excluir
                                            </Button>
                                        </Form>
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
