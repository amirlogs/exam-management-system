<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Plus, Upload, Edit, Archive, RotateCcw, MoreVertical } from 'lucide-vue-next'
import ResourceToolbar from '@/shared/components/ResourceToolbar.vue'
import BaseCard from '@/shared/components/ui/BaseCard.vue'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseDialog from '@/shared/components/ui/BaseDialog.vue'
import BaseSelect from '@/shared/components/ui/BaseSelect.vue'
import ConfirmModal from '@/shared/components/ConfirmModal.vue'
import AppPagination from '@/shared/components/AppPagination.vue'
import QuestionFormFields from '../components/QuestionFormFields.vue'
import * as questionsApi from '../api/questions'
import { useResourceForm } from '@/shared/composables/useResourceForm'
import { questionSchema } from '../schemas/question.schema'
import { useUiStore } from '@/stores/ui'
import type { Question } from '../../exams/types/exam'
import TableRowActions from '@/shared/components/TableRowActions.vue'

const route = useRoute()
const router = useRouter()
const uiStore = useUiStore()
const courseId = computed(() => Number(route.params.courseId))

const questions = ref<Question[]>([])
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0, from: null, to: null })
const loading = ref(false)
const refreshing = ref(false)
const search = ref('')
const activeView = ref<'active' | 'archived'>('active')
const filterType = ref('')
const filterChapter = ref('')
const filterDifficulty = ref('')

const typeOptions = [
    { value: '', label: 'All types' },
    { value: 'mcq', label: 'MCQ' },
    { value: 'true_false', label: 'True/False' },
    { value: 'short_answer', label: 'Short Answer' },
    { value: 'essay', label: 'Essay' },
]
const difficultyOptions = [
    { value: '', label: 'Any difficulty' },
    { value: 'easy', label: 'Easy' },
    { value: 'medium', label: 'Medium' },
    { value: 'hard', label: 'Hard' },
]

const typeBadgeVariant: Record<string, 'neutral' | 'info' | 'success' | 'warning'> = {
    mcq: 'info', true_false: 'neutral', short_answer: 'warning', essay: 'success',
}

async function load(page = 1) {
    loading.value = true
    try {
        const filters: Record<string, any> = { course_id: courseId.value }
        if (search.value) filters.content = search.value
        if (filterType.value) filters.type = filterType.value
        if (filterChapter.value) filters.chapter = filterChapter.value
        if (filterDifficulty.value) filters.difficulty = filterDifficulty.value

        const res = activeView.value === 'archived'
            ? await questionsApi.getArchivedQuestions(filters, page)
            : await questionsApi.getQuestions(filters, page)
        questions.value = res.data
        pagination.value = res.pagination
    } catch (err: any) {
        uiStore.showToast(err?.response?.data?.message || 'Failed to load questions.', 'error')
    } finally {
        loading.value = false
    }
}
async function handleRefresh() { refreshing.value = true; await load(pagination.value.current_page); refreshing.value = false }

const showFormModal = ref(false)
const selected = ref<Question | null>(null)
const saving = ref(false)
const { form, errors, validate, reset, applyServerErrors } = useResourceForm(questionSchema, {
    type: 'mcq', content: '', chapter: '', difficulty: 'easy', options: ['', ''], correct_answer: '',
})

function openCreate() {
    selected.value = null
    reset({ type: 'mcq', content: '', chapter: '', difficulty: 'easy', options: ['', ''], correct_answer: '' })
    showFormModal.value = true
}
function openEdit(q: Question) {
    selected.value = q
    reset({
        type: q.type, content: q.content, chapter: q.chapter || '', difficulty: q.difficulty,
        options: q.options.map(o => o.option_text), correct_answer: q.options.find(o => o.is_correct)?.option_text || '',
    })
    showFormModal.value = true
}
function closeFormModal() { showFormModal.value = false; selected.value = null }

async function submitForm() {
    if (!validate()) return
    saving.value = true
    const isChoice = form.type === 'mcq' || form.type === 'true_false'
    const payload = {
        type: form.type, content: form.content, chapter: form.chapter || null, difficulty: form.difficulty,
        options: isChoice ? form.options.filter((o: string) => o.trim()) : undefined,
        correct_answer: isChoice ? form.correct_answer : undefined,
    }
    try {
        if (selected.value) await questionsApi.updateQuestion(selected.value.id, payload)
        else await questionsApi.createQuestion(courseId.value, payload)
        closeFormModal()
        saving.value = false
        uiStore.showToast(selected.value ? 'Question updated.' : 'Question created.', 'success')
        await load(pagination.value.current_page)
    } catch (err: any) {
        saving.value = false
        if (err?.response?.status === 422) {
            applyServerErrors(err.response.data?.errors)
            uiStore.showToast('Please fix the errors below.', 'error')
        } else {
            uiStore.showToast('Failed to save question.', 'error')
        }
    }
}

const showArchiveModal = ref(false)
const archiving = ref(false)
function openArchive(q: Question) { selected.value = q; showArchiveModal.value = true }
async function confirmArchive() {
    if (!selected.value) return
    archiving.value = true
    try {
        await questionsApi.archiveQuestion(selected.value.id)
        showArchiveModal.value = false; selected.value = null; archiving.value = false
        uiStore.showToast('Question archived.', 'success')
        await load(pagination.value.current_page)
    } catch { archiving.value = false; uiStore.showToast('Failed to archive question.', 'error') }
}
async function restoreOne(q: Question) {
    try {
        await questionsApi.restoreQuestion(q.id)
        uiStore.showToast('Question restored.', 'success')
        await load(pagination.value.current_page)
    } catch { uiStore.showToast('Failed to restore question.', 'error') }
}

