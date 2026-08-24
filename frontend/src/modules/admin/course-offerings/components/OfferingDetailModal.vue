<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { School, User, X, UserCheck, Ban, Users2, Archive, RotateCcw } from 'lucide-vue-next'
import BaseDialog from '@/shared/components/ui/BaseDialog.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'
import BaseSelect from '@/shared/components/ui/BaseSelect.vue'
import { getSections } from '@/modules/admin/sections/api/sections'
import type { CourseOffering } from '../types/courseOffering'
import {
    attachSection, detachSection, attachInstructor, removeInstructor,
    approveOffering, rejectOffering, cancelOffering, enrollOfferingSection,
    archiveOffering, reopenOffering, getUsers,
} from '../api/courseOfferings'
import { useUiStore } from '@/stores/ui'
import ConfirmModal from '@/shared/components/ConfirmModal.vue'

const props = defineProps<{ modelValue: boolean; offering: CourseOffering | null }>()
const emit = defineEmits<{
    'update:modelValue': [boolean]
    updated: [CourseOffering]
    archived: [number]
}>()

const uiStore = useUiStore()
const local = ref<CourseOffering | null>(null)
const loadingOptions = ref(false)

const sectionOptions = ref<{ value: string; label: string }[]>([])
const instructorOptions = ref<{ value: string; label: string }[]>([])
const selectedSectionId = ref('')
const selectedInstructorId = ref('')
const instructorType = ref<'lead_instructor' | 'instructor'>('lead_instructor')

const busy = ref(false)
const reopening = ref(false)
const archiving = ref(false)
const showRejectModal = ref(false)
const showCancelModal = ref(false)
const showArchiveModal = ref(false)
const rejectReason = ref('')

watch(
    () => props.offering,
    async (offering) => {
        local.value = offering
        selectedSectionId.value = ''
        selectedInstructorId.value = ''
        rejectReason.value = ''

        if (!offering) return

        loadingOptions.value = true
        try {
            const [secRes, userRes] = await Promise.all([
                getSections(offering.semester_id, 0, 1, 200).catch(() => ({ data: [] })),
                getUsers(1, 200).catch(() => ({ data: [] })),
            ])
            sectionOptions.value = (secRes.data ?? []).map((s: any) => ({
                value: String(s.id),
                label: `Section ${s.name} (Year ${s.year_level})`,
            }))
            instructorOptions.value = (userRes.data ?? [])
                .filter(
                    (u) =>
                        u.role_name?.includes('instructor') ||
                        u.role_name?.includes('lead_instructor'),
                )
                .map((u) => ({ value: String(u.id), label: `${u.first_name} ${u.last_name}` }))
        } finally {
            loadingOptions.value = false
        }
    },
    { immediate: true },
)

const isDraft = computed(() => local.value?.status === 'draft')
const isApproved = computed(() => local.value?.status === 'approved')
const isDeadEnd = computed(() => local.value?.status === 'rejected' || local.value?.status === 'cancelled')

const archiveWarning = computed(() => {
    if (isDeadEnd.value) {
        return `This will remove the ${local.value?.course?.code} offering from the list. You can restore it later from the Archived tab if needed.`
    }
    return `This offering is still ${local.value?.status}. Archiving it now will remove it from the active list even though it hasn't been cancelled or rejected. You can restore it later from the Archived tab.`
})

function close() { emit('update:modelValue', false) }

