<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

defineProps<{
    occurrences: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Painel', href: '/dashboard' },
            { title: 'Ocorrências', href: '/occurrences' },
        ],
    },
});
</script>

<template>
    <Head title="Ocorrências" />

    <div class="flex flex-col gap-8">
        <Heading
            title="Ocorrências"
            description="Acompanhe e atualize ocorrências reportadas pelos inquilinos"
        />

        <Card>
            <CardHeader>
                <CardTitle>Lista de ocorrências</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div
                    v-if="occurrences.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhuma ocorrência registrada.
                </div>

                <div
                    v-for="occurrence in occurrences"
                    :key="occurrence.id"
                    class="space-y-3 rounded-lg border p-4"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-medium">
                                {{ occurrence.property?.name ?? 'Imóvel' }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ occurrence.tenant?.name ?? 'Inquilino' }}
                            </p>
                        </div>
                        <Badge variant="outline">
                            {{ occurrence.status_label ?? occurrence.status }}
                        </Badge>
                    </div>

                    <p class="text-sm">{{ occurrence.description }}</p>

                    <div
                        v-if="(occurrence.photos ?? []).length > 0"
                        class="flex flex-wrap gap-2"
                    >
                        <a
                            v-for="photo in occurrence.photos"
                            :key="photo.id"
                            :href="photo.url"
                            target="_blank"
                            rel="noopener"
                            class="text-sm text-primary underline-offset-4 hover:underline"
                        >
                            {{ photo.file_name }}
                        </a>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Form
                            v-if="occurrence.status !== 'in_review'"
                            :action="`/occurrences/${occurrence.id}`"
                            method="post"
                            #default="{ processing }"
                        >
                            <input type="hidden" name="_method" value="patch" />
                            <input type="hidden" name="status" value="in_review" />
                            <Button type="submit" size="sm" variant="outline" :disabled="processing">
                                Marcar em análise
                            </Button>
                        </Form>

                        <Form
                            v-if="occurrence.status !== 'resolved'"
                            :action="`/occurrences/${occurrence.id}`"
                            method="post"
                            class="flex flex-wrap items-center gap-2"
                            #default="{ processing }"
                        >
                            <input type="hidden" name="_method" value="patch" />
                            <input type="hidden" name="status" value="resolved" />
                            <input
                                type="text"
                                name="resolution_note"
                                placeholder="Observação (opcional)"
                                class="h-8 rounded-md border border-input bg-transparent px-2 text-sm"
                            />
                            <Button type="submit" size="sm" :disabled="processing">
                                Marcar resolvida
                            </Button>
                        </Form>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
