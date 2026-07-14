<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index } from '@/routes/admin/integrations';

defineProps<{
    mercadoPago?: {
        appConfigured?: boolean;
        connectedReceiversCount?: number;
        sandbox?: boolean;
        platformTokenConfigured?: boolean;
    };
    waha?: { connected: boolean; status?: string };
    mail?: { configured: boolean; mailer?: string; from?: string };
    cron?: { enabled: boolean; last_run?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Integrações', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="Integrações" />

    <div class="flex flex-col gap-8">
        <Heading
            title="Integrações"
            description="Status das integrações do sistema"
        />

        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Mercado Pago</CardTitle>
                    <CardDescription>Pagamentos via PIX</CardDescription>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <Badge :variant="mercadoPago?.appConfigured ? 'default' : 'outline'">
                        {{
                            mercadoPago?.appConfigured
                                ? 'App configurada'
                                : 'App não configurada'
                        }}
                    </Badge>
                    <p class="text-muted-foreground">
                        Recebedores com OAuth:
                        {{ mercadoPago?.connectedReceiversCount ?? 0 }}
                    </p>
                    <p class="text-muted-foreground">
                        Modo:
                        {{ mercadoPago?.sandbox ? 'Sandbox' : 'Produção' }}
                        · Orders API (Pix)
                    </p>
                    <p
                        v-if="mercadoPago?.platformTokenConfigured"
                        class="text-muted-foreground"
                    >
                        MP_ACCESS_TOKEN definido (atalho local/testes).
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>WAHA (WhatsApp)</CardTitle>
                    <CardDescription>Notificações via WhatsApp</CardDescription>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <Badge :variant="waha?.connected ? 'default' : 'outline'">
                        {{ waha?.connected ? 'Conectado' : 'Desconectado' }}
                    </Badge>
                    <p v-if="waha?.status" class="text-muted-foreground">
                        Status: {{ waha.status }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>E-mail (SMTP)</CardTitle>
                    <CardDescription>Envio de e-mails transacionais</CardDescription>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <Badge :variant="mail?.configured ? 'default' : 'outline'">
                        {{ mail?.configured ? 'Configurado' : 'Não configurado' }}
                    </Badge>
                    <p v-if="mail?.mailer" class="text-muted-foreground">
                        Mailer: {{ mail.mailer }}
                    </p>
                    <p v-if="mail?.from" class="text-muted-foreground">
                        Remetente: {{ mail.from }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Cron / Agendador</CardTitle>
                    <CardDescription>Tarefas agendadas do sistema</CardDescription>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <Badge :variant="cron?.enabled ? 'default' : 'outline'">
                        {{ cron?.enabled ? 'Ativo' : 'Inativo' }}
                    </Badge>
                    <p v-if="cron?.last_run" class="text-muted-foreground">
                        Última execução: {{ cron.last_run }}
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
