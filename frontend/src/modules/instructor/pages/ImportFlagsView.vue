<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Flag, CheckCircle2, ArrowLeft, Pencil, X } from 'lucide-vue-next'
import * as api from './question-bank/api'
import BaseCard from '@/shared/components/ui/BaseCard.vue'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import AlertBanner from '@/shared/components/ui/AlertBanner.vue'

const route = useRoute()
const router = useRouter()
const importId = Number(route.params.importId)

const summary = ref<Awaited<ReturnType<typeof api.getImportFlagsSummary>> | null>(null)
const questionsById = ref<Record<number, any>>({})
const isLoading = ref(true)
const loadError = ref<string | null>(null)

const editingFlagId = ref<number | null>(null)
const editDraft = ref<any>(null)
const isSaving = ref(false)
const saveError = ref<string | null>(null)

function parsedOptions(options: unknown): string[] {
    if (Array.isArray(options)) return options.map(String)
    if (typeof options === 'string') {
        try {
            const parsed = JSON.parse(options)
            if (Array.isArray(parsed)) return parsed.map(String)
        } catch { }
    }
    return []
}

async function load() {
    isLoading.value = true
    loadError.value = null
    try {
        const [flagsSummary, questions] = await Promise.all([
            api.getImportFlagsSummary(importId),
            api.getImportQuestions(importId),
        ])
        summary.value = flagsSummary
        questionsById.value = Object.fromEntries(questions.map((q: any) => [q.id, q]))
    } catch (err: any) {
        loadError.value = err?.response?.data?.message || 'Could not load flags.'
    } finally {
        isLoading.value = false
    }
}

function startFix(flag: any) {
    const q = questionsById.value[flag.question_id]
    editingFlagId.value = flag.id
    saveError.value = null

    if (q) {
        editDraft.value = {
            type: q.type,
            text: q.text ?? '',
            difficulty: q.difficulty ?? 'easy',
            points: Number(q.points) || 1,
            options: parsedOptions(q.options),
            correct_answer: q.correct_answer ?? '',
        }
    } else {
        // Fallback if the question wasn't found in the fetched list — still lets them fill it in
        editDraft.value = { type: 'mcq', text: '', difficulty: 'easy', points: 1, options: ['', ''], correct_answer: '' }
    }
}

function cancelFix() {
    editingFlagId.value = null
    editDraft.value = null
    saveError.value = null
}

async function submitFix(flag: any) {
    if (!editDraft.value) return
    isSaving.value = true
    saveError.value = null
    try {
        const isChoiceType = editDraft.value.type === 'mcq' || editDraft.value.type === 'true_false'
        const payload = {
            ...editDraft.value,
            options: isChoiceType ? editDraft.value.options.filter((o: string) => o.trim()) : null,
        }
        await api.updateQuestionAndResolveFlag(flag.question_id, flag.id, payload)
        editingFlagId.value = null
        editDraft.value = null
        await load()
    } catch (err: any) {
        const data = err?.response?.data
        saveError.value =
            typeof data?.message === 'string' ? data.message
                : typeof data?.errors?.message === 'string' ? data.errors.message
                    : 'Could not save changes. Please try again.'
    } finally {
        isSaving.value = false
    }
}

onMounted(load)
</script>

