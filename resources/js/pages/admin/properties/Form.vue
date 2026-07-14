<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import FormSelect from '@/components/FormSelect.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';

const props = defineProps<{
    property?: any | null;
    owners: any[];
}>();

const isEditing = computed(() => !!props.property?.id);

const formAction = computed(() =>
    isEditing.value ? `/properties/${props.property.id}` : '/properties',
);

const formMethod = computed(() => (isEditing.value ? 'put' : 'post'));

const ownerOptions = computed(() =>
    props.owners.map((owner) => ({
        value: owner.id,
        label: owner.name,
    })),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: '/dashboard' },
            { title: 'Imóveis', href: '/properties' },
            { title: 'Formulário', href: '/properties/create' },
        ],
    },
});
</script>

<template>
    <Head :title="isEditing ? 'Editar imóvel' : 'Novo imóvel'" />

    <div class="flex flex-col gap-8">
        <Heading
            :title="isEditing ? 'Editar imóvel' : 'Novo imóvel'"
            description="Preencha os dados do imóvel"
        />

        <Form
            :action="formAction"
            :method="formMethod"
            class="max-w-xl space-y-6"
            #default="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Nome</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="property?.name"
                    required
                    placeholder="Nome ou identificação do imóvel"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="address">Endereço</Label>
                <Input
                    id="address"
                    name="address"
                    :default-value="property?.address"
                    placeholder="Rua, número, bairro, cidade"
                />
                <InputError :message="errors.address" />
            </div>

            <div class="grid gap-2">
                <Label for="type">Tipo</Label>
                <Input
                    id="type"
                    name="type"
                    :default-value="property?.type"
                    placeholder="Apartamento, casa, comercial..."
                />
                <InputError :message="errors.type" />
            </div>

            <div class="grid gap-3">
                <Label>Status</Label>
                <RadioGroup
                    name="status"
                    :default-value="property?.status ?? 'available'"
                    class="grid gap-3 sm:grid-cols-3"
                >
                    <Label
                        for="status-available"
                        class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/80 bg-card px-3 py-2.5 font-normal hover:bg-accent/50"
                    >
                        <RadioGroupItem
                            id="status-available"
                            value="available"
                        />
                        Disponível
                    </Label>
                    <Label
                        for="status-rented"
                        class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/80 bg-card px-3 py-2.5 font-normal hover:bg-accent/50"
                    >
                        <RadioGroupItem id="status-rented" value="rented" />
                        Alugado
                    </Label>
                    <Label
                        for="status-maintenance"
                        class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/80 bg-card px-3 py-2.5 font-normal hover:bg-accent/50"
                    >
                        <RadioGroupItem
                            id="status-maintenance"
                            value="maintenance"
                        />
                        Em manutenção
                    </Label>
                </RadioGroup>
                <InputError :message="errors.status" />
            </div>

            <div class="grid gap-2">
                <Label for="owner_id">Proprietário</Label>
                <FormSelect
                    id="owner_id"
                    name="owner_id"
                    :options="ownerOptions"
                    :default-value="property?.owner_id"
                    placeholder="Selecione um proprietário"
                />
                <InputError :message="errors.owner_id" />
            </div>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="processing">
                    {{ isEditing ? 'Salvar' : 'Cadastrar' }}
                </Button>
            </div>
        </Form>
    </div>
</template>
