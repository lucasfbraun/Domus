<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import BackupSettingController from '@/actions/App/Http/Controllers/Admin/BackupSettingController';
import BillingSettingController from '@/actions/App/Http/Controllers/Admin/BillingSettingController';
import PixSyncSettingController from '@/actions/App/Http/Controllers/Admin/PixSyncSettingController';
import FormSelect from '@/components/FormSelect.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/dates';
import { dashboard } from '@/routes';
import { edit } from '@/routes/admin/billing-settings';

defineProps<{
    generation_day: number;
    backup_frequency: 'disabled' | 'daily' | 'weekly' | 'monthly';
    backup_retention_count: number;
    backup_run_at_hour: number;
    backup_last_run_at: string | null;
    backup_next_run_at: string | null;
    pix_sync_enabled: boolean;
    pix_sync_interval_value: number;
    pix_sync_interval_unit: 'minutes' | 'hours';
    pix_sync_last_run_at: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Configurações', href: edit() },
        ],
    },
});

const backupFrequencyOptions = [
    { value: 'disabled', label: 'Desativado' },
    { value: 'daily', label: 'Diário' },
    { value: 'weekly', label: 'Semanal' },
    { value: 'monthly', label: 'Mensal' },
];

const backupHourOptions = Array.from({ length: 24 }, (_, hour) => ({
    value: hour,
    label: String(hour).padStart(2, '0') + ':00',
}));

const pixSyncIntervalUnitOptions = [
    { value: 'minutes', label: 'Minutos' },
    { value: 'hours', label: 'Horas' },
];
</script>

<template>
    <Head title="Configurações" />

    <div class="flex flex-col gap-8">
        <Heading
            title="Configurações"
            description="Cobrança automática, backup do banco de dados e sincronização com o Mercado Pago"
        />

        <Card class="max-w-xl border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Geração automática de cobrança</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="BillingSettingController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="generation_day">
                            Dia do mês em que as cobranças são geradas
                        </Label>
                        <Input
                            id="generation_day"
                            type="number"
                            min="1"
                            max="28"
                            class="max-w-32"
                            name="generation_day"
                            :default-value="generation_day"
                            required
                        />
                        <p class="text-sm text-muted-foreground">
                            A partir desse dia, o sistema gera a cobrança mensal
                            de todos os contratos ativos, um dia após o outro
                            até o fim do mês caso a execução tenha sido
                            interrompida. O vencimento de cada contrato não muda
                            — continua o que está cadastrado nele.
                        </p>
                        <InputError :message="errors.generation_day" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="processing">Salvar</Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <Card class="max-w-xl border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Backup automático</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="BackupSettingController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="backup_frequency">Periodicidade</Label>
                        <div class="max-w-48">
                            <FormSelect
                                id="backup_frequency"
                                name="frequency"
                                :options="backupFrequencyOptions"
                                :default-value="backup_frequency"
                                required
                            />
                        </div>
                        <p class="text-sm text-muted-foreground">
                            Com que frequência um backup é gerado
                            automaticamente. "Desativado" mantém só os backups
                            manuais (Admin → Backups).
                        </p>
                        <InputError :message="errors.frequency" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="run_at_hour">Horário do backup</Label>
                        <div class="max-w-48">
                            <FormSelect
                                id="run_at_hour"
                                name="run_at_hour"
                                :options="backupHourOptions"
                                :default-value="backup_run_at_hour"
                                required
                            />
                        </div>
                        <p class="text-sm text-muted-foreground">
                            Em que hora do dia o backup automático roda, quando
                            chega a vez dele conforme a periodicidade acima. O
                            padrão (03:00) evita concorrer com os horários de
                            geração de cobrança e lembretes.
                        </p>
                        <InputError :message="errors.run_at_hour" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="retention_count">
                            Quantidade máxima de backups mantidos
                        </Label>
                        <Input
                            id="retention_count"
                            type="number"
                            min="1"
                            max="90"
                            class="max-w-32"
                            name="retention_count"
                            :default-value="backup_retention_count"
                            required
                        />
                        <p class="text-sm text-muted-foreground">
                            Ao ultrapassar esse número, os backups mais antigos
                            são apagados automaticamente — vale para qualquer
                            backup (automático, manual ou importado), não só os
                            gerados pela periodicidade acima.
                        </p>
                        <InputError :message="errors.retention_count" />
                    </div>

                    <p
                        v-if="backup_last_run_at"
                        class="text-sm text-muted-foreground"
                    >
                        Último backup automático:
                        {{ formatDateTime(backup_last_run_at) }}
                    </p>
                    <p
                        v-if="backup_next_run_at"
                        class="text-sm text-muted-foreground"
                    >
                        Próximo backup automático:
                        {{ formatDateTime(backup_next_run_at) }} — se essa hora
                        já passou hoje, ele roda amanhã no mesmo horário.
                    </p>

                    <div class="flex items-center gap-4">
                        <Button :disabled="processing">Salvar</Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <Card class="max-w-xl border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>
                    Sincronização automática com o Mercado Pago
                </CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="PixSyncSettingController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="pix_sync_enabled"
                                name="enabled"
                                value="1"
                                :default-checked="pix_sync_enabled"
                            />
                            <Label for="pix_sync_enabled">Ativado</Label>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            Verifica automaticamente se cobranças e cauções com
                            Pix pendente já foram pagas no Mercado Pago. Serve
                            como reforço caso a notificação automática (webhook)
                            falhe ou não esteja configurada — desativar aqui
                            deixa a sincronização só a cargo do botão manual
                            "Sincronizar pagamento".
                        </p>
                        <InputError :message="errors.enabled" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="interval_value">Sincronizar a cada</Label>
                        <div class="flex items-end gap-3">
                            <Input
                                id="interval_value"
                                type="number"
                                min="1"
                                max="1440"
                                class="max-w-24"
                                name="interval_value"
                                :default-value="pix_sync_interval_value"
                                required
                            />
                            <div class="max-w-40">
                                <FormSelect
                                    id="interval_unit"
                                    name="interval_unit"
                                    :options="pixSyncIntervalUnitOptions"
                                    :default-value="pix_sync_interval_unit"
                                    required
                                />
                            </div>
                        </div>
                        <InputError :message="errors.interval_value" />
                        <InputError :message="errors.interval_unit" />
                    </div>

                    <p
                        v-if="pix_sync_last_run_at"
                        class="text-sm text-muted-foreground"
                    >
                        Última sincronização automática:
                        {{ formatDateTime(pix_sync_last_run_at) }}
                    </p>

                    <div class="flex items-center gap-4">
                        <Button :disabled="processing">Salvar</Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
