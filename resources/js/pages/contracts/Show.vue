<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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

defineProps<{
    contract: any;
    readyForTenantSignature?: boolean;
    canUploadSigned?: boolean;
    isTenant?: boolean;
    isAdmin?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Contrato',
                href: '/contrato',
            },
        ],
    },
});

function formatCurrency(value?: number | string | null): string {
    if (value === undefined || value === null || value === '') {
        return '-';
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(Number(value));
}

function goBack(): void {
    window.history.back();
}

function printContract(): void {
    window.print();
}
</script>

<template>
    <Head :title="`Contrato #${contract.id}`" />

    <div class="flex flex-col gap-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Heading
                :title="`Contrato #${contract.id}`"
                description="Detalhes do contrato de locação"
            />
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" @click="printContract">Imprimir</Button>
                <Button variant="outline" @click="goBack">Voltar</Button>
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
                        <Badge variant="outline">{{ contract.status ?? '—' }}</Badge>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Assinatura</span>
                        <Badge variant="outline">
                            {{ contract.signature_status ?? '—' }}
                        </Badge>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Valor do aluguel</span>
                        <span>{{ formatCurrency(contract.monthly_rent) }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Início</span>
                        <span>{{ contract.starts_at ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Término</span>
                        <span>{{ contract.ends_at ?? '—' }}</span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Partes envolvidas</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Imóvel</span>
                        <span>{{ contract.property?.name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Endereço</span>
                        <span>{{ contract.property?.address ?? '—' }}</span>
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
                        <span>{{ contract.property?.owner?.name ?? '—' }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card v-if="contract.generated_document_path || contract.signed_document_path">
            <CardHeader>
                <CardTitle>Documentos</CardTitle>
            </CardHeader>
            <CardContent class="flex flex-wrap gap-2">
                <Button
                    v-if="contract.generated_document_path"
                    as-child
                    variant="outline"
                >
                    <a :href="`/contracts/${contract.id}/document/generated`">
                        Baixar contrato gerado
                    </a>
                </Button>
                <Button
                    v-if="contract.signed_document_path"
                    as-child
                    variant="outline"
                >
                    <a :href="`/contracts/${contract.id}/document/signed`">
                        Baixar contrato assinado
                    </a>
                </Button>
            </CardContent>
        </Card>

        <Card v-if="isTenant && canUploadSigned">
            <CardHeader>
                <CardTitle>Enviar contrato assinado</CardTitle>
            </CardHeader>
            <CardContent>
                <p
                    v-if="contract.signature_status === 'rejected' && contract.review_note"
                    class="mb-4 text-sm text-muted-foreground"
                >
                    Rejeitado: {{ contract.review_note }}
                </p>
                <Form
                    :action="`/contracts/${contract.id}/document/upload-signed`"
                    method="post"
                    enctype="multipart/form-data"
                    class="grid max-w-xl gap-3"
                    #default="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="signed_document">PDF assinado</Label>
                        <Input
                            id="signed_document"
                            type="file"
                            name="signed_document"
                            accept="application/pdf"
                            required
                        />
                        <InputError :message="errors.signed_document" />
                    </div>
                    <Button type="submit" :disabled="processing">
                        Enviar para revisão
                    </Button>
                </Form>
            </CardContent>
        </Card>

        <Card
            v-else-if="isTenant && !readyForTenantSignature"
            class="border-dashed"
        >
            <CardContent class="py-6 text-sm text-muted-foreground">
                Aguardando assinaturas do proprietário e testemunhas antes do envio do
                contrato assinado.
            </CardContent>
        </Card>

        <Card v-if="isTenant">
            <CardHeader>
                <CardTitle>Reportar ocorrência</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    action="/occurrences"
                    method="post"
                    enctype="multipart/form-data"
                    class="grid max-w-xl gap-3"
                    #default="{ errors, processing }"
                >
                    <input type="hidden" name="contract_id" :value="contract.id" />
                    <div class="grid gap-2">
                        <Label for="description">Descrição</Label>
                        <Textarea id="description" name="description" rows="4" required />
                        <InputError :message="errors.description" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="photos">Fotos (até 8, JPG/PNG)</Label>
                        <Input
                            id="photos"
                            type="file"
                            name="photos[]"
                            accept="image/jpeg,image/png"
                            multiple
                        />
                        <InputError :message="errors.photos" />
                    </div>
                    <Button type="submit" :disabled="processing">
                        Registrar ocorrência
                    </Button>
                </Form>
            </CardContent>
        </Card>

        <Card v-if="(contract.inspection_photos ?? []).length > 0">
            <CardHeader>
                <CardTitle>Fotos de vistoria</CardTitle>
            </CardHeader>
            <CardContent>
                <ul class="space-y-2 text-sm">
                    <li
                        v-for="photo in contract.inspection_photos"
                        :key="photo.id"
                    >
                        {{ photo.room || 'Foto' }}
                        <span v-if="photo.caption" class="text-muted-foreground">
                            — {{ photo.caption }}
                        </span>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>
</template>
