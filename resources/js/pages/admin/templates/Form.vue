<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import type { TemplateVariableItem } from '@/components/template-editor/prepareTemplateContent';
import TemplateEditor from '@/components/template-editor/TemplateEditor.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { create, index, store, update } from '@/routes/admin/templates';

const props = defineProps<{
    template?: { id: number; name: string; content: string } | null;
    variables: TemplateVariableItem[];
}>();

const isEditing = computed(() => !!props.template?.id);

const form = computed(() =>
    isEditing.value ? update.form(props.template!) : store.form(),
);

const content = ref(props.template?.content ?? '');

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
            description="Monte o contrato com formatação e variáveis arrastáveis"
        />

        <Form
            v-bind="form"
            class="space-y-6"
            #default="{ errors, processing }"
        >
            <div class="grid max-w-xl gap-2">
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
                <TemplateEditor
                    v-model="content"
                    name="content"
                    :variables="variables"
                    :invalid="!!errors.content"
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
