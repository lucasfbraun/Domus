<script setup lang="ts">
import { Check, Copy, QrCode } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/dates';

const props = defineProps<{
    copyPaste: string;
    qrCodeBase64: string | null;
    expiresAt?: string | null;
    refreshing?: boolean;
}>();

const emit = defineEmits<{
    refresh: [];
}>();

const copied = ref(false);

function qrImageSrc(base64: string): string {
    if (base64.startsWith('data:')) {
        return base64;
    }

    return `data:image/png;base64,${base64}`;
}

async function copyCode(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.copyPaste);
        copied.value = true;
        toast.success('Código Pix copiado', { richColors: true });
        window.setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        toast.error('Não foi possível copiar o código', { richColors: true });
    }
}
</script>

<template>
    <div
        class="mt-4 overflow-hidden rounded-xl border border-border/70 bg-muted/20"
    >
        <div
            class="flex items-center justify-between gap-3 border-b border-border/60 px-4 py-2.5"
        >
            <div class="flex min-w-0 items-center gap-2">
                <QrCode class="size-4 shrink-0 text-muted-foreground" />
                <p class="truncate text-sm font-medium">Pagamento Pix</p>
            </div>
            <Button
                size="sm"
                variant="ghost"
                class="h-8 shrink-0 px-2 text-xs"
                :disabled="refreshing"
                @click="emit('refresh')"
            >
                {{ refreshing ? 'Atualizando...' : 'Atualizar' }}
            </Button>
        </div>

        <div
            class="grid gap-4 p-4 sm:grid-cols-[7.5rem_minmax(0,1fr)] sm:items-start"
        >
            <div
                v-if="qrCodeBase64"
                class="mx-auto h-[7.5rem] w-[7.5rem] shrink-0 overflow-hidden rounded-lg border border-border/60 bg-white p-1.5 sm:mx-0"
            >
                <img
                    :src="qrImageSrc(qrCodeBase64)"
                    alt="QR Code Pix"
                    class="h-full w-full max-h-full max-w-full object-contain"
                    width="120"
                    height="120"
                    decoding="async"
                    style="width: 120px; height: 120px; max-width: 120px; max-height: 120px;"
                />
            </div>

            <div class="flex min-w-0 flex-col gap-3">
                <div class="space-y-1">
                    <p class="text-sm text-muted-foreground">
                        Escaneie o QR no app do banco ou copie o código.
                    </p>
                    <p
                        v-if="expiresAt"
                        class="text-xs text-muted-foreground"
                    >
                        Válido até {{ formatDateTime(expiresAt) }}
                    </p>
                </div>

                <div class="flex min-w-0 items-stretch gap-2">
                    <p
                        class="min-w-0 flex-1 truncate rounded-lg border border-border/70 bg-background px-3 py-2 font-mono text-[11px] leading-5 text-foreground"
                        :title="copyPaste"
                    >
                        {{ copyPaste }}
                    </p>
                    <Button
                        size="sm"
                        variant="secondary"
                        class="h-auto shrink-0 px-3"
                        @click="copyCode"
                    >
                        <Check
                            v-if="copied"
                            class="size-4"
                        />
                        <Copy
                            v-else
                            class="size-4"
                        />
                        <span class="sr-only sm:not-sr-only sm:ml-1.5">
                            {{ copied ? 'Copiado' : 'Copiar' }}
                        </span>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
