<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatCpfCnpj, formatPhone } from '@/lib/brazilian-masks';

defineProps<{
    owners: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: '/dashboard' },
            { title: 'Proprietários', href: '/owners' },
        ],
    },
});
</script>

<template>
    <Head title="Proprietários" />

    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <Heading
                title="Proprietários"
                description="Gerencie os proprietários de imóveis"
            />
            <Button as-child class="shrink-0">
                <Link href="/owners/create">Novo proprietário</Link>
            </Button>
        </div>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Lista de proprietários</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="owners.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhum proprietário cadastrado.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Nome</th>
                                <th class="pb-2 pr-4 font-medium">Documento</th>
                                <th class="pb-2 pr-4 font-medium">E-mail</th>
                                <th class="pb-2 pr-4 font-medium">Telefone</th>
                                <th class="pb-2 font-medium">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="owner in owners"
                                :key="owner.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">{{ owner.name }}</td>
                                <td class="py-3 pr-4">
                                    {{
                                        owner.document
                                            ? formatCpfCnpj(owner.document)
                                            : '—'
                                    }}
                                </td>
                                <td class="py-3 pr-4">{{ owner.email ?? '—' }}</td>
                                <td class="py-3 pr-4">
                                    {{
                                        owner.phone
                                            ? formatPhone(owner.phone)
                                            : '—'
                                    }}
                                </td>
                                <td class="py-3">
                                    <div class="flex items-center gap-2">
                                        <Button as-child size="sm" variant="outline">
                                            <Link :href="`/owners/${owner.id}/edit`">
                                                Editar
                                            </Link>
                                        </Button>
                                        <Form
                                            :action="`/owners/${owner.id}`"
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
