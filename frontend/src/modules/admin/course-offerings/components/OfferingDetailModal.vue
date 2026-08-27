<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import {
    Archive,
    Ban,
    Loader2,
    Plus,
    UserRound,
} from 'lucide-vue-next'

import BaseDialog from '@/shared/components/ui/BaseDialog.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'
import BaseSelect from '@/shared/components/ui/BaseSelect.vue'
import ConfirmModal from '@/shared/components/ConfirmModal.vue'

import { useUiStore } from '@/stores/ui'

import {
    getOffering,
    listInstructors,
    assignInstructor,
    removeInstructorAssignment,
    approveOffering,
    rejectOffering,
    cancelOffering,
    reopenOffering,
    archiveOffering,
} from '../api/courseOfferings'

import type {
    CourseOffering,
    InstructorAssignment,
    InstructorAssignmentType,
} from '../types/courseOffering'

const props = defineProps<{
    modelValue: boolean
    offering: CourseOffering | null
}>()

const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    updated: [offering: CourseOffering]
    archived: [id: number]
}>()

const uiStore = useUiStore()

const local = ref<CourseOffering | null>(null)
const loading = ref(false)
const busy = ref(false)

const instructors = ref<any[]>([])

const selectedInstructorId = ref('')
const instructorType = ref<InstructorAssignmentType>(
    'lead_instructor',
)

const assignmentSectionId = ref<number | null>(null)

const showRejectModal = ref(false)
const showCancelModal = ref(false)
const showArchiveModal = ref(false)

const rejectReason = ref('')
const archiving = ref(false)
const reopening = ref(false)

const instructorOptions = computed(() =>
    instructors.value
        .filter((instructor) => instructor.status === 'active')
        .map((instructor) => ({
            value: String(instructor.id),
            label:
                `${instructor.user?.first_name ?? ''} ${instructor.user?.last_name ?? ''}`.trim() ||
                instructor.employee_number,
        })),
)

const assignmentForSection = computed(() => {
    const map = new Map<number, InstructorAssignment[]>()

    for (const assignment of local.value?.instructor_assignments ?? []) {
        const existing =
            map.get(assignment.section_id) ?? []

        existing.push(assignment)

        map.set(assignment.section_id, existing)
    }

    return map
})

const isDraft = computed(
    () => local.value?.status === 'draft',
)

const isDeadEnd = computed(
    () =>
        local.value?.status === 'rejected' ||
        local.value?.status === 'cancelled',
)

watch(
    () => props.offering,
    async (offering) => {
        if (!offering) {
            local.value = null
            return
        }

        await refresh(offering.id)
    },
    { immediate: true },
)

async function refresh(offeringId: number) {
    loading.value = true

    try {
        local.value = await getOffering(offeringId)

        if (!instructors.value.length) {
            const response = await listInstructors(1, 200)

            instructors.value = response.data
        }
    } catch (err: any) {
        uiStore.showToast(
            err?.response?.data?.message ||
            'Failed to load course offering.',
            'error',
        )

        close()
    } finally {
        loading.value = false
    }
}

function close() {
    emit('update:modelValue', false)
}

function openAssign(sectionId: number) {
    assignmentSectionId.value = sectionId
    selectedInstructorId.value = ''
    instructorType.value = 'lead_instructor'
}

function closeAssign() {
    assignmentSectionId.value = null
    selectedInstructorId.value = ''
}

async function assignToSection(sectionId: number) {
    if (!local.value || !selectedInstructorId.value) {
        return
    }

    busy.value = true

    try {
        await assignInstructor(
            local.value.id,
            Number(selectedInstructorId.value),
            sectionId,
            instructorType.value,
        )

        await refresh(local.value.id)

        if (local.value) {
            emit('updated', local.value)
        }

        closeAssign()

        uiStore.showToast(
            'Instructor assigned successfully.',
            'success',
        )
    } catch (err: any) {
        uiStore.showToast(
            err?.response?.data?.message ||
            'Failed to assign instructor.',
            'error',
        )
    } finally {
        busy.value = false
    }
}

async function removeAssignment(
    assignment: InstructorAssignment,
) {
    if (!local.value) return

    busy.value = true

    try {
        await removeInstructorAssignment(
            local.value.id,
            assignment.id,
        )

        await refresh(local.value.id)

        if (local.value) {
            emit('updated', local.value)
        }

        uiStore.showToast(
            'Instructor assignment removed.',
            'success',
        )
    } catch (err: any) {
        uiStore.showToast(
            err?.response?.data?.message ||
            'Failed to remove instructor.',
            'error',
        )
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

        uiStore.showToast(
            'Offering approved and students enrolled.',
            'success',
        )
    } catch (err: any) {
        uiStore.showToast(
            err?.response?.data?.message ||
            'Failed to approve offering.',
            'error',
        )
    } finally {
        busy.value = false
    }
}

