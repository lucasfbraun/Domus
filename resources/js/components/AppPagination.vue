<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import type { Paginated } from '@/types';

const props = withDefaults(
    defineProps<{
        paginator: Pick<
            Paginated<unknown>,
            'current_page' | 'last_page' | 'per_page' | 'total' | 'path'
        >;
        pageName?: string;
        only?: string[];
    }>(),
    {
        pageName: 'page',
    },
);

function visitPage(page: number): void {
    if (
        page === props.paginator.current_page ||
        page < 1 ||
        page > props.paginator.last_page
    ) {
        return;
    }

    const params = Object.fromEntries(
        new URLSearchParams(window.location.search),
    );
    params[props.pageName] = String(page);

    router.get(props.paginator.path, params, {
        preserveState: true,
        preserveScroll: true,
        ...(props.only ? { only: props.only } : {}),
    });
}
</script>

<template>
    <Pagination
        v-if="paginator.last_page > 1"
        v-slot="{ page }"
        :page="paginator.current_page"
        :items-per-page="paginator.per_page"
        :total="paginator.total"
        :sibling-count="1"
        show-edges
        class="mt-4"
        @update:page="visitPage"
    >
        <PaginationContent v-slot="{ items }" class="flex-wrap justify-center">
            <PaginationPrevious>
                <ChevronLeft />
                <span class="hidden sm:block">Anterior</span>
            </PaginationPrevious>

            <template v-for="(item, index) in items" :key="index">
                <PaginationItem
                    v-if="item.type === 'page'"
                    :value="item.value"
                    :is-active="item.value === page"
                >
                    {{ item.value }}
                </PaginationItem>
                <PaginationEllipsis v-else />
            </template>

            <PaginationNext>
                <span class="hidden sm:block">Próxima</span>
                <ChevronRight />
            </PaginationNext>
        </PaginationContent>
    </Pagination>
</template>
