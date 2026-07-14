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
import { formatCpfCnpj } from '@/lib/brazilian-masks';

defineProps<{
    receivers: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: '/dashboard' },
            { title: 'Recebedores', href: '/receivers' },
        ],
    },
});
</script>

<template>
    <Head title="Recebedores" />

    <div class="flex flex-col gap-8">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Recebedores"
                description="Gerencie os recebedores de pagamentos"
            />
            <Button as-child>
                <Link href="/receivers/create">Novo recebedor</Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Lista de recebedores</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="receivers.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhum recebedor cadastrado.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Nome</th>
                                <th class="pb-2 pr-4 font-medium">Documento</th>
                                <th class="pb-2 pr-4 font-medium">E-mail</th>
                                <th class="pb-2 pr-4 font-medium">Ativo</th>
                                <th class="pb-2 font-medium">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="receiver in receivers"
                                :key="receiver.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">{{ receiver.name }}</td>
                                <td class="py-3 pr-4">
                                    {{
                                        receiver.document
                                            ? formatCpfCnpj(receiver.document)
                                            : '—'
                                    }}
                                </td>
                                <td class="py-3 pr-4">{{ receiver.email ?? '—' }}</td>
                                <td class="py-3 pr-4">
                                    <Badge
                                        :variant="
                                            receiver.active ? 'default' : 'outline'
                                        "
                                    >
                                        {{ receiver.active ? 'Sim' : 'Não' }}
                                    </Badge>
                                </td>
                                <td class="py-3">
                                    <div class="flex items-center gap-2">
                                        <Button as-child size="sm" variant="outline">
                                            <Link
                                                :href="`/receivers/${receiver.id}/edit`"
                                            >
                                                Editar
                                            </Link>
                                        </Button>
                                        <Form
                                            :action="`/receivers/${receiver.id}`"
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
