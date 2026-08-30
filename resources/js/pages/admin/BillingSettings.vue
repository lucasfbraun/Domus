<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import BillingSettingController from '@/actions/App/Http/Controllers/Admin/BillingSettingController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { edit } from '@/routes/admin/billing-settings';

defineProps<{
    generation_day: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Configurações de cobrança', href: edit() },
        ],
    },
});
</script>

<template>
    <Head title="Configurações de cobrança" />

    <div class="flex flex-col gap-8">
        <Heading
            title="Configurações de cobrança"
            description="Defina quando as cobranças mensais são geradas automaticamente"
        />

        <Card class="max-w-xl border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Geração automática</CardTitle>
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
                            A partir desse dia, o sistema gera a cobrança
                            mensal de todos os contratos ativos, um dia após
                            o outro até o fim do mês caso a execução tenha
                            sido interrompida. O vencimento de cada contrato
                            não muda — continua o que está cadastrado nele.
                        </p>
                        <InputError :message="errors.generation_day" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="processing">Salvar</Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
