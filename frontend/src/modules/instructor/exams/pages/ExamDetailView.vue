<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { FileEdit, Send, CheckCircle2, XCircle, Calendar, Play, Square, Ban, Archive } from 'lucide-vue-next'
import BaseCard from '@/shared/components/ui/BaseCard.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseDialog from '@/shared/components/ui/BaseDialog.vue'
import ConfirmModal from '@/shared/components/ConfirmModal.vue'
import AlertBanner from '@/shared/components/ui/AlertBanner.vue'
import * as examsApi from '../api/exams'
import { useUiStore } from '@/stores/ui'
import type { Exam } from '../types/exam'

const route = useRoute()
const router = useRouter()
const uiStore = useUiStore()
const examId = computed(() => Number(route.params.examId))

const exam = ref<Exam | null>(null)
const loading = ref(true)
const busy = ref(false)

const steps = [
    { key: 'draft', label: 'Draft', icon: FileEdit },
    { key: 'pending_approval', label: 'Pending', icon: Send },
    { key: 'approved', label: 'Approved', icon: CheckCircle2 },
    { key: 'scheduled', label: 'Scheduled', icon: Calendar },
    { key: 'active', label: 'Active', icon: Play },
    { key: 'completed', label: 'Completed', icon: Square },
]
const stepIndex = computed(() => steps.findIndex(s => s.key === exam.value?.status))

async function load() {
    loading.value = true
    try { exam.value = await examsApi.getExam(examId.value) }
    catch (err: any) { uiStore.showToast(err?.response?.data?.message || 'Failed to load exam.', 'error') }
    finally { loading.value = false }
}
onMounted(load)

async function guardedAction(fn: () => Promise<Exam>, successMsg: string) {
    busy.value = true
    try {
        exam.value = await fn()
        uiStore.showToast(successMsg, 'success')
    } catch (err: any) {
        uiStore.showToast(err?.response?.data?.message || 'Action failed.', 'error')
    } finally {
        busy.value = false
    }
}

const showRejectModal = ref(false)
const rejectReason = ref('')
async function submitReject() {
    if (!exam.value) return
    await guardedAction(() => examsApi.rejectExam(exam.value!.id, rejectReason.value), 'Exam rejected.')
    showRejectModal.value = false
    rejectReason.value = ''
}

const showScheduleModal = ref(false)
const scheduleDate = ref('')
async function submitSchedule() {
    if (!exam.value) return
    await guardedAction(() => examsApi.scheduleExam(exam.value!.id, new Date(scheduleDate.value).toISOString()), 'Exam scheduled.')
    showScheduleModal.value = false
}

const showExtendModal = ref(false)
const extendMinutes = ref(15)
async function submitExtend() {
    if (!exam.value) return
    await guardedAction(() => examsApi.extendTime(exam.value!.id, extendMinutes.value), 'Time extended.')
    showExtendModal.value = false
}

const showCancelModal = ref(false)
async function submitCancel() {
    if (!exam.value) return
    await guardedAction(() => examsApi.cancelExam(exam.value!.id), 'Exam cancelled.')
    showCancelModal.value = false
}

const showEndModal = ref(false)
async function submitEnd() {
    if (!exam.value) return
    await guardedAction(() => examsApi.endExam(exam.value!.id), 'Exam ended.')
    showEndModal.value = false
}
</script>