<template>
    <div class="space-y-6">
        <button @click="router.back()"
            class="inline-flex items-center gap-1.5 text-sm text-text/60 hover:text-accent transition-colors">
            <ArrowLeft class="w-4 h-4" /> Back
        </button>

        <div>
            <h1 class="font-display text-2xl text-accent">Flags</h1>
            <p class="text-sm text-text/60 mt-1">Import #{{ importId }}</p>
        </div>

        <div v-if="isLoading" class="bg-surface border border-border rounded-lg p-12 text-center text-text/50">Loading…
        </div>
        <div v-else-if="loadError" class="bg-surface border border-border rounded-lg p-12 text-center text-red-600">{{
            loadError }}</div>

        <template v-else-if="summary">
            <div class="flex items-center gap-6 bg-surface border border-border rounded-lg p-5">
                <div class="flex items-center gap-2 text-red-600">
                    <Flag class="w-5 h-5" /><span class="font-semibold">{{ summary.open_count }} open</span>
                </div>
                <div class="flex items-center gap-2 text-emerald-600">
                    <CheckCircle2 class="w-5 h-5" /><span class="font-semibold">{{ summary.resolved_count }}
                        resolved</span>
                </div>
            </div>

            <div v-if="summary.flags.length === 0"
                class="bg-surface border border-border rounded-lg p-12 text-center text-text/50">
                No flags have been raised on this import.
            </div>

            <BaseCard v-else :padded="false">
                <div v-for="flag in summary.flags" :key="flag.id" class="border-b border-border last:border-0">
                    <div class="p-5 flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3 min-w-0">
                            <Flag class="w-4 h-4 text-red-500 mt-1 shrink-0" />
                            <div class="min-w-0">
                                <p class="text-xs text-text/40 mb-1">
                                    Question #{{ flag.question_id }}
                                    <span v-if="questionsById[flag.question_id]" class="text-text/60">
                                        — {{ questionsById[flag.question_id].text }}
                                    </span>
                                </p>
                                <p class="text-sm text-text">{{ flag.comment }}</p>
                                <p class="text-xs text-text/40 mt-1">{{ new Date(flag.created_at).toLocaleString() }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <BaseBadge :variant="flag.status === 'open' ? 'danger' : 'success'">{{ flag.status }}
                            </BaseBadge>
                            <BaseButton v-if="flag.status === 'open' && editingFlagId !== flag.id" variant="secondary"
                                @click="startFix(flag)">
                                <template #icon>
                                    <Pencil class="w-4 h-4" />
                                </template>
                                Review & Resolve
                            </BaseButton>
                        </div>
                    </div>

                    <!-- Inline fix form, pre-filled with the real question data -->
                    <div v-if="editingFlagId === flag.id" class="px-5 pb-5 bg-bg/30">
                        <div class="bg-surface p-4 rounded-lg border border-border space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-accent">Fix Question #{{
                                    flag.question_id }}</h4>
                                <button @click="cancelFix" class="text-text/40 hover:text-text">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                                <div class="md:col-span-2">
                                    <label class="font-semibold text-text/70 mb-1 block">Question Text</label>
                                    <textarea v-model="editDraft.text" rows="2"
                                        class="w-full px-3 py-2 rounded-md border border-border bg-bg text-sm" />
                                </div>
                                <div>
                                    <label class="font-semibold text-text/70 mb-1 block">Type</label>
                                    <select v-model="editDraft.type"
                                        class="w-full px-3 py-2 rounded-md border border-border bg-bg text-sm">
                                        <option value="mcq">MCQ</option>
                                        <option value="true_false">True/False</option>
                                        <option value="essay">Essay</option>
                                        <option value="short_answer">Short Answer</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="font-semibold text-text/70 mb-1 block">Difficulty</label>
                                    <select v-model="editDraft.difficulty"
                                        class="w-full px-3 py-2 rounded-md border border-border bg-bg text-sm">
                                        <option value="easy">Easy</option>
                                        <option value="medium">Medium</option>
                                        <option value="hard">Hard</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="font-semibold text-text/70 mb-1 block">Points</label>
                                    <input v-model.number="editDraft.points" type="number" step="0.5"
                                        class="w-full px-3 py-2 rounded-md border border-border bg-bg text-sm" />
                                </div>
                                <div v-if="editDraft.type === 'mcq' || editDraft.type === 'true_false'">
                                    <label class="font-semibold text-text/70 mb-1 block">Options
                                        (comma-separated)</label>
                                    <input :value="editDraft.options.join(', ')"
                                        @input="editDraft.options = ($event.target as HTMLInputElement).value.split(',').map((s: string) => s.trim())"
                                        class="w-full px-3 py-2 rounded-md border border-border bg-bg text-sm" />
                                </div>
                                <div v-if="editDraft.type === 'mcq' || editDraft.type === 'true_false'">
                                    <label class="font-semibold text-text/70 mb-1 block">Correct Answer</label>
                                    <input v-model="editDraft.correct_answer"
                                        class="w-full px-3 py-2 rounded-md border border-border bg-bg text-sm" />
                                </div>
                            </div>

                            <AlertBanner v-if="saveError" variant="danger">{{ saveError }}</AlertBanner>

                            <div class="flex justify-end gap-2 pt-2 border-t border-border">
                                <BaseButton variant="secondary" :disabled="isSaving" @click="cancelFix">Cancel
                                </BaseButton>
                                <BaseButton :disabled="isSaving" @click="submitFix(flag)">
                                    {{ isSaving ? 'Saving…' : 'Save & Resolve' }}
                                </BaseButton>
                            </div>
                        </div>
                    </div>
                </div>
            </BaseCard>
        </template>
    </div>
</template>
