<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import DatePickerField from '@/components/DatePickerField.vue';
import FormSelect from '@/components/FormSelect.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { dashboard } from '@/routes';
import { create, index, store, update } from '@/routes/admin/contracts';

const props = defineProps<{
    contract?: any | null;
    properties: any[];
    tenants: any[];
    receivers: any[];
    templates: any[];
}>();

const isEditing = computed(() => !!props.contract?.id);

const form = computed(() =>
    isEditing.value ? update.form(props.contract) : store.form(),
);

const propertyOptions = computed(() =>
    props.properties.map((property) => ({
        value: property.id,
        label: property.name,
    })),
);

const tenantOptions = computed(() =>
    props.tenants.map((tenant) => ({
        value: tenant.id,
        label: tenant.name,
    })),
);

const receiverOptions = computed(() =>
    props.receivers.map((receiver) => ({
        value: receiver.id,
        label: receiver.name,
    })),
);

const templateOptions = computed(() =>
    props.templates.map((template) => ({
        value: template.id,
        label: template.name,
    })),
);

const selectedWitnessIds = computed(() =>
    (props.contract?.witnesses ?? []).map((witness: any) => witness.receiver_id),
);

const finePercent = computed(() =>
    props.contract?.fine_rate != null
        ? Number(props.contract.fine_rate) * 100
        : 2,
);

const interestPercent = computed(() =>
    props.contract?.monthly_interest_rate != null
        ? Number(props.contract.monthly_interest_rate) * 100
        : 1,
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Contratos', href: index() },
            { title: 'Formulário', href: create() },
        ],
    },
});
</script>

