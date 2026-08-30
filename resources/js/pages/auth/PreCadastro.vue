<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import BrazilianMaskedInput from '@/components/BrazilianMaskedInput.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';
import { submit } from '@/routes/tenant-pre-registrations';

const props = defineProps<{
    status: 'fillable' | 'expired' | 'submitted' | 'approved' | 'rejected';
    name: string | null;
    token: string;
}>();

defineOptions({
    layout: {
        title: 'Pré-cadastro de inquilino',
        description: 'Preencha seus dados para iniciarmos seu cadastro',
    },
});
</script>

<template>
    <Head title="Pré-cadastro" />

    <Form
        v-if="props.status === 'fillable'"
        v-bind="submit.form(props.token)"
        :reset-on-success="false"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Nome completo</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    autofocus
                    placeholder="Nome completo"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="document">CPF</Label>
                <BrazilianMaskedInput
                    id="document"
                    name="document"
                    mask="cpf-cnpj"
                    required
                    placeholder="000.000.000-00"
                />
                <InputError :message="errors.document" />
            </div>

            <div class="grid gap-2">
                <Label for="email">E-mail</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
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
                    required
                    placeholder="(00) 00000-0000"
                />
                <InputError :message="errors.whatsapp" />
            </div>

            <div class="grid gap-2">
                <Label for="resident_count">Quantidade de moradores</Label>
                <Input
                    id="resident_count"
                    type="number"
                    name="resident_count"
                    min="1"
                    required
                    placeholder="1"
                />
                <InputError :message="errors.resident_count" />
            </div>

            <InputError :message="errors.form" />

            <Button type="submit" class="mt-2 w-full" :disabled="processing">
                Enviar
            </Button>
        </div>
    </Form>

    <div v-else class="flex flex-col gap-4 text-center">
        <p v-if="status === 'expired'" class="text-sm text-muted-foreground">
            Este link de pré-cadastro expirou. Peça para gerarem um novo link
            para você.
        </p>
        <p v-else-if="status === 'submitted'" class="text-sm text-muted-foreground">
            Recebemos seus dados{{ name ? `, ${name}` : '' }}! Seu pré-cadastro
            está em análise. Você será avisado assim que for aprovado.
        </p>
        <p v-else-if="status === 'approved'" class="text-sm text-muted-foreground">
            Seu pré-cadastro foi aprovado! Faça login no portal com o e-mail
            informado e a senha temporária que você recebeu.
        </p>
        <p v-else-if="status === 'rejected'" class="text-sm text-muted-foreground">
            Seu pré-cadastro não foi aprovado. Entre em contato com quem
            enviou este link para mais informações.
        </p>

        <TextLink v-if="status === 'approved'" :href="login()" class="text-sm">
            Ir para o login
        </TextLink>
    </div>
</template>
