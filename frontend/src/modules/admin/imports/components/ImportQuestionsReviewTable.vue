<script setup lang="ts">
import { ref, computed } from 'vue'
import { CheckCircle2, AlertTriangle, Pencil, Trash2, X, Check, Plus, Filter } from 'lucide-vue-next'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import type { ImportRowMap } from '../types/import'

const props = defineProps<{ rows: ImportRowMap; savingRow: string | null; readonly?: boolean }>()
const emit = defineEmits<{ 'update-row': [{ row: string; data: Record<string, any> }]; 'delete-row': [row: string] }>()

const filterInvalidOnly = ref(false)
const expandedRow = ref<string | null>(null)
const editingRow = ref<string | null>(null)
const draft = ref<Record<string, any>>({})

const entries = computed(() => {
    const all = Object.entries(props.rows)
    return filterInvalidOnly.value ? all.filter(([, r]) => r.status === 'invalid') : all
})

function parsedOptions(options: unknown): string[] {
    if (Array.isArray(options)) return options.map(String)
    if (typeof options === 'string') {
        try {
            const parsed = JSON.parse(options)
            if (Array.isArray(parsed)) return parsed.map(String)
        } catch { /* fall through */ }
    }
    return []
}

function isChoiceType(type: string) {
    return type === 'MCQ' || type === 'TRUE_FALSE'
}

function toggleExpand(rowKey: string) {
    if (editingRow.value === rowKey) return
    expandedRow.value = expandedRow.value === rowKey ? null : rowKey
}

function startEdit(rowKey: string, data: Record<string, any>) {
    editingRow.value = rowKey
    expandedRow.value = rowKey
    draft.value = { ...data, options: parsedOptions(data.options) }
}
function cancelEdit() {
    editingRow.value = null
    draft.value = {}
}
function addOption() {
    draft.value.options = [...(draft.value.options ?? []), '']
}
function removeOption(idx: number) {
    const opts = [...draft.value.options]
    const removed = opts.splice(idx, 1)[0]
    if (draft.value.correct_answer === removed) draft.value.correct_answer = opts[0] ?? ''
    draft.value.options = opts
}
function saveEdit(rowKey: string) {
    const payload = {
        ...draft.value,
        options: isChoiceType(draft.value.type) ? (draft.value.options as string[]).map((o) => o.trim()).filter(Boolean) : null,
    }
    emit('update-row', { row: rowKey, data: payload })
    editingRow.value = null
}

function hasFieldError(errors: string[], keyword: string) {
    return errors.some((e) => e.toLowerCase().includes(keyword.toLowerCase()))
}
</script>

