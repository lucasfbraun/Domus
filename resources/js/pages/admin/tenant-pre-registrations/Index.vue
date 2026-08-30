<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Check, Copy, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppPagination from '@/components/AppPagination.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/StatusBadge.vue';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/dates';
import { dashboard } from '@/routes';
import {
    approve,
    index,
    reject,
    store,
} from '@/routes/admin/tenant-pre-registrations';
import type { Paginated } from '@/types';

type PreRegistration = {
    id: number;
    status: 'pending' | 'in_review' | 'approved' | 'rejected';
    name: string | null;
    document: string | null;
    email: string | null;
    whatsapp: string | null;
    resident_count: number | null;
    invited_at: string;
    expires_at: string;
    submitted_at: string | null;
    is_expired: boolean;
    rejection_note: string | null;
    tenant_id: number | null;
    link: string | null;
};

defineProps<{
    preRegistrations: Paginated<PreRegistration>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Pré-cadastros', href: index() },
        ],
    },
});

const copiedId = ref<number | null>(null);

async function copyLink(preRegistration: PreRegistration): Promise<void> {
    if (!preRegistration.link) {
        return;
    }

    await navigator.clipboard.writeText(preRegistration.link);
    copiedId.value = preRegistration.id;
    setTimeout(() => {
        if (copiedId.value === preRegistration.id) {
            copiedId.value = null;
        }
    }, 2000);
}

const rejectTarget = ref<PreRegistration | null>(null);

const isRejectDialogOpen = computed({
    get: () => rejectTarget.value !== null,
    set: (value: boolean) => {
        if (!value) {
            rejectTarget.value = null;
        }
    },
});

function statusFor(preRegistration: PreRegistration): string {
    return preRegistration.status === 'pending' && preRegistration.is_expired
        ? 'expired'
        : preRegistration.status;
}
</script>

<template>
    <Head title="Pré-cadastros" />

    <div class="flex flex-col gap-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Pré-cadastros"
                description="Gere um link para o futuro inquilino preencher os próprios dados"
            />
            <Form v-bind="store.form()" #default="{ processing }">
                <Button type="submit" :disabled="processing">
                    Gerar link de pré-cadastro
                </Button>
            </Form>
        </div>

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Convites</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="preRegistrations.data.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhum pré-cadastro gerado ainda.
                </div>
                <DataTable v-else>
                    <thead>
                        <DataTableRow variant="header">
                            <DataTableHeadCell>Status</DataTableHeadCell>
                            <DataTableHeadCell>Nome</DataTableHeadCell>
                            <DataTableHeadCell>Contato</DataTableHeadCell>
                            <DataTableHeadCell>Moradores</DataTableHeadCell>
                            <DataTableHeadCell>Convidado em</DataTableHeadCell>
                            <DataTableActionsHeader />
                        </DataTableRow>
                    </thead>
                    <tbody>
                        <DataTableRow
                            v-for="preRegistration in preRegistrations.data"
                            :key="preRegistration.id"
                        >
                            <DataTableCell>
                                <StatusBadge
                                    type="preRegistration"
                                    :status="statusFor(preRegistration)"
                                />
                                <p
                                    v-if="preRegistration.rejection_note"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ preRegistration.rejection_note }}
                                </p>
                            </DataTableCell>
                            <DataTableCell>
                                {{ preRegistration.name ?? '—' }}
                            </DataTableCell>
                            <DataTableCell class="text-sm text-muted-foreground">
                                <div>{{ preRegistration.email ?? '—' }}</div>
                                <div>{{ preRegistration.whatsapp ?? '' }}</div>
                            </DataTableCell>
                            <DataTableCell>
                                {{ preRegistration.resident_count ?? '—' }}
                            </DataTableCell>
                            <DataTableCell>
                                {{ formatDateTime(preRegistration.invited_at) }}
                            </DataTableCell>
                            <DataTableActionsCell>
                                <TableActionButton
                                    v-if="preRegistration.link"
                                    :label="copiedId === preRegistration.id ? 'Copiado!' : 'Copiar link'"
                                    :icon="copiedId === preRegistration.id ? Check : Copy"
                                    @click="copyLink(preRegistration)"
                                />
                                <TableActionButton
                                    v-if="preRegistration.status === 'in_review'"
                                    label="Aceitar"
                                    :icon="Check"
                                    @click="router.post(approve.url(preRegistration.id))"
                                />
                                <TableActionButton
                                    v-if="preRegistration.status === 'in_review'"
                                    label="Recusar"
                                    :icon="X"
                                    variant="destructive"
                                    @click="rejectTarget = preRegistration"
                                />
                            </DataTableActionsCell>
                        </DataTableRow>
                    </tbody>
                </DataTable>
                <AppPagination :paginator="preRegistrations" />
            </CardContent>
        </Card>

        <Dialog v-model:open="isRejectDialogOpen">
            <DialogContent v-if="rejectTarget">
                <DialogHeader>
                    <DialogTitle>Recusar pré-cadastro</DialogTitle>
                    <DialogDescription>
                        Recusar o pré-cadastro de
                        <strong>{{ rejectTarget.name }}</strong>. Você pode
                        registrar o motivo abaixo (opcional).
                    </DialogDescription>
                </DialogHeader>

                <Form
                    v-bind="reject.form(rejectTarget.id)"
                    #default="{ errors, processing }"
                    @success="rejectTarget = null"
                >
                    <div class="grid gap-2">
                        <Label for="note">Motivo (opcional)</Label>
                        <Textarea id="note" name="note" rows="3" />
                        <InputError :message="errors.note" />
                    </div>

                    <DialogFooter class="mt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="rejectTarget = null"
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                        >
                            Recusar
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
