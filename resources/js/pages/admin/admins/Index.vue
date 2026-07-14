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
    admins: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: '/dashboard' },
            { title: 'Administradores', href: '/admins' },
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
                <Link href="/admins/create">Novo admin</Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Lista de administradores</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="admins.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhum administrador cadastrado.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Nome</th>
                                <th class="pb-2 pr-4 font-medium">E-mail</th>
                                <th class="pb-2 font-medium">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="admin in admins"
                                :key="admin.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">{{ admin.name }}</td>
                                <td class="py-3 pr-4">{{ admin.email }}</td>
                                <td class="py-3">
                                    <div class="flex items-center gap-2">
                                        <Button as-child size="sm" variant="outline">
                                            <Link :href="`/admins/${admin.id}/edit`">
                                                Editar
                                            </Link>
                                        </Button>
                                        <Form
                                            :action="`/admins/${admin.id}`"
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
