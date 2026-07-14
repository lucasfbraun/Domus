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

defineProps<{
    templates: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: '/dashboard' },
            { title: 'Modelos', href: '/templates' },
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
                <Link href="/templates/create">Novo modelo</Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Lista de modelos</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="templates.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhum modelo cadastrado.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Nome</th>
                                <th class="pb-2 font-medium">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="template in templates"
                                :key="template.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">{{ template.name }}</td>
                                <td class="py-3">
                                    <div class="flex items-center gap-2">
                                        <Button as-child size="sm" variant="outline">
                                            <Link
                                                :href="`/templates/${template.id}/edit`"
                                            >
                                                Editar
                                            </Link>
                                        </Button>
                                        <Form
                                            :action="`/templates/${template.id}`"
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
