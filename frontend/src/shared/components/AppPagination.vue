<script setup lang="ts">
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

export interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

defineProps<{
    pagination: Pagination
}>()

const emit = defineEmits<{
    changePage: [page: number]
}>()
</script>

<template>
    <div v-if="pagination.total > 0"
        class="flex flex-col gap-3 border-t border-border px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-text/50">
            Showing
            <span class="font-mono tabular-nums">{{ pagination.from }}</span>
            to
            <span class="font-mono tabular-nums">{{ pagination.to }}</span>
            of
            <span class="font-mono tabular-nums">{{ pagination.total }}</span>
        </p>

        <div class="flex items-center gap-1">
            <button type="button"
                class="flex h-8 w-8 items-center justify-center rounded-md border border-border text-text/50 transition hover:bg-text/5 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="pagination.current_page === 1" @click="emit('changePage', pagination.current_page - 1)">
                <ChevronLeft class="h-4 w-4" />
            </button>

            <button v-for="page in pagination.last_page" :key="page" type="button" :class="[
                'flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-xs font-medium transition',
                page === pagination.current_page
                    ? 'border-accent bg-accent/5 text-accent'
                    : 'border-border text-text/60 hover:bg-text/5',
            ]" @click="emit('changePage', page)">
                {{ page }}
            </button>

            <button type="button"
                class="flex h-8 w-8 items-center justify-center rounded-md border border-border text-text/50 transition hover:bg-text/5 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="pagination.current_page === pagination.last_page"
                @click="emit('changePage', pagination.current_page + 1)">
                <ChevronRight class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>
