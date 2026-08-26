<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Plus, ChevronRight } from 'lucide-vue-next'
import ResourceToolbar from '@/shared/components/ResourceToolbar.vue'
import BaseCard from '@/shared/components/ui/BaseCard.vue'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseDialog from '@/shared/components/ui/BaseDialog.vue'
import BaseInput from '@/shared/components/ui/BaseInput.vue'
import BaseSelect from '@/shared/components/ui/BaseSelect.vue'
import AppPagination from '@/shared/components/AppPagination.vue'
import * as examsApi from '../api/exams'
import { useUiStore } from '@/stores/ui'
import type { Exam } from '../types/exam'

const route = useRoute()
const router = useRouter()
const uiStore = useUiStore()
const courseOfferingId = computed(() => Number(route.params.courseOfferingId))

const exams = ref<Exam[]>([])
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0, from: null, to: null })
const loading = ref(false)
const refreshing = ref(false)

const statusVariant: Record<string, any> = {
    draft: 'neutral', pending_approval: 'warning', approved: 'info', rejected: 'danger',
    scheduled: 'info', active: 'success', completed: 'success', cancelled: 'danger',
}

async function load(page = 1) {
    loading.value = true
    try {
        const res = await examsApi.getExams(courseOfferingId.value, page)
        exams.value = res.data
        pagination.value = res.pagination
    } catch (err: any) {
        uiStore.showToast(err?.response?.data?.message || 'Failed to load exams.', 'error')
    } finally {
        loading.value = false
    }
}
async function handleRefresh() { refreshing.value = true; await load(pagination.value.current_page); refreshing.value = false }
onMounted(() => load(1))

const showCreateModal = ref(false)
const saving = ref(false)
const form = ref({ title: '', type: 'MIDTERM', duration_minutes: 60, mcq_marks: 1, tf_marks: 1 })

function openCreate() { form.value = { title: '', type: 'MIDTERM', duration_minutes: 60, mcq_marks: 1, tf_marks: 1 }; showCreateModal.value = true }

async function submitCreate() {
    saving.value = true
    try {
        const composition: Record<string, any> = {}
        if (form.value.mcq_marks) composition.mcq = { marks_each: form.value.mcq_marks }
        if (form.value.tf_marks) composition.true_false = { marks_each: form.value.tf_marks }
        const exam = await examsApi.createExam(courseOfferingId.value, {
            title: form.value.title, type: form.value.type, duration_minutes: form.value.duration_minutes, composition,
        })
        showCreateModal.value = false
        saving.value = false
        uiStore.showToast('Exam created.', 'success')
        router.push(`/instructor/exams/${exam.id}`)
    } catch (err: any) {
        saving.value = false
        uiStore.showToast(err?.response?.data?.message || 'Failed to create exam.', 'error')
    }
}

function openExam(exam: Exam) { router.push(`/instructor/exams/${exam.id}`) }
</script>

<template>
    <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-8 min-h-[calc(100vh-68px)]">
        <ResourceToolbar title="Exams" description="Manage exams for this course offering." show-refresh
            :refreshing="refreshing" @refresh="handleRefresh">
            <template #actions>
                <BaseButton v-can="'exam.create'" @click="openCreate">
                    <template #icon>
                        <Plus class="h-4 w-4" />
                    </template>
                    Create Exam
                </BaseButton>
            </template>
        </ResourceToolbar>

        <BaseCard :padded="false" class="rounded-xl border border-border overflow-hidden shadow-sm">
            <table class="w-full">
                <thead>
                    <tr class="bg-bg/40 border-b border-border">
                        <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Title
                        </th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Type
                        </th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">
                            Questions</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Status
                        </th>
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody v-if="loading" class="divide-y divide-border">
                    <tr v-for="i in 4" :key="i">
                        <td colspan="5" class="px-6 py-5">
                            <div class="h-4 w-64 animate-pulse rounded bg-bg" />
                        </td>
                    </tr>
                </tbody>
                <tbody v-else-if="exams.length" class="divide-y divide-border">
                    <tr v-for="exam in exams" :key="exam.id" class="cursor-pointer hover:bg-bg/40 transition-colors"
                        @click="openExam(exam)">
                        <td class="px-6 py-5 text-sm font-medium text-text">{{ exam.title || '—' }}</td>
                        <td class="px-6 py-5 text-sm text-text/70">{{ exam.type }}</td>
                        <td class="px-6 py-5 text-sm text-text/70">{{ exam.total_questions ?? 0 }} · {{ exam.total_marks
                            ?? 0 }}
                            marks</td>
                        <td class="px-6 py-5">
                            <BaseBadge :variant="statusVariant[exam.status] || 'neutral'">
                                {{ exam.status.replace('_', '') }}
                            </BaseBadge>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <ChevronRight class="w-4 h-4 text-text/30 inline-block" />
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center text-sm text-text/50">No exams yet.</td>
                    </tr>
                </tbody>
            </table>
            <div class="border-t border-border px-6 py-4">
                <AppPagination :pagination="pagination" @change-page="load" />
            </div>
        </BaseCard>
    </div>

    <BaseDialog :model-value="showCreateModal" title="Create Exam" @update:model-value="showCreateModal = false">
        <div class="space-y-4">
            <BaseInput v-model="form.title" label="Title" placeholder="e.g. Discrete Mathematics Final" />
            <BaseSelect v-model="form.type" label="Type"
                :options="[{ value: 'MIDTERM', label: 'Midterm' }, { value: 'FINAL', label: 'Final' }]" />
            <BaseInput v-model.number="form.duration_minutes" type="number" label="Duration (minutes)" />
            <div class="grid grid-cols-2 gap-3">
                <BaseInput v-model.number="form.mcq_marks" type="number" step="0.5" label="MCQ marks each" />
                <BaseInput v-model.number="form.tf_marks" type="number" step="0.5" label="True/False marks each" />
            </div>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton variant="secondary" @click="showCreateModal = false">Cancel</BaseButton>
                <BaseButton :loading="saving" @click="submitCreate">Create Exam</BaseButton>
            </div>
        </template>
    </BaseDialog>
</template>
