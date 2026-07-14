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
import { formatCpfCnpj, formatPhone } from '@/lib/brazilian-masks';

defineProps<{
    tenants: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: '/dashboard' },
            { title: 'Inquilinos', href: '/tenants' },
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
                <Link href="/tenants/create">Novo inquilino</Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Lista de inquilinos</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="tenants.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhum inquilino cadastrado.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Nome</th>
                                <th class="pb-2 pr-4 font-medium">Documento</th>
                                <th class="pb-2 pr-4 font-medium">E-mail</th>
                                <th class="pb-2 pr-4 font-medium">WhatsApp</th>
                                <th class="pb-2 pr-4 font-medium">Status</th>
                                <th class="pb-2 pr-4 font-medium">Moradores</th>
                                <th class="pb-2 font-medium">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="tenant in tenants"
                                :key="tenant.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">{{ tenant.name }}</td>
                                <td class="py-3 pr-4">
                                    {{
                                        tenant.document
                                            ? formatCpfCnpj(tenant.document)
                                            : '—'
                                    }}
                                </td>
                                <td class="py-3 pr-4">{{ tenant.email ?? '—' }}</td>
                                <td class="py-3 pr-4">
                                    {{
                                        tenant.whatsapp
                                            ? formatPhone(tenant.whatsapp)
                                            : '—'
                                    }}
                                </td>
                                <td class="py-3 pr-4">
                                    <Badge variant="outline">
                                        {{ tenant.status ?? '—' }}
                                    </Badge>
                                </td>
                                <td class="py-3 pr-4">
                                    {{ tenant.resident_count ?? '—' }}
                                </td>
                                <td class="py-3">
                                    <div class="flex items-center gap-2">
                                        <Button as-child size="sm" variant="outline">
                                            <Link :href="`/tenants/${tenant.id}/edit`">
                                                Editar
                                            </Link>
                                        </Button>
                                        <Form
                                            :action="`/tenants/${tenant.id}`"
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
