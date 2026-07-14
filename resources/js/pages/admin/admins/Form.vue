<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { computed } from 'vue';
import { dashboard } from '@/routes';
import { create, index, store, update } from '@/routes/admin/admins';

const props = defineProps<{
    admin?: any | null;
}>();

const isEditing = computed(() => !!props.admin?.id);

const form = computed(() =>
    isEditing.value ? update.form(props.admin) : store.form(),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Administradores', href: index() },
            { title: 'Formulário', href: create() },
        ],
    },
});
</script>

<template>
    <Head :title="isEditing ? 'Editar admin' : 'Novo admin'" />

    <div class="flex flex-col gap-8">
        <Heading
            :title="isEditing ? 'Editar admin' : 'Novo admin'"
            description="Preencha os dados do administrador"
        />

        <Form
            v-bind="form"
            class="max-w-xl space-y-6"
            #default="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Nome</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="admin?.name"
                    required
                    placeholder="Nome completo"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">E-mail</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    :default-value="admin?.email"
                    required
                    placeholder="email@exemplo.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">
                    {{ isEditing ? 'Nova senha (opcional)' : 'Senha' }}
                </Label>
                <Input
                    id="password"
                    type="password"
                    name="password"
                    :required="!isEditing"
                    placeholder="Senha de acesso"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="processing">
                    {{ isEditing ? 'Salvar' : 'Cadastrar' }}
                </Button>
            </div>
        </Form>
    </div>
</template>