async function addSection() {
    if (!local.value || !selectedSectionId.value) return
    busy.value = true
    try {
        local.value = await attachSection(local.value.id, Number(selectedSectionId.value))
        emit('updated', local.value)
        selectedSectionId.value = ''
        uiStore.showToast('Section attached.', 'success')
    } catch (err: any) {
        uiStore.showToast(err?.response?.data?.message || 'Failed to attach section.', 'error')
    } finally {
        busy.value = false
    }
}
async function removeSectionFrom(sectionId: number) {
    if (!local.value) return
    busy.value = true
    try {
        local.value = await detachSection(local.value.id, sectionId)
        emit('updated', local.value)
        uiStore.showToast('Section removed.', 'success')
    } finally {
        busy.value = false
    }
}
async function addInstructor() {
    if (!local.value || !selectedInstructorId.value) return
    busy.value = true
    try {
        local.value = await attachInstructor(local.value.id, Number(selectedInstructorId.value), instructorType.value)
        emit('updated', local.value)
        selectedInstructorId.value = ''
        uiStore.showToast('Instructor assigned.', 'success')
    } catch (err: any) {
        uiStore.showToast(err?.response?.data?.message || 'Failed to assign instructor.', 'error')
    } finally {
        busy.value = false
    }
}
async function removeInstructorFrom(instructorId: number) {
    if (!local.value) return
    busy.value = true
    try {
        local.value = await removeInstructor(local.value.id, instructorId)
        emit('updated', local.value)
        uiStore.showToast('Instructor removed.', 'success')
    } finally {
        busy.value = false
    }
}
async function approve() {
    if (!local.value) return
    busy.value = true
    try {
        local.value = await approveOffering(local.value.id)
        emit('updated', local.value)
        uiStore.showToast('Offering approved.', 'success')
    } catch (err: any) {
        uiStore.showToast(err?.response?.data?.message || 'Failed to approve.', 'error')
    } finally {
        busy.value = false
    }
}
async function confirmReject() {
    if (!local.value || !rejectReason.value.trim()) return
    busy.value = true
    try {
        local.value = await rejectOffering(local.value.id, rejectReason.value.trim())
        emit('updated', local.value)
        showRejectModal.value = false
        rejectReason.value = ''
        uiStore.showToast('Offering rejected.', 'success')
    } finally {
        busy.value = false
    }
}
async function confirmCancel() {
    if (!local.value) return
    busy.value = true
    try {
        local.value = await cancelOffering(local.value.id)
        emit('updated', local.value)
        showCancelModal.value = false
        uiStore.showToast('Offering cancelled.', 'success')
    } finally {
        busy.value = false
    }
}
async function reopen() {
    if (!local.value) return
    reopening.value = true
    try {
        local.value = await reopenOffering(local.value.id)
        emit('updated', local.value)
        uiStore.showToast('Offering reopened as draft.', 'success')
    } catch (err: any) {
        uiStore.showToast(err?.response?.data?.message || 'Failed to reopen offering.', 'error')
    } finally {
        reopening.value = false
    }
}
async function confirmArchive() {
    if (!local.value) return
    archiving.value = true
    try {
        await archiveOffering(local.value.id)
        emit('archived', local.value.id)
        showArchiveModal.value = false
        uiStore.showToast('Offering archived.', 'success')
        close()
    } catch (err: any) {
        uiStore.showToast(err?.response?.data?.message || 'Failed to archive offering.', 'error')
    } finally {
        archiving.value = false
    }
}
async function enroll() {
    if (!local.value) return
    busy.value = true
    try {
        const res = await enrollOfferingSection(local.value.id)
        uiStore.showToast(`${res.enrolled_count} students enrolled.`, 'success')
    } catch (err: any) {
        uiStore.showToast(err?.response?.data?.message || 'Failed to enroll students.', 'error')
    } finally {
        busy.value = false
    }
}
</script>

