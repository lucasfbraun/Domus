<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import BrazilianMaskedInput from '@/components/BrazilianMaskedInput.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { create, index, store, update } from '@/routes/admin/owners';

const props = defineProps<{
    owner?: any | null;
}>();

const isEditing = computed(() => !!props.owner?.id);

const form = computed(() =>
    isEditing.value ? update.form(props.owner) : store.form(),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Proprietários', href: index() },
            { title: 'Formulário', href: create() },
        ],
    },
});
</script>

<template>
    <Head :title="isEditing ? 'Editar proprietário' : 'Novo proprietário'" />

    <div class="flex flex-col gap-8">
        <Heading
            :title="isEditing ? 'Editar proprietário' : 'Novo proprietário'"
            description="Preencha os dados do proprietário"
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
                    :default-value="owner?.name"
                    required
                    placeholder="Nome completo"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="document">Documento</Label>
                <BrazilianMaskedInput
                    id="document"
                    name="document"
                    mask="cpf-cnpj"
                    :default-value="owner?.document"
                    placeholder="CPF ou CNPJ"
                />
                <InputError :message="errors.document" />
            </div>

            <div class="grid gap-2">
                <Label for="email">E-mail</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    :default-value="owner?.email"
                    placeholder="email@exemplo.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">Telefone</Label>
                <BrazilianMaskedInput
                    id="phone"
                    name="phone"
                    mask="phone"
                    :default-value="owner?.phone"
                    placeholder="(00) 00000-0000"
                />
                <InputError :message="errors.phone" />
            </div>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="processing">
                    {{ isEditing ? 'Salvar' : 'Cadastrar' }}
                </Button>
            </div>
        </Form>
    </div>
</template>
