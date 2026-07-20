<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Building2, LayoutGrid, List, MapPin, Pencil, Trash2, UserRound } from '@lucide/vue';
import { onMounted, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import PropertyCover from '@/components/PropertyCover.vue';
import TableActionButton from '@/components/TableActionButton.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import AppPagination from '@/components/AppPagination.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { create, destroy, edit, index } from '@/routes/admin/properties';
import type { Paginated } from '@/types';

type PropertyOwner = {
    id: number;
    name: string;
};

type PropertyItem = {
    id: number;
    name: string;
    address?: string | null;
    type?: string | null;
    type_label?: string | null;
    status?: string | null;
    cover_url?: string | null;
    owners?: PropertyOwner[];
};

type ViewMode = 'grid' | 'list';

const VIEW_STORAGE_KEY = 'properties-view-mode';

defineProps<{
    properties: Paginated<PropertyItem>;
}>();

const viewMode = ref<ViewMode>('grid');

onMounted(() => {
    const stored = localStorage.getItem(VIEW_STORAGE_KEY);
    if (stored === 'grid' || stored === 'list') {
        viewMode.value = stored;
    }
});

watch(viewMode, (mode) => {
    localStorage.setItem(VIEW_STORAGE_KEY, mode);
});

function ownerNames(property: PropertyItem): string {
    return property.owners?.length
        ? property.owners.map((owner) => owner.name).join(', ')
        : 'Sem proprietário';
}

function setViewMode(mode: ViewMode): void {
    viewMode.value = mode;
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Imóveis', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="Imóveis" />

    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading
                title="Imóveis"
                description="Gerencie os imóveis cadastrados"
            />
            <Button as-child class="shrink-0 self-start sm:self-auto">
                <Link :href="create()">Novo imóvel</Link>
            </Button>
        </div>

        <div
            v-if="properties.data.length === 0"
            class="flex flex-col items-center justify-center gap-4 rounded-xl border border-dashed border-border bg-muted/30 px-6 py-20 text-center"
        >
            <div
                class="flex size-14 items-center justify-center rounded-xl bg-muted text-muted-foreground"
            >
                <Building2 class="size-7" />
            </div>
            <div class="space-y-1">
                <p class="text-base font-medium text-foreground">
                    Nenhum imóvel cadastrado
                </p>
                <p class="max-w-sm text-sm text-muted-foreground">
                    Cadastre o primeiro imóvel para começar a gerenciar
                    contratos, cobranças e proprietários.
                </p>
            </div>
            <Button as-child>
                <Link :href="create()">Cadastrar imóvel</Link>
            </Button>
        </div>

        <template v-else>
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-muted-foreground">
                    {{ properties.total }}
                    {{ properties.total === 1 ? 'imóvel' : 'imóveis' }}
                </p>
                <div
                    class="inline-flex items-center rounded-lg border border-border bg-background p-0.5"
                    role="group"
                    aria-label="Modo de visualização"
                >
                    <Button
                        type="button"
                        size="icon-sm"
                        :variant="viewMode === 'grid' ? 'secondary' : 'ghost'"
                        aria-label="Grade"
                        :aria-pressed="viewMode === 'grid'"
                        @click="setViewMode('grid')"
                    >
                        <LayoutGrid />
                    </Button>
                    <Button
                        type="button"
                        size="icon-sm"
                        :variant="viewMode === 'list' ? 'secondary' : 'ghost'"
                        aria-label="Lista"
                        :aria-pressed="viewMode === 'list'"
                        @click="setViewMode('list')"
                    >
                        <List />
                    </Button>
                </div>
            </div>

            <!-- Grid: fixed card height + fixed cover height -->
            <div
                v-if="viewMode === 'grid'"
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
            >
                <article
                    v-for="property in properties.data"
                    :key="property.id"
                    class="group flex h-84 flex-col overflow-hidden rounded-xl border border-border bg-card transition-colors hover:border-primary/30"
                >
                    <div class="relative h-40 w-full shrink-0">
                        <PropertyCover
                            :src="property.cover_url"
                            :alt="property.name"
                            class="size-full"
                        />
                        <div class="absolute left-3 top-3">
                            <StatusBadge
                                type="property"
                                :status="property.status"
                                class="bg-background/95 shadow-sm"
                            />
                        </div>
                    </div>

                    <div class="flex min-h-0 flex-1 flex-col gap-3 p-4">
                        <div class="min-h-0 flex-1 space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <h2
                                    class="truncate text-base font-semibold leading-snug text-foreground"
                                    :title="property.name"
                                >
                                    {{ property.name }}
                                </h2>
                                <span
                                    v-if="property.type_label"
                                    class="shrink-0 rounded-md bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                                >
                                    {{ property.type_label }}
                                </span>
                            </div>

                            <p
                                class="flex h-5 items-center gap-1.5 text-sm text-muted-foreground"
                            >
                                <MapPin class="size-3.5 shrink-0" />
                                <span
                                    class="truncate"
                                    :title="property.address ?? undefined"
                                >
                                    {{ property.address ?? 'Endereço não informado' }}
                                </span>
                            </p>

                            <p
                                class="flex h-5 items-center gap-1.5 text-xs text-muted-foreground"
                            >
                                <UserRound class="size-3.5 shrink-0" />
                                <span
                                    class="truncate"
                                    :title="ownerNames(property)"
                                >
                                    {{ ownerNames(property) }}
                                </span>
                            </p>
                        </div>

                        <div
                            class="flex h-9 shrink-0 items-center gap-1 border-t border-border pt-3"
                        >
                            <TableActionButton label="Editar" as-child>
                                <Link :href="edit(property)">
                                    <Pencil />
                                    <span class="sr-only">Editar</span>
                                </Link>
                            </TableActionButton>
                            <Form
                                v-bind="destroy.form(property)"
                                #default="{ processing }"
                            >
                                <TableActionButton
                                    label="Excluir"
                                    :icon="Trash2"
                                    type="submit"
                                    variant="destructive"
                                    :disabled="processing"
                                />
                            </Form>
                        </div>
                    </div>
                </article>
            </div>

            <!-- List: fixed row height + fixed thumb size -->
            <div v-else class="flex flex-col gap-2">
                <article
                    v-for="property in properties.data"
                    :key="property.id"
                    class="flex h-24 items-center gap-4 overflow-hidden rounded-xl border border-border bg-card px-3 transition-colors hover:border-primary/30"
                >
                    <PropertyCover
                        :src="property.cover_url"
                        :alt="property.name"
                        class="size-18 rounded-lg [&_svg]:size-6"
                    />

                    <div class="flex min-w-0 flex-1 flex-col justify-center gap-1">
                        <div class="flex min-w-0 items-center gap-2">
                            <h2
                                class="min-w-0 truncate text-sm font-semibold text-foreground"
                                :title="property.name"
                            >
                                {{ property.name }}
                            </h2>
                            <StatusBadge
                                type="property"
                                :status="property.status"
                                class="shrink-0"
                            />
                        </div>

                        <p
                            class="flex h-4 min-w-0 items-center gap-1.5 text-sm text-muted-foreground"
                        >
                            <MapPin class="size-3.5 shrink-0" />
                            <span
                                class="truncate"
                                :title="property.address ?? undefined"
                            >
                                {{ property.address ?? 'Endereço não informado' }}
                            </span>
                        </p>

                        <p
                            class="flex h-4 min-w-0 items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <UserRound class="size-3 shrink-0" />
                            <span
                                class="truncate"
                                :title="ownerNames(property)"
                            >
                                {{ ownerNames(property) }}
                            </span>
                            <template v-if="property.type_label">
                                <span class="text-border" aria-hidden="true">·</span>
                                <span class="shrink-0">{{ property.type_label }}</span>
                            </template>
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-1">
                        <TableActionButton label="Editar" as-child>
                            <Link :href="edit(property)">
                                <Pencil />
                                <span class="sr-only">Editar</span>
                            </Link>
                        </TableActionButton>
                        <Form
                            v-bind="destroy.form(property)"
                            #default="{ processing }"
                        >
                            <TableActionButton
                                label="Excluir"
                                :icon="Trash2"
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                            />
                        </Form>
                    </div>
                </article>
            </div>

            <AppPagination :paginator="properties" />
        </template>
    </div>
</template>
