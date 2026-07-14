<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import AppPagination from '@/components/AppPagination.vue';
import Heading from '@/components/Heading.vue';
import TableActionButton from '@/components/TableActionButton.vue';
import { Badge } from '@/components/ui/badge';
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
import { formatCpfCnpj } from '@/lib/brazilian-masks';
import { dashboard } from '@/routes';
import { create, destroy, edit, index } from '@/routes/admin/receivers';
import type { Paginated } from '@/types';

defineProps<{
    receivers: Paginated<any>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Recebedores', href: index() },
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
                <Link :href="create()">Novo recebedor</Link>
            </Button>
        </div>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Lista de recebedores</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="receivers.data.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhum recebedor cadastrado.
                </div>
                <template v-else>
                    <DataTable>
                        <thead>
                            <DataTableRow variant="header">
                                <DataTableHeadCell>Nome</DataTableHeadCell>
                                <DataTableHeadCell>Documento</DataTableHeadCell>
                                <DataTableHeadCell>E-mail</DataTableHeadCell>
                                <DataTableHeadCell>Mercado Pago</DataTableHeadCell>
                                <DataTableHeadCell>Ativo</DataTableHeadCell>
                                <DataTableActionsHeader />
                            </DataTableRow>
                        </thead>
                        <tbody>
                            <DataTableRow
                                v-for="receiver in receivers.data"
                                :key="receiver.id"
                            >
                                <DataTableCell>{{
                                    receiver.name
                                }}</DataTableCell>
                                <DataTableCell>
                                    {{
                                        receiver.document
                                            ? formatCpfCnpj(receiver.document)
                                            : '—'
                                    }}
                                </DataTableCell>
                                <DataTableCell>
                                    {{ receiver.email ?? '—' }}
                                </DataTableCell>
                                <DataTableCell>
                                    <div class="flex flex-wrap gap-1">
                                        <Badge
                                            :variant="
                                                receiver.mp_connected
                                                    ? 'default'
                                                    : 'outline'
                                            "
                                        >
                                            {{
                                                receiver.mp_connected
                                                    ? 'Conectado'
                                                    : 'Desconectado'
                                            }}
                                        </Badge>
                                        <Badge
                                            v-if="receiver.mp_connected"
                                            variant="secondary"
                                        >
                                            {{
                                                receiver.mp_live_mode
                                                    ? 'Produção'
                                                    : 'Teste'
                                            }}
                                        </Badge>
                                    </div>
                                </DataTableCell>
                                <DataTableCell>
                                    <Badge
                                        :variant="
                                            receiver.active
                                                ? 'default'
                                                : 'outline'
                                        "
                                    >
                                        {{ receiver.active ? 'Sim' : 'Não' }}
                                    </Badge>
                                </DataTableCell>
                                <DataTableActionsCell>
                                    <TableActionButton label="Editar" as-child>
                                        <Link :href="edit(receiver)">
                                            <Pencil />
                                            <span class="sr-only">Editar</span>
                                        </Link>
                                    </TableActionButton>
                                    <Form
                                        v-bind="destroy.form(receiver)"
                                        #default="{ processing }"
                                    >
                                        <TableActionButton
                                            label="Excluir"
                                            :icon="Trash2"
                                            type="submit"
                                            variant="destructive"
                                            :disabled="processing"
                                        />
                                    </Form>
                                </DataTableActionsCell>
                            </DataTableRow>
                        </tbody>
                    </DataTable>
                    <AppPagination :paginator="receivers" />
                </template>
            </CardContent>
        </Card>
    </div>
</template>
