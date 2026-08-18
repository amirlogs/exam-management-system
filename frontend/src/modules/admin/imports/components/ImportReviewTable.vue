<script setup lang="ts">
import { ref, computed } from 'vue'
import { CheckCircle2, AlertTriangle, Pencil, Trash2, X, Check, Filter } from 'lucide-vue-next'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import type { ImportRowMap } from '../types/import'

const props = defineProps<{ rows: ImportRowMap; savingRow: string | null; readonly?: boolean }>()
const emit = defineEmits<{ 'update-row': [{ row: string; data: Record<string, any> }]; 'delete-row': [row: string] }>()

const filterInvalidOnly = ref(false)
const editingRow = ref<string | null>(null)
const draft = ref<Record<string, any>>({})

const entries = computed(() => {
    const all = Object.entries(props.rows)
    return filterInvalidOnly.value ? all.filter(([, r]) => r.status === 'invalid') : all
})
const columns = computed(() => {
    const first = Object.values(props.rows)[0]
    return first ? Object.keys(first.data) : []
})

function startEdit(rowKey: string, data: Record<string, any>) {
    editingRow.value = rowKey
    draft.value = { ...data }
}
function cancelEdit() {
    editingRow.value = null
    draft.value = {}
}
function saveEdit(rowKey: string) {
    emit('update-row', { row: rowKey, data: { ...draft.value } })
    editingRow.value = null
}
</script>

<template>
    <div class="overflow-hidden rounded-md border border-border bg-surface">
        <div class="flex items-center justify-between border-b border-border bg-bg/50 px-5 py-3">
            <div class="flex items-center gap-3 text-xs">
                <span class="font-semibold uppercase tracking-wide text-text/60">{{ Object.keys(rows).length }} rows</span>
                <span class="text-border">|</span>
                <span class="font-medium text-success">{{ Object.values(rows).filter(r => r.status === 'valid').length }} valid</span>
                <span v-if="!readonly" class="font-medium text-error">{{ Object.values(rows).filter(r => r.status === 'invalid').length }} invalid</span>
            </div>
            <button
                v-if="!readonly" type="button"
                class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs font-semibold transition-colors"
                :class="filterInvalidOnly ? 'border-error/30 bg-error/10 text-error' : 'border-border bg-surface text-text/70 hover:bg-bg'"
                @click="filterInvalidOnly = !filterInvalidOnly"
            >
                <Filter class="h-3.5 w-3.5" />
                {{ filterInvalidOnly ? 'Showing invalid only' : 'Filter invalid rows' }}
            </button>
        </div>

        <div class="divide-y divide-border">
            <div v-for="[rowKey, row] in entries" :key="rowKey" :class="row.status === 'invalid' ? 'bg-error/5' : ''">
                <div class="flex items-center justify-between gap-4 p-4">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <span class="w-8 shrink-0 font-mono text-xs font-semibold text-text/40">#{{ rowKey }}</span>
                        <CheckCircle2 v-if="row.status === 'valid'" class="h-4 w-4 shrink-0 text-success" />
                        <AlertTriangle v-else class="h-4 w-4 shrink-0 text-error" />
                        <p class="truncate text-sm text-text">{{ Object.values(row.data)[0] || '(empty)' }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <BaseBadge :variant="row.status === 'valid' ? 'success' : 'danger'">
                            {{ row.status === 'valid' ? 'Valid' : `${row.errors.length} issue(s)` }}
                        </BaseBadge>
                        <template v-if="!readonly">
                            <button v-if="editingRow !== rowKey" type="button" class="rounded-md bg-accent/10 px-2.5 py-1 text-xs font-semibold text-accent hover:bg-accent/20" @click="startEdit(rowKey, row.data)">
                                <Pencil class="mr-1 inline h-3.5 w-3.5" />Edit
                            </button>
                            <button type="button" class="rounded-md bg-error/10 px-2.5 py-1 text-xs font-semibold text-error hover:bg-error/20" @click="emit('delete-row', rowKey)">
                                <Trash2 class="mr-1 inline h-3.5 w-3.5" />Delete
                            </button>
                        </template>
                    </div>
                </div>

                <div v-if="!readonly && row.errors.length" class="mx-4 mb-3 rounded-md border border-error/20 bg-error/10 p-3 text-xs">
                    <div class="mb-1 flex items-center gap-1.5 font-bold text-error"><AlertTriangle class="h-3.5 w-3.5" /> Needs correction:</div>
                    <ul class="list-inside list-disc space-y-0.5 pl-1 text-error/90">
                        <li v-for="(err, i) in row.errors" :key="i">{{ err }}</li>
                    </ul>
                </div>

                <div v-if="!readonly && editingRow === rowKey" class="mx-4 mb-4 space-y-3 rounded-md border border-accent/30 bg-bg p-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div v-for="col in columns" :key="col">
                            <label class="mb-1 block text-xs font-semibold text-text/60">{{ col }}</label>
                            <input v-model="draft[col]" type="text" class="w-full rounded-md border border-border bg-surface px-3 py-1.5 text-sm text-text outline-none focus:border-accent" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-border pt-3">
                        <BaseButton variant="secondary" @click="cancelEdit"><template #icon><X class="h-4 w-4" /></template>Cancel</BaseButton>
                        <BaseButton :loading="savingRow === rowKey" @click="saveEdit(rowKey)"><template #icon><Check class="h-4 w-4" /></template>Save row</BaseButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
