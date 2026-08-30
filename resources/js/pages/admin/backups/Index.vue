<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Download, RotateCcw, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/dates';
import { dashboard } from '@/routes';
import backupRoutes, {
    destroy,
    download,
    index,
    restore,
    store,
} from '@/routes/admin/backups';

type Backup = {
    name: string;
    size: number;
    created_at: string;
};

const props = defineProps<{
    supported: boolean;
    driver: string;
    backups: Backup[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Backups', href: index() },
        ],
    },
});

const RESTORE_CONFIRMATION_PHRASE = 'RESTAURAR BANCO';

const restoreTarget = ref<string | null>(null);
const confirmText = ref('');

const isRestoreDialogOpen = computed({
    get: () => restoreTarget.value !== null,
    set: (value: boolean) => {
        if (!value) {
            closeRestoreDialog();
        }
    },
});

function openRestoreDialog(backup: Backup): void {
    restoreTarget.value = backup.name;
    confirmText.value = '';
}

function closeRestoreDialog(): void {
    restoreTarget.value = null;
    confirmText.value = '';
}

function confirmDelete(backup: Backup): void {
    if (
        !window.confirm(
            `Excluir o backup "${backup.name}"? Esta ação não pode ser desfeita.`,
        )
    ) {
        return;
    }

    router.delete(destroy.url(backup.name), { preserveScroll: true });
}

function formatBytes(bytes: number): string {
    if (!Number.isFinite(bytes) || bytes <= 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const exponent = Math.min(
        Math.floor(Math.log(bytes) / Math.log(1024)),
        units.length - 1,
    );
    const value = bytes / 1024 ** exponent;

    return `${exponent === 0 ? value.toFixed(0) : value.toFixed(1)} ${units[exponent]}`;
}
</script>

<template>
    <Head title="Backups" />

    <div class="flex flex-col gap-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Backups"
                description="Exportação e restauração manual do banco de dados"
            />
            <Form
                v-if="props.supported"
                v-bind="store.form()"
                #default="{ errors, processing }"
            >
                <div class="flex flex-col items-end gap-2">
                    <Button type="submit" :disabled="processing">
                        Gerar backup agora
                    </Button>
                    <InputError :message="errors.backup" />
                </div>
            </Form>
        </div>

        <p class="max-w-2xl text-sm text-muted-foreground">
            Esta funcionalidade exporta e restaura apenas o arquivo do banco de
            dados SQLite da aplicação. Arquivos de mídia (fotos, documentos)
            enviados pelos usuários não fazem parte deste backup.
        </p>

        <Card v-if="props.supported" class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Importar backup</CardTitle>
            </CardHeader>
            <CardContent>
                <p class="mb-4 text-sm text-muted-foreground">
                    Envie um arquivo <code>.sql.gz</code> gerado por este
                    mesmo sistema (baixado daqui ou de outro ambiente) para
                    adicioná-lo à lista abaixo, sem precisar copiá-lo
                    manualmente no servidor.
                </p>
                <Form
                    v-bind="backupRoutes.import.form()"
                    enctype="multipart/form-data"
                    reset-on-success
                    class="flex flex-wrap items-end gap-4"
                    #default="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="import-file">Arquivo (.sql.gz)</Label>
                        <Input
                            id="import-file"
                            type="file"
                            name="file"
                            accept=".gz"
                            required
                        />
                        <InputError :message="errors.file" />
                    </div>
                    <Button type="submit" :disabled="processing">
                        Importar
                    </Button>
                </Form>
            </CardContent>
        </Card>

        <Card v-if="!props.supported" class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Backup não disponível</CardTitle>
            </CardHeader>
            <CardContent>
                <p class="text-sm text-muted-foreground">
                    Backup automático não é suportado com o driver atual
                    ({{ props.driver }}). Esta funcionalidade só funciona com
                    sqlite.
                </p>
            </CardContent>
        </Card>

        <Card v-else class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Backups disponíveis</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="props.backups.length === 0"
                    class="rounded-xl bg-muted/50 px-6 py-12 text-center text-sm text-muted-foreground"
                >
                    Nenhum backup disponível.
                </div>
                <DataTable v-else>
                    <thead>
                        <DataTableRow variant="header">
                            <DataTableHeadCell>Arquivo</DataTableHeadCell>
                            <DataTableHeadCell>Tamanho</DataTableHeadCell>
                            <DataTableHeadCell>Criado em</DataTableHeadCell>
                            <DataTableActionsHeader />
                        </DataTableRow>
                    </thead>
                    <tbody>
                        <DataTableRow
                            v-for="backup in props.backups"
                            :key="backup.name"
                        >
                            <DataTableCell class="max-w-xs truncate font-mono text-xs">
                                {{ backup.name }}
                            </DataTableCell>
                            <DataTableCell>
                                {{ formatBytes(backup.size) }}
                            </DataTableCell>
                            <DataTableCell>
                                {{ formatDateTime(backup.created_at) }}
                            </DataTableCell>
                            <DataTableActionsCell>
                                <TableActionButton label="Baixar" as-child>
                                    <a :href="download.url(backup.name)">
                                        <Download />
                                        <span class="sr-only">Baixar</span>
                                    </a>
                                </TableActionButton>
                                <TableActionButton
                                    label="Restaurar"
                                    :icon="RotateCcw"
                                    @click="openRestoreDialog(backup)"
                                />
                                <TableActionButton
                                    label="Excluir"
                                    :icon="Trash2"
                                    variant="destructive"
                                    @click="confirmDelete(backup)"
                                />
                            </DataTableActionsCell>
                        </DataTableRow>
                    </tbody>
                </DataTable>
            </CardContent>
        </Card>

        <Dialog v-model:open="isRestoreDialogOpen">
            <DialogContent v-if="restoreTarget">
                <DialogHeader>
                    <DialogTitle>Restaurar backup</DialogTitle>
                    <DialogDescription>
                        Isso substitui todo o banco de dados atual pelo conteúdo
                        de <strong>{{ restoreTarget }}</strong>. Um backup de
                        segurança do estado atual é criado automaticamente antes
                        da restauração.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    v-bind="restore.form(restoreTarget)"
                    #default="{ errors, processing }"
                    @success="closeRestoreDialog"
                >
                    <div class="grid gap-2">
                        <Label for="confirm">
                            Digite "{{ RESTORE_CONFIRMATION_PHRASE }}" para
                            confirmar
                        </Label>
                        <Input
                            id="confirm"
                            v-model="confirmText"
                            name="confirm"
                            autocomplete="off"
                        />
                        <InputError :message="errors.confirm" />
                        <InputError :message="errors.restore" />
                    </div>

                    <DialogFooter class="mt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeRestoreDialog"
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="
                                processing ||
                                confirmText !== RESTORE_CONFIRMATION_PHRASE
                            "
                        >
                            Restaurar
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
