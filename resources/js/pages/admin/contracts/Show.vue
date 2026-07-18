<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import FormSelect from '@/components/FormSelect.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDate } from '@/lib/dates';
import { useMoney } from '@/composables/useMoney';
import { dashboard } from '@/routes';
import {
    edit,
    index,
    ownerSign,
    show,
} from '@/routes/admin/contracts';
import {
    generate as generateDocument,
    ownerSigned as downloadOwnerSigned,
    review,
    uploadOwnerSigned,
} from '@/routes/admin/contracts/document';
import {
    destroy as destroyPhoto,
    store as storePhoto,
} from '@/routes/admin/contracts/inspection-photos';
import { sign as signWitness } from '@/routes/admin/contracts/witnesses';
import { generate as generateCharges } from '@/routes/admin/charges';
import {
    generated as downloadGenerated,
    signed as downloadSigned,
} from '@/routes/contracts/document';

const props = defineProps<{
    contract: any;
    templates?: any[];
    readyForTenantSignature?: boolean;
}>();

defineOptions({
    layout: (pageProps: { contract: { id: number } }) => ({
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Contratos', href: index() },
            {
                title: 'Detalhes',
                href: show(pageProps.contract),
            },
        ],
    }),
});

const templateOptions = (props.templates ?? []).map((template) => ({
    value: template.id,
    label: template.name,
}));

const ownerNames = computed(() =>
    props.contract.property?.owners?.length
        ? props.contract.property.owners
              .map((owner: any) => owner.name)
              .join(', ')
        : '—',
);

const { formatCurrency } = useMoney();

function formatPercent(value?: number | string | null): string {
    if (value === undefined || value === null || value === '') {
        return '—';
    }

    return `${(Number(value) * 100).toFixed(0)}%`;
}
</script>