<template>
    <div class="overflow-hidden rounded-md border border-border bg-surface">
        <div class="flex items-center justify-between border-b border-border bg-bg/50 px-5 py-3">
            <div class="flex items-center gap-3 text-xs">
                <span class="font-semibold uppercase tracking-wide text-text/60">{{ Object.keys(rows).length }}
                    questions</span>
                <span class="text-border">|</span>
                <span class="font-medium text-success">{{Object.values(rows).filter(r => r.status === 'valid').length
                    }} valid</span>
                <span v-if="!readonly" class="font-medium text-error">{{Object.values(rows).filter(r => r.status ===
                    'invalid').length }} invalid</span>
            </div>
            <button v-if="!readonly" type="button"
                class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs font-semibold transition-colors"
                :class="filterInvalidOnly ? 'border-error/30 bg-error/10 text-error' : 'border-border bg-surface text-text/70 hover:bg-bg'"
                @click="filterInvalidOnly = !filterInvalidOnly">
                <Filter class="h-3.5 w-3.5" />
                {{ filterInvalidOnly ? 'Showing invalid only' : 'Filter invalid rows' }}
            </button>
        </div>

        <div class="divide-y divide-border">
            <div v-for="[rowKey, row] in entries" :key="rowKey" :class="row.status === 'invalid' ? 'bg-error/5' : ''">
                <!-- Collapsed row: click anywhere to see the full question -->
                <div class="flex cursor-pointer items-center justify-between gap-4 p-4 hover:bg-text/2"
                    @click="toggleExpand(rowKey)">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <span class="w-8 shrink-0 font-mono text-xs font-semibold text-text/40">#{{ rowKey }}</span>
                        <CheckCircle2 v-if="row.status === 'valid'" class="h-4 w-4 shrink-0 text-success" />
                        <AlertTriangle v-else class="h-4 w-4 shrink-0 text-error" />
                        <p class="truncate text-sm text-text">{{ row.data.content || '(empty question text)' }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2" @click.stop>
                        <BaseBadge variant="neutral" class="text-[10px] uppercase">{{ row.data.type || 'unknown' }}
                        </BaseBadge>
                        <BaseBadge :variant="row.status === 'valid' ? 'success' : 'danger'">
                            {{ row.status === 'valid' ? 'Valid' : `${row.errors.length} issue(s)` }}
                        </BaseBadge>
                        <template v-if="!readonly">
                            <button v-if="editingRow !== rowKey" type="button"
                                class="rounded-md bg-accent/10 px-2.5 py-1 text-xs font-semibold text-accent hover:bg-accent/20"
                                @click="startEdit(rowKey, row.data)">
                                <Pencil class="mr-1 inline h-3.5 w-3.5" />Edit
                            </button>
                            <button type="button"
                                class="rounded-md bg-error/10 px-2.5 py-1 text-xs font-semibold text-error hover:bg-error/20"
                                @click="emit('delete-row', rowKey)">
                                <Trash2 class="mr-1 inline h-3.5 w-3.5" />Delete
                            </button>
                        </template>
                        <button type="button" class="text-text/40 hover:text-text" @click="toggleExpand(rowKey)">
                            {{ expandedRow === rowKey ? '▲' : '▼' }}
                        </button>
                    </div>
                </div>

                <!-- Expanded: full question detail, works for valid AND invalid rows -->
                <div v-if="expandedRow === rowKey" class="border-t border-border bg-bg/20 p-5">
                    <div v-if="row.errors.length"
                        class="mb-4 rounded-md border border-error/20 bg-error/10 p-3 text-xs">
                        <div class="mb-1 flex items-center gap-1.5 font-bold text-error">
                            <AlertTriangle class="h-3.5 w-3.5" /> Needs correction:
                        </div>
                        <ul class="list-inside list-disc space-y-0.5 pl-1 text-error/90">
                            <li v-for="(err, i) in row.errors" :key="i">{{ err }}</li>
                        </ul>
                    </div>

                    <!-- View mode -->
                    <div v-if="editingRow !== rowKey" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 border-b border-border/50 pb-3 text-xs md:grid-cols-3">
                            <div><span class="text-text/50">Difficulty:</span> <span
                                    class="ml-1.5 font-semibold uppercase">{{
                                    row.data.difficulty || 'N/A' }}</span></div>
                            <div><span class="text-text/50">Chapter:</span> <span class="ml-1.5 font-semibold">{{
                                    row.data.chapter ||
                                    'N/A' }}</span></div>
                            <div v-if="!isChoiceType(row.data.type)"><span class="text-text/50">Expected answer:</span>
                                <span class="ml-1.5 font-semibold text-success">{{ row.data.correct_answer || 'N/A'
                                    }}</span></div>
                        </div>

                        <div v-if="isChoiceType(row.data.type)" class="space-y-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-text/50">Choices</span>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <div v-for="(opt, idx) in parsedOptions(row.data.options)" :key="idx"
                                    class="flex items-center justify-between rounded-md border p-2.5 text-xs"
                                    :class="opt === row.data.correct_answer ? 'border-success/40 bg-success/10 font-medium text-success' : 'border-border bg-surface text-text/80'">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold"
                                            :class="opt === row.data.correct_answer ? 'bg-success text-white' : 'bg-bg text-text/60'">
                                            {{ String.fromCharCode(65 + idx) }}
                                        </span>
                                        <span>{{ opt }}</span>
                                    </div>
                                    <Check v-if="opt === row.data.correct_answer" class="h-3.5 w-3.5 text-success" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit mode -->
                    <div v-else class="space-y-4 rounded-md border border-accent/30 bg-surface p-5">
                        <div class="flex items-center justify-between border-b border-border pb-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-accent">Edit question</h4>
                            <span class="text-xs text-text/50">Row #{{ rowKey }}</span>
                        </div>

                        <div class="grid grid-cols-1 gap-4 text-xs md:grid-cols-3">
                            <div class="md:col-span-3">
                                <label class="mb-1 block font-semibold text-text/70">Question text *</label>
                                <textarea v-model="draft.content" rows="2"
                                    class="w-full rounded-md border bg-bg px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-accent"
                                    :class="hasFieldError(row.errors, 'content') ? 'border-error bg-error/5' : 'border-border'" />
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold text-text/70">Type</label>
                                <select v-model="draft.type"
                                    class="w-full rounded-md border border-border bg-bg px-3 py-2 text-sm">
                                    <option value="MCQ">Multiple choice</option>
                                    <option value="TRUE_FALSE">True / False</option>
                                    <option value="ESSAY">Essay</option>
                                    <option value="SHORT_ANSWER">Short answer</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold text-text/70">Difficulty</label>
                                <select v-model="draft.difficulty"
                                    class="w-full rounded-md border border-border bg-bg px-3 py-2 text-sm uppercase">
                                    <option value="easy">Easy</option>
                                    <option value="medium">Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold text-text/70">Chapter</label>
                                <input v-model="draft.chapter" type="text"
                                    class="w-full rounded-md border border-border bg-bg px-3 py-2 text-sm" />
                            </div>

                            <div v-if="isChoiceType(draft.type)" class="space-y-3 pt-2 md:col-span-3">
                                <div class="flex items-center justify-between">
                                    <label class="font-semibold text-text/70">Choices & correct answer</label>
                                    <button v-if="draft.type === 'MCQ'" type="button"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-accent"
                                        @click="addOption">
                                        <Plus class="h-3.5 w-3.5" /> Add choice
                                    </button>
                                </div>
                                <div class="space-y-2">
                                    <div v-for="(_, idx) in draft.options" :key="idx" class="flex items-center gap-2">
                                        <input type="radio" :checked="draft.correct_answer === draft.options[idx]"
                                            class="h-4 w-4 accent-accent"
                                            @change="draft.correct_answer = draft.options[idx]" />
                                        <input v-model="draft.options[idx]" type="text" placeholder="Option text..."
                                            class="flex-1 rounded-md border border-border bg-bg px-3 py-1.5 text-xs" />
                                        <button v-if="draft.type === 'MCQ' && draft.options.length > 2" type="button"
                                            class="p-1.5 text-text/40 hover:text-error" @click="removeOption(idx)">
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="pt-2 md:col-span-3">
                                <label class="mb-1 block font-semibold text-text/70">Expected answer</label>
                                <textarea v-model="draft.correct_answer" rows="2"
                                    class="w-full rounded-md border border-border bg-bg px-3 py-2 text-xs" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 border-t border-border pt-3">
                            <BaseButton variant="secondary" @click="cancelEdit"><template #icon>
                                    <X class="h-4 w-4" />
                                </template>Cancel
                            </BaseButton>
                            <BaseButton :loading="savingRow === rowKey" @click="saveEdit(rowKey)"><template #icon>
                                    <Check class="h-4 w-4" />
                                </template>Save row</BaseButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
