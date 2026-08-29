<script setup lang="ts">
import { CheckCircle2, Download, Smartphone } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { usePwaInstall } from '@/lib/pwa';

const { isInstalled, isIos, promptInstall } = usePwaInstall();

const showManualInstructions = ref(false);

const manualInstructions = computed(() =>
    isIos
        ? [
              'Abra este site no Safari.',
              'Toque no botão Compartilhar.',
              'Escolha Adicionar à Tela de Início.',
          ]
        : [
              'Abra o menu do navegador (⋮).',
              'Escolha Instalar app ou Adicionar à tela inicial.',
              'Confirme a instalação.',
          ],
);

async function installApp(): Promise<void> {
    const result = await promptInstall();

    // Without a native prompt the button still has to do something visible.
    showManualInstructions.value = result !== 'accepted';
}
</script>

<template>
    <section
        class="rounded-lg border border-border bg-card p-4 text-card-foreground shadow-xs"
        data-test="pwa-install-card"
    >
        <div class="flex items-start gap-3">
            <div
                class="flex size-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary"
                aria-hidden="true"
            >
                <CheckCircle2 v-if="isInstalled" class="size-5" />
                <Smartphone v-else class="size-5" />
            </div>

            <div class="min-w-0 flex-1">
                <h2 class="text-sm font-semibold leading-5">
                    {{ isInstalled ? 'Aplicativo instalado' : 'Instale o Domus' }}
                </h2>
                <p class="mt-1 text-sm leading-5 text-muted-foreground">
                    {{
                        isInstalled
                            ? 'O Domus já está disponível como aplicativo neste dispositivo.'
                            : 'Acesse o Domus pela tela inicial, com abertura mais rápida e visual de app.'
                    }}
                </p>
            </div>
        </div>

        <Button
            v-if="!isInstalled"
            type="button"
            variant="outline"
            class="mt-4 w-full"
            data-test="pwa-install-button"
            :aria-expanded="showManualInstructions"
            @click="installApp"
        >
            <Download class="size-4" />
            Instalar aplicativo
        </Button>

        <ol
            v-if="showManualInstructions && !isInstalled"
            class="mt-3 list-decimal space-y-1 rounded-md bg-muted px-3 py-2 pl-7 text-xs leading-5 text-muted-foreground"
            data-test="pwa-install-fallback"
        >
            <li v-for="instruction in manualInstructions" :key="instruction">
                {{ instruction }}
            </li>
        </ol>
    </section>
</template>
