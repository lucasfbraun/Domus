<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
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
import { formatCpfCnpj, formatPhone } from '@/lib/brazilian-masks';
import { dashboard } from '@/routes';
import { create, destroy, edit, index } from '@/routes/admin/tenants';

defineProps<{
    tenants: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Inquilinos', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="Inquilinos" />

    <div class="flex flex-col gap-8">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Inquilinos"
                description="Gerencie os inquilinos cadastrados"
            />
            <Button as-child>
                <Link :href="create()">Novo inquilino</Link>
            </Button>
        </div>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Lista de inquilinos</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="tenants.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhum inquilino cadastrado.
                </div>
                <DataTable v-else>
                    <thead>
                        <DataTableRow variant="header">
                            <DataTableHeadCell>Nome</DataTableHeadCell>
                            <DataTableHeadCell>Documento</DataTableHeadCell>
                            <DataTableHeadCell>E-mail</DataTableHeadCell>
                            <DataTableHeadCell>WhatsApp</DataTableHeadCell>
                            <DataTableHeadCell>Status</DataTableHeadCell>
                            <DataTableHeadCell>Moradores</DataTableHeadCell>
                            <DataTableActionsHeader />
                        </DataTableRow>
                    </thead>
                    <tbody>
                        <DataTableRow
                            v-for="tenant in tenants"
                            :key="tenant.id"
                        >
                            <DataTableCell>{{ tenant.name }}</DataTableCell>
                            <DataTableCell>
                                {{
                                    tenant.document
                                        ? formatCpfCnpj(tenant.document)
                                        : '—'
                                }}
                            </DataTableCell>
                            <DataTableCell>
                                {{ tenant.email ?? '—' }}
                            </DataTableCell>
                            <DataTableCell>
                                {{
                                    tenant.whatsapp
                                        ? formatPhone(tenant.whatsapp)
                                        : '—'
                                }}
                            </DataTableCell>
                            <DataTableCell>
                                <StatusBadge
                                    type="tenant"
                                    :status="tenant.status"
                                />
                            </DataTableCell>
                            <DataTableCell class="tabular-nums">
                                {{ tenant.resident_count ?? '—' }}
                            </DataTableCell>
                            <DataTableActionsCell>
                                <TableActionButton label="Editar" as-child>
                                    <Link :href="edit(tenant)">
                                        <Pencil />
                                        <span class="sr-only">Editar</span>
                                    </Link>
                                </TableActionButton>
                                <Form
                                    v-bind="destroy.form(tenant)"
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
            </CardContent>
        </Card>
    </div>
</template>