async function confirmReject() {
    if (!local.value || !rejectReason.value.trim()) {
        return
    }

    busy.value = true

    try {
        local.value = await rejectOffering(
            local.value.id,
            rejectReason.value.trim(),
        )

        emit('updated', local.value)

        showRejectModal.value = false
        rejectReason.value = ''

        uiStore.showToast(
            'Offering rejected.',
            'success',
        )
    } catch (err: any) {
        uiStore.showToast(
            err?.response?.data?.message ||
            'Failed to reject offering.',
            'error',
        )
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

        uiStore.showToast(
            'Offering cancelled.',
            'success',
        )
    } catch (err: any) {
        uiStore.showToast(
            err?.response?.data?.message ||
            'Failed to cancel offering.',
            'error',
        )
    } finally {
        busy.value = false
    }
}

async function reopen() {
    if (!local.value) return

    reopening.value = true

    try {
        local.value = await reopenOffering(
            local.value.id,
        )

        emit('updated', local.value)

        uiStore.showToast(
            'Offering reopened as draft.',
            'success',
        )
    } catch (err: any) {
        uiStore.showToast(
            err?.response?.data?.message ||
            'Failed to reopen offering.',
            'error',
        )
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

        uiStore.showToast(
            'Offering archived.',
            'success',
        )

        close()
    } catch (err: any) {
        uiStore.showToast(
            err?.response?.data?.message ||
            'Failed to archive offering.',
            'error',
        )
    } finally {
        archiving.value = false
    }
}

function instructorName(
    assignment: InstructorAssignment,
) {
    const user = assignment.instructor?.user

    return (
        `${user?.first_name ?? ''} ${user?.last_name ?? ''}`.trim() ||
        assignment.instructor?.employee_number ||
        'Unknown instructor'
    )
}

function instructorTypeLabel(
    type: InstructorAssignmentType,
) {
    return type === 'lead_instructor'
        ? 'Lead instructor'
        : 'Instructor'
}
</script>

<template>
    <BaseDialog :model-value="modelValue" :title="local?.course?.name" :description="local?.course?.code"
        max-width="max-w-3xl" @update:model-value="close">
        <div v-if="loading" class="flex items-center justify-center py-14">
            <Loader2 class="h-5 w-5 animate-spin text-text/40" />
        </div>

        <div v-else-if="local" class="space-y-6">
            <!-- Header -->
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="font-mono text-xs text-text/45">
                        {{ local.course?.code }}
                    </p>

                    <h3 class="mt-1 text-lg font-semibold text-text">
                        {{ local.course?.name }}
                    </h3>

                    <p class="mt-1 text-xs text-text/50">
                        {{ local.semester?.name }}
                        ·
                        {{ local.semester?.academic_year }}
                    </p>
                </div>

                <BaseBadge :variant="local.status === 'approved'
                        ? 'success'
                        : local.status === 'rejected'
                            ? 'danger'
                            : local.status === 'cancelled'
                                ? 'neutral'
                                : 'info'
                    ">
                    {{ local.status }}
                </BaseBadge>
            </div>

            <!-- Rejection -->
            <div v-if="
                local.status === 'rejected' &&
                local.rejection_reason
            " class="rounded-md border border-error/20 bg-error/5 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-error/70">
                    Rejection reason
                </p>

                <p class="mt-1 text-sm text-error">
                    {{ local.rejection_reason }}
                </p>
            </div>

            <!-- Sections -->
            <section>
                <div class="mb-3 flex items-end justify-between">
                    <div>
                        <h4 class="text-sm font-semibold text-text">
                            Sections
                        </h4>

                        <p class="mt-1 text-xs text-text/45">
                            These sections were determined automatically
                            from the active curriculum.
                        </p>
                    </div>

                    <span class="text-xs text-text/40">
                        {{ local.sections?.length ?? 0 }}
                        {{ (local.sections?.length ?? 0) === 1
                            ? 'section'
                        : 'sections' }}
                    </span>
                </div>

                <div class="space-y-3">
                    <div v-for="section in local.sections" :key="section.id"
                        class="rounded-lg border border-border bg-bg/40 p-4">
                        <!-- Section header -->
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-text">
                                    {{ section.program?.name ?? 'Program' }}
                                </p>

                                <p class="mt-1 text-xs text-text/50">
                                    Year {{ section.year_level }}
                                    · Section {{ section.name }}
                                </p>
                            </div>

                            <BaseButton v-if="isDraft" v-can="'course_offering.assign_instructor'" variant="secondary"
                                size="sm" @click="openAssign(section.id)">
                                <template #icon>
                                    <Plus class="h-3.5 w-3.5" />
                                </template>
                                Assign instructor
                            </BaseButton>
                        </div>

                        <!-- Assignments -->
                        <div class="mt-3 space-y-2">
                            <div v-for="
