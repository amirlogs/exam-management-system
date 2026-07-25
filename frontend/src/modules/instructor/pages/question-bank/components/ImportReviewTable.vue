<script setup lang="ts">
import { computed, ref } from 'vue'
import BaseCard from '@/shared/components/ui/BaseCard.vue'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import { Pencil, ChevronDown, ChevronUp, AlertTriangle, CheckCircle2, Plus, Trash2, Filter, Check, X } from 'lucide-vue-next'
import type { QuestionRow, QuestionRowData } from '../types'

const props = defineProps<{ rows: QuestionRow[]; isSaving?: boolean }>()
const emit = defineEmits<{
    'update-row': [{ row: number; data: QuestionRowData }]
    'delete-row': [row: number]
}>()

const expandedRow = ref<number | null>(null)
const editingRow = ref<number | null>(null)
const draft = ref<QuestionRowData | null>(null)
const filterInvalidOnly = ref(false)


function safeErrors(row: QuestionRow): string[] {
    return row.errors ?? []
}

function parsedOptions(options: QuestionRowData['options']): string[] {
    if (Array.isArray(options)) return options.map(String)
    if (typeof options === 'string') {
        try {
            const parsed = JSON.parse(options)
            if (Array.isArray(parsed)) return parsed.map(String)
        } catch {
            if (options.includes(',')) return options.split(',').map(s => s.trim()).filter(Boolean)
        }
    }
    return []
}

function toggleExpand(rowNum: number) {
    if (editingRow.value === rowNum) return
    expandedRow.value = expandedRow.value === rowNum ? null : rowNum
}

function startEdit(row: QuestionRow) {
    editingRow.value = row.row
    expandedRow.value = row.row
    draft.value = {
        ...row.data,
        points: Number(row.data.points) || 0,
        options: parsedOptions(row.data.options),
    }
}

function cancelEdit() {
    editingRow.value = null
    draft.value = null
}

function addOption() {
    if (!draft.value) return
    const opts = (draft.value.options as string[]) ?? []
    opts.push('')
    draft.value.options = opts
}

function removeOption(index: number) {
    if (!draft.value) return
    const opts = draft.value.options as string[]
    if (!opts) return
    const removed = opts.splice(index, 1)[0]
    if (draft.value.correct_answer === removed) draft.value.correct_answer = opts[0] || ''
    draft.value.options = opts
}

function saveEdit(row: QuestionRow) {
    if (!draft.value) return
    const isChoiceType = draft.value.type === 'mcq' || draft.value.type === 'true_false'
    const opts = draft.value.options as string[]
    const payload: QuestionRowData = {
        ...draft.value,
        options: isChoiceType ? opts.map(o => o.trim()).filter(Boolean) : null,
    }
    emit('update-row', { row: row.row, data: payload })
    editingRow.value = null
    draft.value = null
}

function hasFieldError(row: QuestionRow, keyword: string): boolean {
    return safeErrors(row).some(e => e.toLowerCase().includes(keyword.toLowerCase()))
}
</script>

