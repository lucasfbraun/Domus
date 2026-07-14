<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CircleHelp, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const page = usePage();

const isAdmin = computed(() => {
    const roles = page.props.auth.user?.roles;

    return Array.isArray(roles) && roles.includes('admin');
});

const open = ref(false);
const query = ref('');
const loading = ref(false);
const results = ref<Array<{ id: string; title: string; answer: string }>>([]);

async function search(): Promise<void> {
    if (!query.value.trim()) {
        results.value = [];

        return;
    }

    loading.value = true;

    try {
        const response = await fetch(
            `/help/search?q=${encodeURIComponent(query.value)}`,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            },
        );

        const data = (await response.json()) as {
            results?: Array<{ id: string; title: string; answer: string }>;
        };

        results.value = data.results ?? [];
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div v-if="isAdmin" class="fixed right-4 bottom-4 z-50 flex flex-col items-end gap-3">
        <div
            v-if="open"
            class="w-[min(100vw-2rem,22rem)] rounded-xl border border-border bg-background p-4 shadow-lg"
        >
            <div class="mb-3 flex items-center justify-between gap-2">
                <p class="text-sm font-medium">Ajuda do painel</p>
                <Button size="icon" variant="ghost" @click="open = false">
                    <X class="size-4" />
                </Button>
            </div>

            <form class="flex gap-2" @submit.prevent="search">
                <Input
                    v-model="query"
                    placeholder="Ex.: pix, rateio, assinatura"
                    class="flex-1"
                />
                <Button type="submit" :disabled="loading">
                    {{ loading ? '...' : 'Buscar' }}
                </Button>
            </form>

            <div class="mt-3 max-h-72 space-y-3 overflow-y-auto text-sm">
                <p v-if="!loading && results.length === 0 && query" class="text-muted-foreground">
                    Nenhum resultado.
                </p>
                <div v-for="item in results" :key="item.id" class="rounded-lg bg-muted/50 p-3">
                    <p class="font-medium">{{ item.title }}</p>
                    <p class="mt-1 text-muted-foreground">{{ item.answer }}</p>
                </div>
            </div>
        </div>

        <Button
            size="lg"
            class="rounded-full shadow-md"
            @click="open = !open"
        >
            <CircleHelp class="size-5" />
            Ajuda
        </Button>
    </div>
</template>