assignment in assignmentForSection.get(section.id) ?? []
                " :key="assignment.id"
                                class="flex items-center justify-between gap-3 rounded-md border border-border bg-surface px-3 py-2.5">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-text/5">
                                        <UserRound class="h-4 w-4 text-text/45" />
                                    </div>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-text">
                                            {{ instructorName(assignment) }}
                                        </p>

                                        <p class="mt-0.5 text-xs text-text/45">
                                            {{ instructorTypeLabel(assignment.type) }}
                                        </p>
                                    </div>
                                </div>

                                <BaseButton v-if="isDraft" v-can="'course_offering.remove_instructor'" variant="ghost"
                                    size="sm" class="text-error hover:text-error" @click="removeAssignment(assignment)">
                                    Remove
                                </BaseButton>
                            </div>

                            <p v-if="!(assignmentForSection.get(section.id)?.length)"
                                class="rounded-md border border-dashed border-border px-3 py-3 text-center text-xs text-text/40">
                                No instructor assigned yet.
                            </p>
                        </div>

                        <!-- Assignment form -->
                        <div v-if="assignmentSectionId === section.id"
                            class="mt-3 rounded-md border border-accent/20 bg-accent/5 p-3">
                            <div class="grid gap-2 sm:grid-cols-[1fr_180px_auto]">
                                <BaseSelect v-model="selectedInstructorId" :options="instructorOptions"
                                    placeholder="Select instructor" />

                                <BaseSelect v-model="instructorType" :options="[
                                    {
                                        value: 'lead_instructor',
                                        label: 'Lead instructor',
                                    },
                                    {
                                        value: 'instructor',
                                        label: 'Instructor',
                                    },
                                ]" />

                                <div class="flex gap-2">
                                    <BaseButton variant="secondary" @click="closeAssign">
                                        Cancel
                                    </BaseButton>

                                    <BaseButton :disabled="!selectedInstructorId" :loading="busy"
                                        @click="assignToSection(section.id)">
                                        Assign
                                    </BaseButton>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!local.sections?.length"
                        class="rounded-md border border-dashed border-border px-4 py-8 text-center text-sm text-text/45">
                        No sections were found for this offering.
                    </div>
                </div>
            </section>
        </div>

        <template #footer>
            <div v-if="local" class="flex flex-wrap justify-end gap-2">
                <BaseButton v-if="
                    local.status !== 'cancelled' &&
                    local.status !== 'rejected'
                " v-can="'course_offering.cancel'" variant="secondary" @click="showCancelModal = true">
                    <template #icon>
                        <Ban class="h-4 w-4" />
                    </template>
                    Cancel offering
                </BaseButton>

                <BaseButton v-if="isDeadEnd" v-can="'course_offering.update'" variant="secondary" :loading="reopening"
                    @click="reopen">
                    Reopen as draft
                </BaseButton>

                <BaseButton v-can="'course_offering.archive'" variant="secondary" @click="showArchiveModal = true">
                    <template #icon>
                        <Archive class="h-4 w-4" />
                    </template>
                    Archive
                </BaseButton>

                <template v-if="isDraft">
                    <BaseButton v-can="'course_offering.reject'" variant="secondary" @click="showRejectModal = true">
                        Reject
                    </BaseButton>

                    <BaseButton v-can="'course_offering.approve'" :loading="busy" @click="approve">
                        Approve
                    </BaseButton>
                </template>
            </div>
        </template>
    </BaseDialog>

    <!-- Reject -->
    <BaseDialog :model-value="showRejectModal" title="Reject this offering?" max-width="max-w-md"
        @update:model-value="showRejectModal = false">
        <textarea v-model="rejectReason" rows="4" maxlength="500"
            class="w-full rounded-md border border-border bg-bg px-3 py-2 text-sm text-text outline-none focus:border-accent"
            placeholder="Why is this offering being rejected?" />

        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton variant="secondary" @click="showRejectModal = false">
                    Cancel
                </BaseButton>

                <BaseButton :disabled="!rejectReason.trim()" :loading="busy" @click="confirmReject">
                    Reject offering
                </BaseButton>
            </div>
        </template>
    </BaseDialog>

    <!-- Cancel -->
    <ConfirmModal :show="showCancelModal" title="Cancel this offering?"
        description="The offering will no longer be active and can be reopened as a draft."
        confirm-text="Cancel offering" variant="danger" :icon="Ban" :loading="busy" @close="showCancelModal = false"
        @confirm="confirmCancel" />

    <!-- Archive -->
    <ConfirmModal :show="showArchiveModal" title="Archive this offering?"
        description="The offering will be removed from the active list. You can restore it later."
        confirm-text="Archive offering" variant="danger" :icon="Archive" :loading="archiving"
        @close="showArchiveModal = false" @confirm="confirmArchive" />
</template>