<template>
    <BaseCard :padded="false" class="overflow-hidden shadow-sm border border-border">
        <div class="px-5 py-3.5 bg-bg/50 border-b border-border flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-text/60">Total: {{ rows.length }}
                    Questions</span>
                <span class="text-border">|</span>
                <span class="text-xs font-medium text-emerald-600">{{rows.filter(r => r.status === 'valid').length}}
                    Valid</span>
                <span class="text-xs font-medium text-red-600">{{rows.filter(r => r.status === 'invalid').length}}
                    Invalid</span>
            </div>
            <button type="button" @click="filterInvalidOnly = !filterInvalidOnly"
                class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-md border transition-colors"
                :class="filterInvalidOnly ? 'bg-red-50 text-red-700 border-red-200' : 'bg-surface border-border text-text/70 hover:bg-bg'">
                <Filter :size="13" />
                {{ filterInvalidOnly ? 'Showing Invalid Only' : 'Filter Invalid Rows' }}
            </button>
        </div>

        <div class="divide-y divide-border">
            <div v-for="row in (filterInvalidOnly ? rows.filter(r => r.status === 'invalid') : rows)" :key="row.row"
                class="bg-surface">
                <div class="p-4 flex items-center justify-between gap-4 cursor-pointer select-none hover:bg-bg/40"
                    :class="[row.status === 'invalid' ? 'bg-red-500/5' : '', expandedRow === row.row ? 'bg-bg/60 border-b border-border/60' : '']"
                    @click="toggleExpand(row.row)">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <span class="font-mono text-xs font-semibold text-text/40 w-8">#{{ row.row }}</span>
                        <CheckCircle2 v-if="row.status === 'valid'" :size="18" class="text-emerald-500 shrink-0" />
                        <AlertTriangle v-else :size="18" class="text-red-500 shrink-0" />
                        <p class="text-sm font-medium text-text truncate max-w-xl">{{ row.data.text || '(Empty question text) ' }}</p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0" @click.stop>
                        <BaseBadge variant="neutral" class="uppercase text-[10px] tracking-wide">{{ row.data.type ||
                            'UNKNOWN' }}</BaseBadge>
                        <BaseBadge :variant="row.status === 'valid' ? 'success' : 'danger'">
                            {{ row.status === 'valid' ? 'Valid' : `${safeErrors(row).length} Issue(s)` }}
                        </BaseBadge>
                        <button v-if="editingRow !== row.row" type="button" @click="startEdit(row)"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-accent px-2.5 py-1 rounded bg-accent/10 hover:bg-accent/20">
                            <Pencil :size="13" /> Edit
                        </button>
                        <button type="button" @click="$emit('delete-row', row.row)"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-red-600 px-2.5 py-1 rounded bg-red-50 hover:bg-red-100">
                            <Trash2 :size="13" /> Delete
                        </button>
                        <button type="button" @click="toggleExpand(row.row)"
                            class="p-1 text-text/40 hover:text-text rounded">
                            <ChevronUp v-if="expandedRow === row.row" :size="18" />
                            <ChevronDown v-else :size="18" />
                        </button>
                    </div>
                </div>

                <div v-if="expandedRow === row.row" class="p-5 bg-bg/20 border-b border-border">
                    <div v-if="safeErrors(row).length > 0"
                        class="mb-4 p-3.5 rounded-lg bg-red-500/10 border border-red-500/20 text-xs">
                        <div class="font-bold flex items-center gap-1.5 text-red-800 mb-1">
                            <AlertTriangle :size="14" /> Needs correction:
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 pl-1 text-red-600">
                            <li v-for="(err, idx) in safeErrors(row)" :key="idx">{{ err }}</li>
                        </ul>
                    </div>

                    <div v-if="editingRow !== row.row" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pb-3 border-b border-border/50 text-xs">
                            <div><span class="text-text/50">Difficulty:</span> <span
                                    class="ml-1.5 font-semibold uppercase">{{ row.data.difficulty || 'N/A' }}</span>
                            </div>
                            <div><span class="text-text/50">Points:</span> <span class="ml-1.5 font-semibold">{{
                                row.data.points ?? 1 }}</span></div>
                            <div class="md:col-span-2"><span class="text-text/50">Correct Answer:</span> <span
                                    class="ml-1.5 font-semibold text-emerald-600">{{ row.data.correct_answer || 'N/A'
                                    }}</span></div>
                        </div>
                        <div v-if="parsedOptions(row.data.options).length > 0" class="space-y-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-text/50">Choices:</span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div v-for="(opt, idx) in parsedOptions(row.data.options)" :key="idx"
                                    class="p-2.5 rounded-md border text-xs flex items-center justify-between"
                                    :class="opt === row.data.correct_answer ? 'border-emerald-500/40 bg-emerald-500/10 font-medium text-emerald-900' : 'border-border bg-surface text-text/80'">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center"
                                            :class="opt === row.data.correct_answer ? 'bg-emerald-500 text-white' : 'bg-bg text-text/60'">
                                            {{ String.fromCharCode(65 + idx) }}
                                        </span>
                                        <span>{{ opt }}</span>
                                    </div>
                                    <Check v-if="opt === row.data.correct_answer" :size="14" class="text-emerald-600" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="space-y-4 bg-surface p-5 rounded-lg border border-accent/30 shadow-sm">
                        <div class="flex items-center justify-between border-b border-border pb-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-accent">Edit Question</h4>
                            <span class="text-xs text-text/50">Row #{{ row.row }}</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                            <div class="md:col-span-3">
                                <label class="font-semibold text-text/70 mb-1 block">Question Text *</label>
                                <textarea v-model="draft!.text" rows="2"
                                    class="w-full px-3 py-2 rounded-md border bg-bg text-sm focus:outline-none focus:ring-1 focus:ring-accent"
                                    :class="hasFieldError(row, 'text') ? 'border-red-500 bg-red-500/5' : 'border-border'" />
                            </div>
                            <div>
                                <label class="font-semibold text-text/70 mb-1 block">Type</label>
                                <select v-model="draft!.type"
                                    class="w-full px-3 py-2 rounded-md border border-border bg-bg text-sm">
                                    <option value="mcq">Multiple Choice</option>
                                    <option value="true_false">True / False</option>
                                    <option value="essay">Essay</option>
                                    <option value="short_answer">Short Answer</option>
                                </select>
                            </div>
                            <div>
                                <label class="font-semibold text-text/70 mb-1 block">Difficulty</label>
                                <select v-model="draft!.difficulty"
                                    class="w-full px-3 py-2 rounded-md border border-border bg-bg text-sm uppercase">
                                    <option value="easy">Easy</option>
                                    <option value="medium">Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                            <div>
                                <label class="font-semibold text-text/70 mb-1 block">Points</label>
                                <input v-model.number="draft!.points" type="number" step="0.5"
                                    class="w-full px-3 py-2 rounded-md border border-border bg-bg text-sm" />
                            </div>
                            <div v-if="draft!.type === 'mcq' || draft!.type === 'true_false'"
                                class="md:col-span-3 space-y-3 pt-2">
                                <div class="flex items-center justify-between">
                                    <label class="font-semibold text-text/70">Choices & Correct Answer</label>
                                    <button v-if="draft!.type === 'mcq'" type="button" @click="addOption"
                                        class="inline-flex items-center gap-1 text-xs text-accent font-semibold">
                                        <Plus :size="13" /> Add Choice
                                    </button>
                                </div>
                                <div class="space-y-2">
                                    <div v-for="(_, optIdx) in draft!.options" :key="optIdx"
                                        class="flex items-center gap-2">
                                        <input type="radio" :name="`correct_ans_${row.row}`"
                                            :checked="draft!.correct_answer === draft!.options[optIdx]"
                                            @change="draft!.correct_answer = draft!.options[optIdx]"
                                            class="w-4 h-4 accent-accent" />
                                        <input v-model="draft!.options[optIdx]" type="text" placeholder="Option text..."
                                            class="flex-1 px-3 py-1.5 rounded-md border border-border bg-bg text-xs" />
                                        <button v-if="draft!.type === 'mcq' && draft!.options.length > 2" type="button"
                                            @click="removeOption(optIdx)" class="p-1.5 text-text/40 hover:text-red-500">
                                            <Trash2 :size="14" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="md:col-span-3 pt-2">
                                <label class="font-semibold text-text/70 mb-1 block">Expected Answer</label>
                                <textarea v-model="draft!.correct_answer" rows="2"
                                    class="w-full px-3 py-2 rounded-md border border-border bg-bg text-xs" />
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-border">
                            <BaseButton variant="secondary" size="sm" @click="cancelEdit">
                                <X class="w-4 h-4" /> Cancel
                            </BaseButton>
                            <BaseButton size="sm" :disabled="isSaving" @click="saveEdit(row)">
                                {{ isSaving ? 'Saving...' : 'Save Row' }}
                            </BaseButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BaseCard>
</template>
