<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import BrazilianMaskedInput from '@/components/BrazilianMaskedInput.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { create, index, store, update } from '@/routes/admin/receivers';
import { connect as connectMercadoPago } from '@/routes/admin/receivers/mercadopago';

const props = defineProps<{
    receiver?: any | null;
}>();

const isEditing = computed(() => !!props.receiver?.id);

const form = computed(() =>
    isEditing.value ? update.form(props.receiver) : store.form(),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Recebedores', href: index() },
            { title: 'Formulário', href: create() },
        ],
    },
});
</script>

<template>
    <Head :title="isEditing ? 'Editar recebedor' : 'Novo recebedor'" />

    <div class="flex flex-col gap-8">
        <Heading
            :title="isEditing ? 'Editar recebedor' : 'Novo recebedor'"
            description="Preencha os dados do recebedor"
        />

        <div
            v-if="isEditing"
            class="rounded-lg border border-dashed p-4 text-sm"
        >
            <p class="mb-2 text-muted-foreground">
                Conecte a conta Mercado Pago para receber pagamentos.
            </p>
            <Button as-child variant="outline">
                <Link :href="connectMercadoPago(receiver)">
                    Conectar Mercado Pago
                </Link>
            </Button>
        </div>

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
                    :default-value="receiver?.name"
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
                    :default-value="receiver?.document"
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
                    :default-value="receiver?.email"
                    placeholder="email@exemplo.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="flex items-center gap-2">
                <Checkbox
                    id="active"
                    name="active"
                    value="1"
                    :default-checked="receiver?.active ?? true"
                />
                <Label for="active">Recebedor ativo</Label>
            </div>
            <InputError :message="errors.active" />

            <div
                v-if="!isEditing"
                class="space-y-4 rounded-xl border border-border/80 bg-muted/30 p-5"
            >
                <div class="flex items-center gap-2">
                    <Checkbox id="create_portal" name="create_portal" value="1" />
                    <Label for="create_portal">Criar acesso ao portal</Label>
                </div>

                <div class="grid gap-2">
                    <Label for="password">Senha do portal</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Senha para acesso ao portal"
                    />
                    <InputError :message="errors.password" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="processing">
                    {{ isEditing ? 'Salvar' : 'Cadastrar' }}
                </Button>
            </div>
        </Form>
    </div>
</template>
