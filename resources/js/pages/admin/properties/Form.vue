<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import FormSelect from '@/components/FormSelect.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { dashboard } from '@/routes';
import { create, index, store, update } from '@/routes/admin/properties';

const props = defineProps<{
    property?: any | null;
    owners: any[];
    types: { value: string; label: string }[];
}>();

const isEditing = computed(() => !!props.property?.id);

const form = computed(() =>
    isEditing.value ? update.form(props.property) : store.form(),
);

const selectedOwnerIds = computed(() =>
    (props.property?.owners ?? []).map((owner: any) => owner.id),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Imóveis', href: index() },
            { title: 'Formulário', href: create() },
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
            v-bind="form"
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
                <FormSelect
                    id="type"
                    name="type"
                    :options="types"
                    :default-value="property?.type ?? 'apartment'"
                    placeholder="Selecione o tipo"
                    required
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
                <Label>Proprietários</Label>
                <div class="space-y-2 rounded-lg border border-border/80 p-3">
                    <Label
                        v-for="owner in owners"
                        :key="owner.id"
                        :for="`owner-${owner.id}`"
                        class="flex cursor-pointer items-center gap-2 font-normal"
                    >
                        <Checkbox
                            :id="`owner-${owner.id}`"
                            name="owner_ids[]"
                            :value="String(owner.id)"
                            :default-value="selectedOwnerIds.includes(owner.id)"
                        />
                        {{ owner.name }}
                    </Label>
                    <p
                        v-if="owners.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Cadastre proprietários para vinculá-los a este imóvel.
                    </p>
                </div>
                <InputError :message="errors.owner_ids" />
            </div>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="processing">
                    {{ isEditing ? 'Salvar' : 'Cadastrar' }}
                </Button>
            </div>
        </Form>
    </div>
</template>