const openMenuId = ref<number | null>(null)
const menuPosition = ref({ top: 0, left: 0 })
function toggleMenu(id: number, event: MouseEvent) {
    if (openMenuId.value === id) { openMenuId.value = null; return }
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
    menuPosition.value = { top: rect.bottom + 4, left: rect.right - 160 }
    openMenuId.value = id
}
function closeMenus() { openMenuId.value = null }
function handleClickOutside(e: MouseEvent) {
    const t = e.target as HTMLElement
    if (!t.closest('[data-action-menu]') && !t.closest('[data-action-trigger]')) closeMenus()
}
onMounted(() => { load(1); document.addEventListener('click', handleClickOutside) })
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

function goToImport() {
    router.push(`/instructor/courses/${courseId.value}/questions/import`)
}
</script>

<template>
    <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-8 min-h-[calc(100vh-68px)]">
        <ResourceToolbar title="Question Bank" description="Manage and curate assessment content for this course."
            search-placeholder="Search question content…" show-search show-filter show-refresh show-archive-toggle
            :refreshing="refreshing" :active-view="activeView" :active-count="pagination.total" :archived-count="0"
            @update:search="v => { search = v; load(1) }" @update:active-view="v => { activeView = v; load(1) }"
            @refresh="handleRefresh">
            <template #actions>
                <BaseButton v-can="'question.create'" variant="secondary" @click="goToImport">
                    <template #icon>
                        <Upload class="h-4 w-4" />
                    </template>
                    Import Questions
                </BaseButton>
                <BaseButton v-can="'question.create'" @click="openCreate">
                    <template #icon>
                        <Plus class="h-4 w-4" />
                    </template>
                    Create Question
                </BaseButton>
            </template>
            <template #filters>
                <div class="grid grid-cols-3 gap-3">
                    <BaseSelect v-model="filterType" :options="typeOptions" @update:model-value="load(1)" />
                    <input v-model="filterChapter" placeholder="Chapter…"
                        class="px-3 py-2.5 rounded-lg border border-border bg-surface text-sm" @change="load(1)" />
                    <BaseSelect v-model="filterDifficulty" :options="difficultyOptions" @update:model-value="load(1)" />
                </div>
            </template>
        </ResourceToolbar>

        <BaseCard :padded="false" class="rounded-xl border border-border overflow-hidden shadow-sm">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-bg/40 border-b border-border">
                        <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Type
                        </th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">
                            Question
                            Content</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Chapter
                        </th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">
                            Difficulty</th>
                        <th class="w-16 px-6 py-3.5"></th>
                    </tr>
                </thead>
                <tbody v-if="loading" class="divide-y divide-border">
                    <tr v-for="i in 6" :key="i">
                        <td colspan="5" class="px-6 py-5">
                            <div class="h-4 w-full max-w-sm animate-pulse rounded bg-bg" />
                        </td>
                    </tr>
                </tbody>
                <tbody v-else-if="questions.length" class="divide-y divide-border">
                    <tr v-for="q in questions" :key="q.id" class="hover:bg-bg/40 transition-colors">
                        <td class="px-6 py-5">
                            <BaseBadge :variant="typeBadgeVariant[q.type] || 'neutral'" class="uppercase text-[10px]">
                                {{ q.type }}
                            </BaseBadge>
                        </td>
                        <td class="px-6 py-5 text-sm text-text max-w-md truncate">{{ q.content || '—' }}</td>
                        <td class="px-6 py-5 text-sm text-text/60">{{ q.chapter || '—' }}</td>
                        <td class="px-6 py-5 text-sm text-text/60 capitalize">{{ q.difficulty || '—' }}</td>
                        <td class="px-6 py-5 text-right">
                            <TableRowActions :active-tab="activeView" edit-permission="question.update"
                                archive-permission="question.archive" restore-permission="question.restore"
                                @edit="openEdit(q)" @archive="openArchive(q)" @restore="restoreOne(q)" />
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center text-sm text-text/50">No questions found.</td>
                    </tr>
                </tbody>
            </table>
            <div class="border-t border-border px-6 py-4">
                <AppPagination :pagination="pagination" @change-page="load" />
            </div>
        </BaseCard>
    </div>

    <BaseDialog :model-value="showFormModal" :title="selected ? 'Edit question' : 'Create question'"
        @update:model-value="closeFormModal">
        <QuestionFormFields v-model:form="form" :errors="errors" />
        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton variant="secondary" @click="closeFormModal">Cancel</BaseButton>
                <BaseButton :loading="saving" @click="submitForm">{{ selected ? 'Save changes' : 'Create question' }}
                </BaseButton>
            </div>
        </template>
    </BaseDialog>

    <ConfirmModal :show="showArchiveModal" title="Archive question?"
        description="This question will be removed from active views and can be restored later."
        confirm-text="Archive question" variant="danger" :icon="Archive" :loading="archiving"
        @close="showArchiveModal = false" @confirm="confirmArchive" />
</template>
