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
import { create, destroy, edit, index } from '@/routes/admin/admins';
import type { Paginated } from '@/types';

defineProps<{
    admins: Paginated<any>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Administradores', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="Administradores" />

    <div class="flex flex-col gap-8">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Administradores"
                description="Gerencie os usuários administradores"
            />
            <Button as-child>
                <Link :href="create()">Novo admin</Link>
            </Button>
        </div>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Lista de administradores</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="admins.data.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhum administrador cadastrado.
                </div>
                <template v-else>
                    <DataTable>
                        <thead>
                            <DataTableRow variant="header">
                                <DataTableHeadCell>Nome</DataTableHeadCell>
                                <DataTableHeadCell>E-mail</DataTableHeadCell>
                                <DataTableActionsHeader />
                            </DataTableRow>
                        </thead>
                        <tbody>
                            <DataTableRow
                                v-for="admin in admins.data"
                                :key="admin.id"
                            >
                                <DataTableCell>{{ admin.name }}</DataTableCell>
                                <DataTableCell>{{
                                    admin.email
                                }}</DataTableCell>
                                <DataTableActionsCell>
                                    <TableActionButton label="Editar" as-child>
                                        <Link :href="edit(admin)">
                                            <Pencil />
                                            <span class="sr-only">Editar</span>
                                        </Link>
                                    </TableActionButton>
                                    <Form
                                        v-bind="destroy.form(admin)"
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
                    <AppPagination :paginator="admins" />
                </template>
            </CardContent>
        </Card>
    </div>
</template>