<template>
    <Head :title="isEditing ? 'Editar contrato' : 'Novo contrato'" />

    <div class="flex flex-col gap-8">
        <Heading
            :title="isEditing ? 'Editar contrato' : 'Novo contrato'"
            description="Preencha os dados do contrato de locação"
        />

        <Form
            v-bind="form"
            class="max-w-2xl space-y-6"
            #default="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="property_id">Imóvel</Label>
                <FormSelect
                    id="property_id"
                    name="property_id"
                    :options="propertyOptions"
                    :default-value="contract?.property_id"
                    placeholder="Selecione um imóvel"
                    required
                />
                <InputError :message="errors.property_id" />
            </div>

            <div class="grid gap-2">
                <Label for="tenant_id">Inquilino</Label>
                <FormSelect
                    id="tenant_id"
                    name="tenant_id"
                    :options="tenantOptions"
                    :default-value="contract?.tenant_id"
                    placeholder="Selecione um inquilino"
                    required
                />
                <InputError :message="errors.tenant_id" />
            </div>

            <div class="grid gap-2">
                <Label for="receiver_id">Recebedor</Label>
                <FormSelect
                    id="receiver_id"
                    name="receiver_id"
                    :options="receiverOptions"
                    :default-value="contract?.receiver_id"
                    placeholder="Selecione um recebedor"
                    required
                />
                <InputError :message="errors.receiver_id" />
            </div>

            <div class="grid gap-2">
                <Label for="template_id">Modelo</Label>
                <FormSelect
                    id="template_id"
                    name="template_id"
                    :options="templateOptions"
                    :default-value="contract?.template_id"
                    placeholder="Selecione um modelo"
                />
                <InputError :message="errors.template_id" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="monthly_rent">Valor do aluguel</Label>
                    <Input
                        id="monthly_rent"
                        type="number"
                        step="0.01"
                        min="0"
                        name="monthly_rent"
                        :default-value="contract?.monthly_rent"
                        required
                        placeholder="0,00"
                    />
                    <InputError :message="errors.monthly_rent" />
                </div>

                <div class="grid gap-2">
                    <Label for="due_day">Dia de vencimento</Label>
                    <Input
                        id="due_day"
                        type="number"
                        min="1"
                        max="31"
                        name="due_day"
                        :default-value="contract?.due_day ?? 10"
                        required
                    />
                    <InputError :message="errors.due_day" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="starts_at">Data de início</Label>
                    <DatePickerField
                        id="starts_at"
                        name="starts_at"
                        :default-value="contract?.starts_at"
                        required
                        placeholder="Selecione a data de início"
                    />
                    <InputError :message="errors.starts_at" />
                </div>

                <div class="grid gap-2">
                    <Label for="ends_at">Data de término</Label>
                    <DatePickerField
                        id="ends_at"
                        name="ends_at"
                        :default-value="contract?.ends_at"
                        required
                        placeholder="Selecione a data de término"
                    />
                    <InputError :message="errors.ends_at" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="fine_percent">Multa (%)</Label>
                    <Input
                        id="fine_percent"
                        type="number"
                        step="0.01"
                        min="0"
                        name="fine_percent"
                        :default-value="finePercent"
                    />
                    <InputError :message="errors.fine_percent || errors.fine_rate" />
                </div>

                <div class="grid gap-2">
                    <Label for="interest_percent">Juros mensais (%)</Label>
                    <Input
                        id="interest_percent"
                        type="number"
                        step="0.01"
                        min="0"
                        name="interest_percent"
                        :default-value="interestPercent"
                    />
                    <InputError
                        :message="errors.interest_percent || errors.monthly_interest_rate"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="grace_days">Carência (dias)</Label>
                    <Input
                        id="grace_days"
                        type="number"
                        min="0"
                        name="grace_days"
                        :default-value="contract?.grace_days ?? 0"
                    />
                    <InputError :message="errors.grace_days" />
                </div>
            </div>

            <div class="grid gap-3">
                <Label>Testemunhas (recebedores)</Label>
                <div class="space-y-2 rounded-lg border border-border/80 p-3">
                    <Label
                        v-for="receiver in receivers"
                        :key="receiver.id"
                        :for="`witness-${receiver.id}`"
                        class="flex cursor-pointer items-center gap-2 font-normal"
                    >
                                <Checkbox
                                    :id="`witness-${receiver.id}`"
                                    name="witness_receiver_ids[]"
                                    :value="String(receiver.id)"
                                    :default-value="selectedWitnessIds.includes(receiver.id)"
                                />
                        {{ receiver.name }}
                    </Label>
                    <p
                        v-if="receivers.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Cadastre recebedores para usá-los como testemunhas.
                    </p>
                </div>
                <InputError :message="errors.witness_receiver_ids" />
            </div>

            <div class="grid gap-3">
                <Label>Status</Label>
                <RadioGroup
                    name="status"
                    :default-value="contract?.status ?? 'draft'"
                    class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <Label
                        for="status-draft"
                        class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/80 bg-card px-3 py-2.5 font-normal hover:bg-accent/50"
                    >
                        <RadioGroupItem id="status-draft" value="draft" />
                        Rascunho
                    </Label>
                    <Label
                        for="status-active"
                        class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/80 bg-card px-3 py-2.5 font-normal hover:bg-accent/50"
                    >
                        <RadioGroupItem id="status-active" value="active" />
                        Ativo
                    </Label>
                    <Label
                        for="status-expiring"
                        class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/80 bg-card px-3 py-2.5 font-normal hover:bg-accent/50"
                    >
                        <RadioGroupItem id="status-expiring" value="expiring" />
                        Vence em breve
                    </Label>
                    <Label
                        for="status-closed"
                        class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/80 bg-card px-3 py-2.5 font-normal hover:bg-accent/50"
                    >
                        <RadioGroupItem id="status-closed" value="closed" />
                        Encerrado
                    </Label>
                    <Label
                        for="status-cancelled"
                        class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/80 bg-card px-3 py-2.5 font-normal hover:bg-accent/50"
                    >
                        <RadioGroupItem id="status-cancelled" value="cancelled" />
                        Cancelado
                    </Label>
                </RadioGroup>
                <InputError :message="errors.status" />
            </div>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="processing">
                    {{ isEditing ? 'Salvar' : 'Cadastrar' }}
                </Button>
            </div>
        </Form>
    </div>
</template>
