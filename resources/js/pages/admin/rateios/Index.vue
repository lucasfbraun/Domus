<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { FileDown, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import FormSelect from '@/components/FormSelect.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import TableActionButton from '@/components/TableActionButton.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DataTable,
    DataTableActionsCell,
    DataTableActionsHeader,
    DataTableCell,
    DataTableHeadCell,
    DataTableRow,
} from '@/components/ui/data-table';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes';
import { useMoney } from '@/composables/useMoney';
import { destroy, index, invoice, store } from '@/routes/admin/rateios';

const props = withDefaults(
    defineProps<{
        rateios?: any[];
        properties?: any[];
        categories?: string[];
    }>(),
    {
        rateios: () => [],
        properties: () => [],
        categories: () => [],
    },
);

const categoryLabels: Record<string, string> = {
    agua: 'Água',
    condominio: 'Condomínio',
    gas: 'Gás',
    internet: 'Internet',
    iptu: 'IPTU',
    outro: 'Outro',
};

const categoryOptions = computed(() =>
    props.categories.map((category) => ({
        value: category,
        label: categoryLabels[category] ?? category,
    })),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Rateios', href: index() },
        ],
    },
});

const { formatCurrency } = useMoney();

function categoryLabel(category?: string | null): string {
    if (!category) {
        return '-';
    }

    return categoryLabels[category] ?? category;
}

function splitModeLabel(mode?: string | null): string {
    if (mode === 'residents') {
        return 'Por moradores';
    }

    if (mode === 'equal') {
        return 'Igual';
    }

    return mode ?? '-';
}

function allocationSummary(rateio: any): string {
    const allocations = rateio.allocations ?? [];

    if (allocations.length === 0) {
        return '-';
    }

    return allocations
        .map(
            (allocation: any) =>
                allocation.property?.name ?? `#${allocation.property_id}`,
        )
        .join(', ');
}
</script>

<template>
    <Head title="Rateios" />

    <div class="flex flex-col gap-8">
        <Heading
            title="Rateios"
            description="Divida despesas entre imóveis e aplique nas cobranças"
        />

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Novo rateio</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="store.form()"
                    enctype="multipart/form-data"
                    class="grid gap-5 md:grid-cols-2"
                    #default="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="category">Categoria</Label>
                        <FormSelect
                            id="category"
                            name="category"
                            :options="categoryOptions"
                            placeholder="Selecione a categoria"
                            required
                        />
                        <InputError :message="errors.category" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="reference">Referência</Label>
                        <Input
                            id="reference"
                            name="reference"
                            placeholder="2026-07"
                            required
                        />
                        <InputError :message="errors.reference" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="total_amount">Valor total</Label>
                        <Input
                            id="total_amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            name="total_amount"
                            placeholder="0,00"
                            required
                        />
                        <InputError :message="errors.total_amount" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="invoice">Comprovante</Label>
                        <Input
                            id="invoice"
                            type="file"
                            name="invoice"
                            accept="image/jpeg,image/png,application/pdf"
                        />
                        <InputError :message="errors.invoice" />
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label for="description">Descrição</Label>
                        <Textarea
                            id="description"
                            name="description"
                            rows="3"
                            placeholder="Detalhes opcionais do rateio"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-3 md:col-span-2">
                        <Label>Modo de divisão</Label>
                        <RadioGroup
                            name="split_mode"
                            default-value="equal"
                            class="grid gap-3 sm:grid-cols-2"
                        >
                            <Label
                                for="split-equal"
                                class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/80 bg-card px-3 py-2.5 font-normal hover:bg-accent/50"
                            >
                                <RadioGroupItem id="split-equal" value="equal" />
                                Igual entre imóveis
                            </Label>
                            <Label
                                for="split-residents"
                                class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/80 bg-card px-3 py-2.5 font-normal hover:bg-accent/50"
                            >
                                <RadioGroupItem
                                    id="split-residents"
                                    value="residents"
                                />
                                Por quantidade de moradores
                            </Label>
                        </RadioGroup>
                        <InputError :message="errors.split_mode" />
                    </div>

                    <div class="grid gap-3 md:col-span-2">
                        <Label>Imóveis</Label>
                        <div
                            v-if="properties.length === 0"
                            class="rounded-xl bg-muted/50 px-4 py-6 text-sm text-muted-foreground"
                        >
                            Cadastre imóveis para criar um rateio.
                        </div>
                        <div
                            v-else
                            class="grid gap-2 rounded-xl border border-border/80 p-4 sm:grid-cols-2"
                        >
                            <Label
                                v-for="property in properties"
                                :key="property.id"
                                :for="`property-${property.id}`"
                                class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-2 font-normal hover:bg-accent/40"
                            >
                                <Checkbox
                                    :id="`property-${property.id}`"
                                    name="property_ids[]"
                                    :value="String(property.id)"
                                />
                                {{ property.name }}
                            </Label>
                        </div>
                        <InputError :message="errors.property_ids" />
                    </div>

                    <div class="md:col-span-2">
                        <Button type="submit" :disabled="processing">
                            Criar rateio
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Rateios cadastrados</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="rateios.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhum rateio cadastrado.
                </div>
                <DataTable v-else>
                    <thead>
                        <DataTableRow variant="header">
                            <DataTableHeadCell>Categoria</DataTableHeadCell>
                            <DataTableHeadCell>Referência</DataTableHeadCell>
                            <DataTableHeadCell>Valor</DataTableHeadCell>
                            <DataTableHeadCell>Divisão</DataTableHeadCell>
                            <DataTableHeadCell>Imóveis</DataTableHeadCell>
                            <DataTableActionsHeader />
                        </DataTableRow>
                    </thead>
                    <tbody>
                        <DataTableRow
                            v-for="rateio in rateios"
                            :key="rateio.id"
                        >
                            <DataTableCell>
                                {{ categoryLabel(rateio.category) }}
                            </DataTableCell>
                            <DataTableCell class="tabular-nums">
                                {{ rateio.reference ?? '-' }}
                            </DataTableCell>
                            <DataTableCell class="tabular-nums">
                                {{ formatCurrency(rateio.total_amount) }}
                            </DataTableCell>
                            <DataTableCell>
                                {{
                                    splitModeLabel(
                                        rateio.split_mode?.value ??
                                            rateio.split_mode,
                                    )
                                }}
                            </DataTableCell>
                            <DataTableCell>
                                {{ allocationSummary(rateio) }}
                            </DataTableCell>
                            <DataTableActionsCell>
                                <TableActionButton
                                    v-if="rateio.invoice_path"
                                    label="Comprovante"
                                    as-child
                                >
                                    <a :href="invoice(rateio).url">
                                        <FileDown />
                                        <span class="sr-only">Comprovante</span>
                                    </a>
                                </TableActionButton>
                                <Form
                                    v-bind="destroy.form(rateio)"
                                    #default="{ processing: deleting }"
                                >
                                    <TableActionButton
                                        label="Excluir"
                                        :icon="Trash2"
                                        type="submit"
                                        variant="destructive"
                                        :disabled="deleting"
                                    />
                                </Form>
                            </DataTableActionsCell>
                        </DataTableRow>
                    </tbody>
                </DataTable>
            </CardContent>
        </Card>
    </div>
</template>
