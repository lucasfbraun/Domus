<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import BrazilianMaskedInput from '@/components/BrazilianMaskedInput.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { dashboard } from '@/routes';
import { create, index, store, update } from '@/routes/admin/tenants';

const props = defineProps<{
    tenant?: {
        id: number;
        name?: string;
        document?: string;
        email?: string;
        whatsapp?: string;
        status?: string;
        resident_count?: number;
        user?: { id: number; email: string } | null;
    } | null;
}>();

const isEditing = computed(() => !!props.tenant?.id);

const form = computed(() =>
    isEditing.value ? update.form(props.tenant) : store.form(),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Inquilinos', href: index() },
            { title: 'Formulário', href: create() },
        ],
    },
});
</script>

<template>
    <Head :title="isEditing ? 'Editar inquilino' : 'Novo inquilino'" />

    <div class="flex flex-col gap-8">
        <Heading
            :title="isEditing ? 'Editar inquilino' : 'Novo inquilino'"
            description="Preencha os dados do inquilino"
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
                    :default-value="tenant?.name"
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
                    :default-value="tenant?.document"
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
                    :default-value="tenant?.email"
                    placeholder="email@exemplo.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="whatsapp">WhatsApp</Label>
                <BrazilianMaskedInput
                    id="whatsapp"
                    name="whatsapp"
                    mask="phone"
                    :default-value="tenant?.whatsapp"
                    placeholder="(00) 00000-0000"
                />
                <InputError :message="errors.whatsapp" />
            </div>

            <div class="grid gap-3">
                <Label>Status</Label>
                <RadioGroup
                    name="status"
                    :default-value="tenant?.status ?? 'active'"
                    class="grid gap-3 sm:grid-cols-2"
                >
                    <Label
                        for="status-active"
                        class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/80 bg-card px-3 py-2.5 font-normal hover:bg-accent/50"
                    >
                        <RadioGroupItem id="status-active" value="active" />
                        Ativo
                    </Label>
                    <Label
                        for="status-inactive"
                        class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/80 bg-card px-3 py-2.5 font-normal hover:bg-accent/50"
                    >
                        <RadioGroupItem id="status-inactive" value="inactive" />
                        Inativo
                    </Label>
                </RadioGroup>
                <InputError :message="errors.status" />
            </div>

            <div class="grid gap-2">
                <Label for="resident_count">Quantidade de moradores</Label>
                <Input
                    id="resident_count"
                    type="number"
                    name="resident_count"
                    min="1"
                    :default-value="tenant?.resident_count"
                    placeholder="1"
                />
                <InputError :message="errors.resident_count" />
            </div>

            <div
                class="space-y-4 rounded-xl border border-border/80 bg-muted/30 p-5"
            >
                <div v-if="!isEditing" class="flex items-center gap-2">
                    <Checkbox id="create_portal" name="create_portal" value="1" />
                    <Label for="create_portal">Criar acesso ao portal</Label>
                </div>

                <div
                    v-if="isEditing && tenant?.user"
                    class="rounded-lg border border-dashed border-border/80 bg-background px-3 py-2 text-sm text-muted-foreground"
                >
                    Login atual:
                    <span class="font-medium text-foreground">{{
                        tenant.user.email
                    }}</span>
                </div>

                <div class="grid gap-2">
                    <Label for="password">
                        {{
                            isEditing
                                ? 'Alterar senha do portal'
                                : 'Senha do portal'
                        }}
                    </Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        :placeholder="
                            isEditing
                                ? 'Deixe em branco para manter a senha atual'
                                : 'Senha para acesso ao portal'
                        "
                    />
                    <p
                        v-if="isEditing && !tenant?.user"
                        class="text-sm text-muted-foreground"
                    >
                        Este inquilino ainda não tem acesso ao portal.
                        Preencha para criar um login.
                    </p>
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirmar senha</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        placeholder="Confirmar senha do portal"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox
                        id="force_password_change"
                        name="force_password_change"
                        value="1"
                    />
                    <Label for="force_password_change">
                        Exigir troca de senha no próximo login
                    </Label>
                </div>
                <p class="text-sm text-muted-foreground">
                    Só tem efeito se uma senha for definida acima — o
                    inquilino é obrigado a trocá-la antes de acessar
                    qualquer outra tela do portal.
                </p>
                <InputError :message="errors.force_password_change" />
            </div>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="processing">
                    {{ isEditing ? 'Salvar' : 'Cadastrar' }}
                </Button>
            </div>
        </Form>
    </div>
</template>
