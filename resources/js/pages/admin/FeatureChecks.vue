<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { CheckCircle2, Loader2, XCircle } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DataTable,
    DataTableCell,
    DataTableHeadCell,
    DataTableRow,
} from '@/components/ui/data-table';
import { formatDateTime } from '@/lib/dates';
import { dashboard } from '@/routes';
import { index, run, status } from '@/routes/admin/feature-checks';

type Feature = {
    area: string;
    name: string;
    description: string;
    source: string[];
    tests: string[];
    note: string | null;
};

type LastRun = {
    status: 'passed' | 'failed';
    exit_code: number;
    summary: string | null;
    output: string;
    started_at: string;
    finished_at: string;
    duration_seconds: number;
} | null;

const props = defineProps<{
    features: Feature[];
    runnerAvailable: boolean;
    running: boolean;
    lastRun: LastRun;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Funcionalidades', href: index() },
        ],
    },
});

const running = ref(props.running);
const lastRun = ref<LastRun>(props.lastRun);
const showOutput = ref(false);
let pollTimer: ReturnType<typeof setInterval> | null = null;

const groupedFeatures = computed(() => {
    const groups = new Map<string, Feature[]>();

    for (const feature of props.features) {
        const list = groups.get(feature.area) ?? [];
        list.push(feature);
        groups.set(feature.area, list);
    }

    return Array.from(groups.entries());
});

const totalFeatures = computed(() => props.features.length);
const testedFeatures = computed(
    () => props.features.filter((feature) => feature.tests.length > 0).length,
);

const hasKnownDiskFlakiness = computed(
    () =>
        lastRun.value?.status === 'failed' &&
        lastRun.value.output.includes('disk I/O error'),
);

function stopPolling(): void {
    if (pollTimer !== null) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

function startPolling(): void {
    stopPolling();

    pollTimer = setInterval(async () => {
        const response = await fetch(status.url(), {
            headers: { Accept: 'application/json' },
        });
        const data = await response.json();

        running.value = data.running;
        lastRun.value = data.lastRun;

        if (!data.running) {
            stopPolling();
        }
    }, 3000);
}

watch(
    () => props.running,
    (value) => {
        running.value = value;

        if (value) {
            startPolling();
        }
    },
);

watch(
    () => props.lastRun,
    (value) => {
        lastRun.value = value;
    },
);

onMounted(() => {
    if (running.value) {
        startPolling();
    }
});

onBeforeUnmount(stopPolling);

function formatDuration(seconds: number): string {
    if (seconds < 60) {
        return `${seconds}s`;
    }

    const minutes = Math.floor(seconds / 60);
    const rest = seconds % 60;

    return `${minutes}min ${rest}s`;
}
</script>

<template>
    <Head title="Funcionalidades" />

    <div class="flex flex-col gap-8">
        <Heading
            title="Funcionalidades"
            description="Catálogo de funcionalidades do sistema e cobertura de testes"
        />

        <Card class="border-border/80 shadow-sm">
            <CardHeader>
                <CardTitle>Suíte de testes</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <p class="text-sm text-muted-foreground">
                    {{ testedFeatures }} de {{ totalFeatures }}
                    funcionalidades mapeadas têm teste automatizado.
                </p>

                <p
                    v-if="!runnerAvailable"
                    class="text-sm text-muted-foreground"
                >
                    Executar a suíte só está disponível em ambiente local, com
                    as dependências de desenvolvimento instaladas — não roda
                    em produção.
                </p>

                <Form
                    v-if="runnerAvailable"
                    v-bind="run.form()"
                    #default="{ errors, processing }"
                >
                    <div class="flex flex-col items-start gap-2">
                        <Button
                            type="submit"
                            :disabled="processing || running"
                        >
                            <Loader2
                                v-if="running"
                                class="animate-spin"
                            />
                            {{
                                running
                                    ? 'Rodando testes...'
                                    : 'Rodar suíte de testes'
                            }}
                        </Button>
                        <InputError :message="errors.run" />
                    </div>
                </Form>

                <div
                    v-if="lastRun"
                    class="rounded-xl border border-border/80 p-4"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <CheckCircle2
                            v-if="lastRun.status === 'passed'"
                            class="size-5 text-green-600 dark:text-green-400"
                        />
                        <XCircle
                            v-else
                            class="size-5 text-destructive"
                        />
                        <span class="font-medium">
                            {{
                                lastRun.status === 'passed'
                                    ? 'Passou'
                                    : 'Falhou'
                            }}
                        </span>
                        <span
                            v-if="lastRun.summary"
                            class="text-sm text-muted-foreground"
                        >
                            {{ lastRun.summary }}
                        </span>
                    </div>
                    <p
                        v-if="hasKnownDiskFlakiness"
                        class="mt-2 text-xs text-muted-foreground"
                    >
                        Essa falha menciona "disk I/O error" — é uma
                        instabilidade conhecida do SQLite neste ambiente
                        (Windows/Docker), não necessariamente um problema
                        real no código. Rode de novo antes de investigar.
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Rodado em {{ formatDateTime(lastRun.finished_at) }} ·
                        {{ formatDuration(lastRun.duration_seconds) }}
                    </p>
                    <button
                        type="button"
                        class="mt-2 text-sm text-foreground underline decoration-neutral-300 underline-offset-4"
                        @click="showOutput = !showOutput"
                    >
                        {{ showOutput ? 'Ocultar saída' : 'Ver saída completa' }}
                    </button>
                    <pre
                        v-if="showOutput"
                        class="mt-2 max-h-96 overflow-auto rounded-lg bg-muted p-3 text-xs"
                        >{{ lastRun.output }}</pre
                    >
                </div>
            </CardContent>
        </Card>

        <Card
            v-for="[area, features] in groupedFeatures"
            :key="area"
            class="border-border/80 shadow-sm"
        >
            <CardHeader>
                <CardTitle>{{ area }}</CardTitle>
            </CardHeader>
            <CardContent>
                <DataTable>
                    <thead>
                        <DataTableRow variant="header">
                            <DataTableHeadCell>Funcionalidade</DataTableHeadCell>
                            <DataTableHeadCell>Descrição</DataTableHeadCell>
                            <DataTableHeadCell>Testes</DataTableHeadCell>
                        </DataTableRow>
                    </thead>
                    <tbody>
                        <DataTableRow
                            v-for="feature in features"
                            :key="feature.name"
                        >
                            <DataTableCell class="font-medium">
                                {{ feature.name }}
                            </DataTableCell>
                            <DataTableCell class="max-w-md text-sm text-muted-foreground">
                                {{ feature.description }}
                            </DataTableCell>
                            <DataTableCell>
                                <span
                                    v-if="feature.tests.length > 0"
                                    class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800 dark:bg-green-900/40 dark:text-green-300"
                                >
                                    Testado ({{ feature.tests.length }})
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-800 dark:bg-red-900/40 dark:text-red-300"
                                >
                                    Sem teste
                                </span>
                                <p
                                    v-if="feature.note"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ feature.note }}
                                </p>
                            </DataTableCell>
                        </DataTableRow>
                    </tbody>
                </DataTable>
            </CardContent>
        </Card>
    </div>
</template>
