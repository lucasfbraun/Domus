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
    properties: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: '/dashboard' },
            { title: 'Imóveis', href: '/properties' },
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
                <Link href="/properties/create">Novo imóvel</Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Lista de imóveis</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="properties.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhum imóvel cadastrado.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Nome</th>
                                <th class="pb-2 pr-4 font-medium">Endereço</th>
                                <th class="pb-2 pr-4 font-medium">Tipo</th>
                                <th class="pb-2 pr-4 font-medium">Status</th>
                                <th class="pb-2 pr-4 font-medium">Proprietário</th>
                                <th class="pb-2 font-medium">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="property in properties"
                                :key="property.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">{{ property.name }}</td>
                                <td class="py-3 pr-4">{{ property.address ?? '—' }}</td>
                                <td class="py-3 pr-4">{{ property.type ?? '—' }}</td>
                                <td class="py-3 pr-4">
                                    <Badge variant="outline">
                                        {{ property.status ?? '—' }}
                                    </Badge>
                                </td>
                                <td class="py-3 pr-4">
                                    {{ property.owner?.name ?? '—' }}
                                </td>
                                <td class="py-3">
                                    <div class="flex items-center gap-2">
                                        <Button as-child size="sm" variant="outline">
                                            <Link
                                                :href="`/properties/${property.id}/edit`"
                                            >
                                                Editar
                                            </Link>
                                        </Button>
                                        <Form
                                            :action="`/properties/${property.id}`"
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