<template>
    <div v-if="loading" class="p-8 text-center text-text/50">Loading…</div>
    <div v-else-if="exam" class="mx-auto w-full max-w-360 space-y-6 px-6 py-8 min-h-[calc(100vh-68px)]">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="font-mono text-xs bg-bg border border-border rounded px-2 py-0.5 text-text/60">{{
                        exam.type }}</span>
                </div>
                <h1 class="text-2xl font-bold font-display text-text">{{ exam.title }}</h1>
                <p class="text-sm text-text/60 mt-1">{{ exam.total_questions ?? 0 }} questions · {{ exam.total_marks ??
                    0 }} marks</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <router-link :to="`/instructor/exams/${exam.id}/build`">
                    <BaseButton variant="secondary">Open Composer</BaseButton>
                </router-link>

                <BaseButton v-if="exam.status === 'draft'" :disabled="!exam.total_questions || busy"
                    @click="guardedAction(() => examsApi.submitApproval(exam!.id), 'Submitted for approval.')">
                    Submit for Approval
                </BaseButton>
                <BaseButton v-if="exam.status === 'pending_approval'" variant="secondary" :disabled="busy"
                    @click="guardedAction(() => examsApi.revertToDraft(exam!.id), 'Reverted to draft.')">
                    Revert to Draft
                </BaseButton>
                <BaseButton v-if="exam.status === 'pending_approval'" :disabled="busy"
                    @click="guardedAction(() => examsApi.approveExam(exam!.id), 'Exam approved.')">
                    Approve
                </BaseButton>
                <BaseButton v-if="exam.status === 'pending_approval'" variant="secondary" :disabled="busy"
                    @click="showRejectModal = true">
                    Reject
                </BaseButton>
                <BaseButton v-if="exam.status === 'approved'" :disabled="busy" @click="showScheduleModal = true">
                    Schedule Exam
                </BaseButton>
                <BaseButton v-if="exam.status === 'scheduled'" :disabled="busy"
                    @click="guardedAction(() => examsApi.publishExam(exam!.id), 'Exam published.')">
                    Publish Now
                </BaseButton>
                <BaseButton v-if="exam.status === 'active'" variant="secondary" :disabled="busy"
                    @click="showExtendModal = true">
                    Extend Time
                </BaseButton>
                <BaseButton v-if="exam.status === 'active'" variant="secondary" :disabled="busy"
                    @click="showEndModal = true">
                    <template #icon>
                        <Ban class="w-4 h-4" />
                    </template>
                    End Exam
                </BaseButton>
                <BaseButton v-if="['approved', 'scheduled'].includes(exam.status)" variant="secondary" :disabled="busy"
                    @click="showCancelModal = true">
                    Cancel Exam
                </BaseButton>
            </div>
        </div>

        <!-- Stepper -->
        <BaseCard>
            <h3 class="font-semibold text-text mb-8">Lifecycle Status</h3>
            <div class="flex items-start justify-between relative">
                <div class="absolute top-5 left-[5%] right-[5%] h-0.5 bg-border" />
                <div v-for="(step, i) in steps" :key="step.key" class="flex flex-col items-center relative z-10"
                    :style="{ width: `${100 / steps.length}%` }">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center border-4 border-bg"
                        :class="i <= stepIndex ? 'bg-accent text-white' : 'bg-surface border-border text-text/30'">
                        <component :is="step.icon" class="w-4 h-4" />
                    </div>
                    <span class="mt-2 text-xs font-semibold"
                        :class="i === stepIndex ? 'text-accent' : 'text-text/50'">{{
                        step.label }}</span>
                </div>
            </div>

            <div v-if="exam.status === 'rejected'"
                class="mt-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-2">
                <XCircle class="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
                <div>
                    <p class="text-sm font-semibold text-red-800">Rejected</p>
                    <p class="text-sm text-red-600 mt-1">Review the rejection reason and revert to draft to make
                        changes.</p>
                </div>
            </div>
        </BaseCard>

        <!-- Metadata -->
        <BaseCard>
            <h3 class="font-semibold text-text mb-4 pb-2 border-b border-border">Exam Metadata</h3>
            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <dt class="text-xs font-bold uppercase text-text/50 mb-1">Duration</dt>
                    <dd class="text-sm font-medium text-text">{{ exam.duration_minutes }} min</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase text-text/50 mb-1">Total Marks</dt>
                    <dd class="text-sm font-medium text-text">{{ exam.total_marks }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase text-text/50 mb-1">Author</dt>
                    <dd class="text-sm font-medium text-text">{{ exam.creator?.name || '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase text-text/50 mb-1">Scheduled</dt>
                    <dd class="text-sm font-medium text-text">{{ exam.scheduled_start || 'Not set' }}</dd>
                </div>
            </dl>
        </BaseCard>
    </div>

    <BaseDialog :model-value="showRejectModal" title="Reject exam" @update:model-value="showRejectModal = false">
        <label class="text-xs font-bold uppercase text-text/70 mb-1.5 block">Reason *</label>
        <textarea v-model="rejectReason" rows="3"
            class="w-full px-3 py-2.5 rounded-lg border border-border bg-bg text-sm"
            placeholder="Explain what needs to change…" />
        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton variant="secondary" @click="showRejectModal = false">Cancel</BaseButton>
                <BaseButton :disabled="!rejectReason.trim() || busy" @click="submitReject">Reject Exam</BaseButton>
            </div>
        </template>
    </BaseDialog>

    <BaseDialog :model-value="showScheduleModal" title="Schedule exam" @update:model-value="showScheduleModal = false">
        <label class="text-xs font-bold uppercase text-text/70 mb-1.5 block">Start date & time *</label>
        <input v-model="scheduleDate" type="datetime-local"
            class="w-full px-3 py-2.5 rounded-lg border border-border bg-bg text-sm" />
        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton variant="secondary" @click="showScheduleModal = false">Cancel</BaseButton>
                <BaseButton :disabled="!scheduleDate || busy" @click="submitSchedule">Schedule</BaseButton>
            </div>
        </template>
    </BaseDialog>

    <BaseDialog :model-value="showExtendModal" title="Extend exam time" @update:model-value="showExtendModal = false">
        <label class="text-xs font-bold uppercase text-text/70 mb-1.5 block">Additional minutes</label>
        <input v-model.number="extendMinutes" type="number" min="1"
            class="w-full px-3 py-2.5 rounded-lg border border-border bg-bg text-sm" />
        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton variant="secondary" @click="showExtendModal = false">Cancel</BaseButton>
                <BaseButton :disabled="busy" @click="submitExtend">Extend</BaseButton>
            </div>
        </template>
    </BaseDialog>

    <ConfirmModal :show="showCancelModal" title="Cancel this exam?"
        description="Students will no longer be able to see or take this exam." confirm-text="Cancel Exam"
        variant="danger" :icon="Ban" :loading="busy" @close="showCancelModal = false" @confirm="submitCancel" />
    <ConfirmModal :show="showEndModal" title="End this exam now?"
        description="All in-progress attempts will be auto-submitted immediately." confirm-text="End Exam"
        variant="danger" :icon="Square" :loading="busy" @close="showEndModal = false" @confirm="submitEnd" />
</template>