<template>
    <BaseDialog :model-value="modelValue" :title="local?.course?.name" :description="local?.course?.code"
        max-width="max-w-2xl" @update:model-value="close">
        <div v-if="!local" class="py-10 text-center text-sm text-text/50">Loading...</div>

        <div v-else class="space-y-6">
            <div class="flex items-center justify-between">
                <BaseBadge
                    :variant="local.status === 'approved' ? 'success' : local.status === 'rejected' ? 'danger' : local.status === 'cancelled' ? 'neutral' : 'info'">
                    {{ local.status }}
                </BaseBadge>

                <span class="text-xs text-text/40">{{ local.semester?.name }} · {{ local.semester?.academic_year
                    }}</span>
            </div>

            <p v-if="local.status === 'rejected' && local.rejection_reason"
                class="rounded-md bg-error/10 p-3 text-sm text-error">
                {{ local.rejection_reason }}
            </p>

            <div>
                <h4 class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-text/60">
                    <School class="h-3.5 w-3.5" /> Sections
                </h4>
                <div class="mb-2 flex flex-wrap gap-2">
                    <span v-for="s in local.sections" :key="s.id"
                        class="flex items-center gap-1.5 rounded-full border border-border bg-bg px-3 py-1 text-xs text-text">
                        Section {{ s.name }} (Y{{ s.year_level }})
                        <button v-if="isDraft" v-can="'course_offering.section.remove'" type="button"
                            class="text-text/40 hover:text-error" @click="removeSectionFrom(s.id)">
                            <X class="h-3 w-3" />
                        </button>
                    </span>
                    <span v-if="!local.sections?.length" class="text-xs text-text/40">No sections attached.</span>
                </div>
                <div v-if="isDraft" v-can="'course_offering.section.assign'" class="flex gap-2">
                    <BaseSelect v-model="selectedSectionId" :options="sectionOptions" placeholder="Add a section"
                        class="flex-1" />
                    <BaseButton variant="secondary" :disabled="!selectedSectionId" :loading="busy" @click="addSection">
                        Add</BaseButton>
                </div>
            </div>

            <div>
                <h4 class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-text/60">
                    <User class="h-3.5 w-3.5" /> Instructors
                </h4>
                <div class="mb-2 flex flex-wrap gap-2">
                    <span v-for="i in local.instructors" :key="i.id"
                        class="flex items-center gap-1.5 rounded-full border border-border bg-bg px-3 py-1 text-xs text-text">
                        {{ i.first_name }} {{ i.last_name }} <span class="text-text/40">· {{ i.type ===
                            'lead_instructor' ? 'Lead' : 'Instructor' }}</span>
                        <button v-if="isDraft" v-can="'course_offering.instructor.remove'" type="button"
                            class="text-text/40 hover:text-error" @click="removeInstructorFrom(i.id)">
                            <X class="h-3 w-3" />
                        </button>
                    </span>
                    <span v-if="!local.instructors?.length" class="text-xs text-text/40">No instructors assigned.</span>
                </div>
                <div v-if="isDraft" v-can="'course_offering.instructor.assign'" class="flex flex-wrap gap-2">
                    <BaseSelect v-model="selectedInstructorId" :options="instructorOptions"
                        placeholder="Select instructor" class="flex-1" />
                    <BaseSelect v-model="instructorType"
                        :options="[{ value: 'lead_instructor', label: 'Lead instructor' }, { value: 'instructor', label: 'Instructor' }]"
                        class="w-40" />
                    <BaseButton variant="secondary" :disabled="!selectedInstructorId" :loading="busy"
                        @click="addInstructor">Assign</BaseButton>
                </div>
            </div>
        </div>

        <template #footer>
            <div v-if="local" class="flex flex-wrap justify-end gap-2">
                <BaseButton v-if="isApproved" v-can="'course_offering.section.enroll'" variant="secondary"
                    :loading="busy" @click="enroll">
                    <template #icon>
                        <Users2 class="h-4 w-4" />
                    </template>Enroll students
                </BaseButton>

                <BaseButton v-if="local.status !== 'cancelled' && local.status !== 'rejected'"
                    v-can="'course_offering.cancel'" variant="secondary" @click="showCancelModal = true">
                    <template #icon>
                        <Ban class="h-4 w-4" />
                    </template>Cancel offering
                </BaseButton>

                <BaseButton v-if="isDeadEnd" v-can="'course_offering.update'" variant="secondary" :loading="reopening"
                    @click="reopen">
                    <template #icon>
                        <RotateCcw class="h-4 w-4" />
                    </template>Reopen as draft
                </BaseButton>

                <BaseButton v-can="'course_offering.archive'" variant="secondary" @click="showArchiveModal = true">
                    <template #icon>
                        <Archive class="h-4 w-4" />
                    </template>Archive
                </BaseButton>

                <template v-if="isDraft">
                    <BaseButton v-can="'course_offering.reject'" variant="secondary" @click="showRejectModal = true">
                        Reject</BaseButton>
                    <BaseButton v-can="'course_offering.approve'" :loading="busy" @click="approve">
                        <template #icon>
                            <UserCheck class="h-4 w-4" />
                        </template>Approve
                    </BaseButton>
                </template>
            </div>
        </template>
    </BaseDialog>

    <BaseDialog :model-value="showRejectModal" title="Reject this offering?" max-width="max-w-md"
        @update:model-value="showRejectModal = false">
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/60">Reason</label>
            <textarea v-model="rejectReason" rows="3" maxlength="500"
                class="w-full rounded-md border border-border bg-bg px-3 py-2 text-sm text-text outline-none focus:border-accent"
                placeholder="Why is this offering being rejected?" />
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton variant="secondary" @click="showRejectModal = false">Cancel</BaseButton>
                <BaseButton :disabled="!rejectReason.trim()" :loading="busy" @click="confirmReject">Reject offering
                </BaseButton>
            </div>
        </template>
    </BaseDialog>

    <ConfirmModal :show="showCancelModal" title="Cancel this offering?"
        description="Students will not be able to enroll in this offering. It can be reopened as a draft later if needed."
        confirm-text="Cancel offering" variant="danger" :icon="Ban" :loading="busy" @close="showCancelModal = false"
        @confirm="confirmCancel" />

    <ConfirmModal :show="showArchiveModal" title="Archive this offering?" :description="archiveWarning"
        confirm-text="Archive offering" variant="danger" :icon="Archive" :loading="archiving"
        @close="showArchiveModal = false" @confirm="confirmArchive" />
</template>
