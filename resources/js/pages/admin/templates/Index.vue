<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import AppPagination from '@/components/AppPagination.vue';
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
import { dashboard } from '@/routes';
import { create, destroy, edit, index } from '@/routes/admin/templates';
import type { Paginated } from '@/types';

defineProps<{
    templates: Paginated<any>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Modelos', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="Modelos" />

    <div class="flex flex-col gap-8">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Modelos"
                description="Gerencie os modelos de contrato"
            />
            <Button as-child>
                <Link :href="create()">Novo modelo</Link>
            </Button>
        </div>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Lista de modelos</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="templates.data.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhum modelo cadastrado.
                </div>
                <template v-else>
                    <DataTable>
                        <thead>
                            <DataTableRow variant="header">
                                <DataTableHeadCell>Nome</DataTableHeadCell>
                                <DataTableActionsHeader />
                            </DataTableRow>
                        </thead>
                        <tbody>
                            <DataTableRow
                                v-for="template in templates.data"
                                :key="template.id"
                            >
                                <DataTableCell>{{
                                    template.name
                                }}</DataTableCell>
                                <DataTableActionsCell>
                                    <TableActionButton label="Editar" as-child>
                                        <Link :href="edit(template)">
                                            <Pencil />
                                            <span class="sr-only">Editar</span>
                                        </Link>
                                    </TableActionButton>
                                    <Form
                                        v-bind="destroy.form(template)"
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
                    <AppPagination :paginator="templates" />
                </template>
            </CardContent>
        </Card>
    </div>
</template>