<template>
    <Head :title="`Contrato #${contract.id}`" />

    <div class="flex flex-col gap-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Heading
                :title="`Contrato #${contract.id}`"
                description="Detalhes, documentos e assinaturas"
            />
            <div class="flex items-center gap-2">
                <Button as-child variant="outline">
                    <Link :href="edit(contract)">Editar</Link>
                </Button>
                <Button as-child variant="outline">
                    <Link :href="index()">Voltar</Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Dados do contrato</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Status</span>
                        <StatusBadge type="contract" :status="contract.status" />
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Assinatura</span>
                        <StatusBadge
                            type="signature"
                            :status="contract.signature_status"
                        />
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Valor do aluguel</span>
                        <span>{{ formatCurrency(contract.monthly_rent) }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Vencimento</span>
                        <span>Dia {{ contract.due_day ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Início</span>
                        <span>{{ formatDate(contract.starts_at) }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Término</span>
                        <span>{{ formatDate(contract.ends_at) }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Multa / Juros / Carência</span>
                        <span>
                            {{ formatPercent(contract.fine_rate) }} /
                            {{ formatPercent(contract.monthly_interest_rate) }} /
                            {{ contract.grace_days ?? 0 }}d
                        </span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Relacionamentos</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Imóvel</span>
                        <span>{{ contract.property?.name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Inquilino</span>
                        <span>{{ contract.tenant?.name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Recebedor</span>
                        <span>{{ contract.receiver?.name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Proprietário</span>
                        <span>{{ ownerNames }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Modelo</span>
                        <span>{{ contract.template?.name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Pronto para inquilino</span>
                        <Badge :variant="readyForTenantSignature ? 'default' : 'outline'">
                            {{ readyForTenantSignature ? 'Sim' : 'Não' }}
                        </Badge>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Assinaturas</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-3 rounded-lg border p-3">
                    <p class="text-sm font-medium">Documento assinado pelo proprietário</p>
                    <p class="text-xs text-muted-foreground">
                        Baixe o contrato gerado, colha a assinatura do proprietário fora do
                        sistema e envie o PDF assinado aqui. Só é possível marcar a assinatura
                        depois desse envio.
                    </p>

                    <Form
                        v-bind="uploadOwnerSigned.form(contract)"
                        enctype="multipart/form-data"
                        class="flex flex-wrap items-end gap-3"
                        #default="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="owner_signed_document">PDF assinado</Label>
                            <Input
                                id="owner_signed_document"
                                type="file"
                                name="owner_signed_document"
                                accept="application/pdf"
                                required
                            />
                            <InputError :message="errors.owner_signed_document" />
                        </div>
                        <Button type="submit" variant="outline" :disabled="processing">
                            {{ contract.owner_signed_document_path ? 'Reenviar' : 'Enviar' }}
                        </Button>
                    </Form>

                    <Button
                        v-if="contract.owner_signed_document_path"
                        as-child
                        size="sm"
                        variant="ghost"
                    >
                        <a :href="downloadOwnerSigned.url(contract)">
                            Baixar documento enviado
                        </a>
                    </Button>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Form
                        v-bind="ownerSign.form(contract)"
                        #default="{ errors, processing }"
                    >
                        <Button
                            type="submit"
                            variant="outline"
                            :disabled="
                                processing ||
                                !!contract.owner_signed_at ||
                                !contract.owner_signed_document_path
                            "
                        >
                            {{
                                contract.owner_signed_at
                                    ? 'Proprietário assinado'
                                    : 'Marcar assinatura do proprietário'
                            }}
                        </Button>
                        <InputError :message="errors.owner_signed_document" />
                    </Form>
                </div>

                <div
                    v-if="(contract.witnesses ?? []).length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhuma testemunha vinculada.
                </div>
                <div v-else class="space-y-2">
                    <div
                        v-for="witness in contract.witnesses"
                        :key="witness.id"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3 text-sm"
                    >
                        <span>{{ witness.receiver?.name ?? `Testemunha #${witness.id}` }}</span>
                        <Form
                            v-bind="signWitness.form({ contract, witness })"
                            #default="{ processing }"
                        >
                            <Button
                                type="submit"
                                size="sm"
                                variant="outline"
                                :disabled="processing || !!witness.signed_at"
                            >
                                {{ witness.signed_at ? 'Assinado' : 'Marcar assinado' }}
                            </Button>
                        </Form>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Documento do contrato</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <Form
                    v-bind="generateDocument.form(contract)"
                    class="grid max-w-xl gap-3 sm:grid-cols-[1fr_auto] sm:items-end"
                    #default="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="template_id">Modelo</Label>
                        <FormSelect
                            id="template_id"
                            name="template_id"
                            :options="templateOptions"
                            :default-value="contract.template_id"
                            placeholder="Selecione um modelo"
                            required
                        />
                        <InputError :message="errors.template_id" />
                    </div>
                    <Button type="submit" :disabled="processing">
                        Gerar documento
                    </Button>
                </Form>

                <div class="flex flex-wrap gap-2">
                    <Button
                        v-if="contract.generated_document_path"
                        as-child
                        variant="outline"
                    >
                        <a :href="downloadGenerated.url(contract)">
                            Baixar gerado
                        </a>
                    </Button>
                    <Button
                        v-if="contract.signed_document_path"
                        as-child
                        variant="outline"
                    >
                        <a :href="downloadSigned.url(contract)">
                            Baixar assinado
                        </a>
                    </Button>
                </div>

                <div
                    v-if="contract.signature_status === 'in_review'"
                    class="space-y-3 rounded-lg border border-amber-500/30 bg-amber-500/5 p-4"
                >
                    <p class="text-sm font-medium">Contrato assinado aguardando revisão</p>
                    <Form
                        v-bind="review.form(contract)"
                        class="space-y-3"
                        #default="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="review_note">Observação</Label>
                            <Textarea id="review_note" name="review_note" rows="2" />
                            <InputError :message="errors.review_note" />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                type="submit"
                                name="action"
                                value="approve"
                                :disabled="processing"
                            >
                                Aprovar
                            </Button>
                            <Button
                                type="submit"
                                name="action"
                                value="reject"
                                variant="outline"
                                :disabled="processing"
                            >
                                Rejeitar
                            </Button>
                        </div>
                    </Form>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Fotos de vistoria</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <Form
                    v-bind="storePhoto.form(contract)"
                    enctype="multipart/form-data"
                    class="grid max-w-xl gap-3 sm:grid-cols-2"
                    #default="{ errors, processing }"
                >
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="photo">Foto</Label>
                        <Input
                            id="photo"
                            type="file"
                            name="photo"
                            accept="image/jpeg,image/png"
                            required
                        />
                        <InputError :message="errors.photo" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="room">Cômodo</Label>
                        <Input id="room" name="room" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="caption">Legenda</Label>
                        <Input id="caption" name="caption" />
                    </div>
                    <div class="sm:col-span-2">
                        <Button type="submit" :disabled="processing">
                            Enviar foto
                        </Button>
                    </div>
                </Form>

                <div
                    v-if="(contract.inspection_photos ?? []).length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhuma foto de vistoria.
                </div>
                <ul v-else class="space-y-2 text-sm">
                    <li
                        v-for="photo in contract.inspection_photos"
                        :key="photo.id"
                        class="flex items-center justify-between gap-3 rounded-lg border p-3"
                    >
                        <span>
                            {{ photo.room || 'Foto' }}
                            <span v-if="photo.caption" class="text-muted-foreground">
                                — {{ photo.caption }}
                            </span>
                        </span>
                        <Form
                            v-bind="destroyPhoto.form({ contract, photo })"
                            #default="{ processing }"
                        >
                            <Button type="submit" size="sm" variant="outline" :disabled="processing">
                                Remover
                            </Button>
                        </Form>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Cobranças</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <Form
                    v-bind="generateCharges.form(contract)"
                    #default="{ processing }"
                >
                    <Button type="submit" variant="outline" :disabled="processing">
                        Gerar cobrança do mês
                    </Button>
                </Form>

                <div
                    v-if="(contract.charges ?? []).length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhuma cobrança gerada.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Referência</th>
                                <th class="pb-2 pr-4 font-medium">Valor</th>
                                <th class="pb-2 pr-4 font-medium">Vencimento</th>
                                <th class="pb-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="charge in contract.charges"
                                :key="charge.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">{{ charge.reference }}</td>
                                <td class="py-3 pr-4">
                                    {{ formatCurrency(charge.original_amount) }}
                                </td>
                                <td class="py-3 pr-4">{{ formatDate(charge.due_date) }}</td>
                                <td class="py-3">
                                    <StatusBadge
                                        type="charge"
                                        :status="charge.status"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
