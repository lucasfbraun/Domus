<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import TableActionButton from '@/components/TableActionButton.vue';
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
import { create, destroy, edit, index } from '@/routes/admin/owners';

defineProps<{
    owners: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Proprietários', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="Proprietários" />

    <div class="flex flex-col gap-8">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                title="Proprietários"
                description="Gerencie os proprietários de imóveis"
            />
            <Button as-child class="shrink-0">
                <Link :href="create()">Novo proprietário</Link>
            </Button>
        </div>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Lista de proprietários</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="owners.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhum proprietário cadastrado.
                </div>
                <DataTable v-else>
                    <thead>
                        <DataTableRow variant="header">
                            <DataTableHeadCell>Nome</DataTableHeadCell>
                            <DataTableHeadCell>Documento</DataTableHeadCell>
                            <DataTableHeadCell>E-mail</DataTableHeadCell>
                            <DataTableHeadCell>Telefone</DataTableHeadCell>
                            <DataTableActionsHeader />
                        </DataTableRow>
                    </thead>
                    <tbody>
                        <DataTableRow v-for="owner in owners" :key="owner.id">
                            <DataTableCell>{{ owner.name }}</DataTableCell>
                            <DataTableCell>
                                {{
                                    owner.document
                                        ? formatCpfCnpj(owner.document)
                                        : '—'
                                }}
                            </DataTableCell>
                            <DataTableCell>
                                {{ owner.email ?? '—' }}
                            </DataTableCell>
                            <DataTableCell>
                                {{
                                    owner.phone
                                        ? formatPhone(owner.phone)
                                        : '—'
                                }}
                            </DataTableCell>
                            <DataTableActionsCell>
                                <TableActionButton label="Editar" as-child>
                                    <Link :href="edit(owner)">
                                        <Pencil />
                                        <span class="sr-only">Editar</span>
                                    </Link>
                                </TableActionButton>
                                <Form
                                    v-bind="destroy.form(owner)"
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
