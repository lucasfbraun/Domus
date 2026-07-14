<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes';
import { create, index, store, update } from '@/routes/admin/templates';

const props = defineProps<{
    template?: any | null;
}>();

const isEditing = computed(() => !!props.template?.id);

const form = computed(() =>
    isEditing.value ? update.form(props.template) : store.form(),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Modelos', href: index() },
            { title: 'Formulário', href: create() },
        ],
    },
});
</script>

<template>
    <Head :title="isEditing ? 'Editar modelo' : 'Novo modelo'" />

    <div class="flex flex-col gap-8">
        <Heading
            :title="isEditing ? 'Editar modelo' : 'Novo modelo'"
            description="Defina o conteúdo do modelo de contrato"
        />

        <Form
            v-bind="form"
            class="max-w-3xl space-y-6"
            #default="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Nome</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="template?.name"
                    required
                    placeholder="Nome do modelo"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="content">Conteúdo</Label>
                <Textarea
                    id="content"
                    name="content"
                    rows="16"
                    class="min-h-64"
                    :default-value="template?.content"
                    placeholder="Conteúdo do contrato com variáveis..."
                />
                <InputError :message="errors.content" />
            </div>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="processing">
                    {{ isEditing ? 'Salvar' : 'Cadastrar' }}
                </Button>
            </div>
        </Form>
    </div>
</template>
