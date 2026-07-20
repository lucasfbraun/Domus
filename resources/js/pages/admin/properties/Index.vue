<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import TableActionButton from '@/components/TableActionButton.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import AppPagination from '@/components/AppPagination.vue';
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
import { create, destroy, edit, index } from '@/routes/admin/properties';
import type { Paginated } from '@/types';

defineProps<{
    properties: Paginated<any>;
}>();

function ownerNames(property: any): string {
    return property.owners?.length
        ? property.owners.map((owner: any) => owner.name).join(', ')
        : '—';
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Imóveis', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="Imóveis" />

    <div class="flex flex-col gap-8">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Imóveis"
                description="Gerencie os imóveis cadastrados"
            />
            <Button as-child>
                <Link :href="create()">Novo imóvel</Link>
            </Button>
        </div>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Lista de imóveis</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="properties.data.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhum imóvel cadastrado.
                </div>
                <template v-else>
                    <DataTable>
                        <thead>
                            <DataTableRow variant="header">
                                <DataTableHeadCell>Nome</DataTableHeadCell>
                                <DataTableHeadCell>Endereço</DataTableHeadCell>
                                <DataTableHeadCell>Tipo</DataTableHeadCell>
                                <DataTableHeadCell>Status</DataTableHeadCell>
                                <DataTableHeadCell
                                    >Proprietário</DataTableHeadCell
                                >
                                <DataTableActionsHeader />
                            </DataTableRow>
                        </thead>
                        <tbody>
                            <DataTableRow
                                v-for="property in properties.data"
                                :key="property.id"
                            >
                                <DataTableCell>{{
                                    property.name
                                }}</DataTableCell>
                                <DataTableCell>
                                    {{ property.address ?? '—' }}
                                </DataTableCell>
                                <DataTableCell>
                                    {{ property.type ?? '—' }}
                                </DataTableCell>
                                <DataTableCell>
                                    <StatusBadge
                                        type="property"
                                        :status="property.status"
                                    />
                                </DataTableCell>
                                <DataTableCell>
                                    {{ ownerNames(property) }}
                                </DataTableCell>
                                <DataTableActionsCell>
                                    <TableActionButton label="Editar" as-child>
                                        <Link :href="edit(property)">
                                            <Pencil />
                                            <span class="sr-only">Editar</span>
                                        </Link>
                                    </TableActionButton>
                                    <Form
                                        v-bind="destroy.form(property)"
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
                    <AppPagination :paginator="properties" />
                </template>
            </CardContent>
        </Card>
    </div>
</template>
