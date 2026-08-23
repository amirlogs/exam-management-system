<script setup lang="ts">
import { onMounted, ref } from 'vue'
import {
    Building2, Edit, MapPin, Hash, RefreshCw, Maximize2, Minimize2
} from 'lucide-vue-next'
import BaseDialog from '@/shared/components/ui/BaseDialog.vue'
import BaseCard from '@/shared/components/ui/BaseCard.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseInput from '@/shared/components/ui/BaseInput.vue'
import { useResourceForm } from '@/shared/composables/useResourceForm'
import { universitySchema } from '../schemas/university.schema'
import { getUniversities, createUniversity, updateUniversity } from '../api/universities'
import type { University } from '../types/university'
import { useUiStore } from '@/stores/ui'
import { usePermissionsStore } from '@/stores/permission'
import PermissionDenied from '@/shared/components/PermissionDenied.vue'


const uiStore = useUiStore()
const university = ref<University | null>(null)
const loading = ref(true)
const refreshing = ref(false)
const error = ref<string | null>(null)
const showFormModal = ref(false)
const saving = ref(false)
const permissionsStore = usePermissionsStore()


const { form, errors, validate, reset, applyServerErrors } = useResourceForm(universitySchema, {
    name: '', code: '', address: '',
})

async function loadUniversity(isRefresh = false) {
    isRefresh ? (refreshing.value = true) : (loading.value = true)
    error.value = null
    try {
        const response = await getUniversities(1, 1)
        university.value = response.data[0] ?? null
    } catch {
        error.value = 'Failed to load university information.'
    } finally {
        loading.value = false
        refreshing.value = false
    }
}

function openEdit() {
    if (university.value) reset({ name: university.value.name, code: university.value.code, address: university.value.address })
    else reset({ name: '', code: '', address: '' })
    showFormModal.value = true
}
function closeFormModal() { showFormModal.value = false }

async function submitForm() {
    if (!validate()) return
    saving.value = true
    const isCreating = !university.value
    try {
        university.value = university.value
            ? await updateUniversity(university.value.id, form)
            : await createUniversity(form)
        closeFormModal()
        saving.value = false
        uiStore.showToast(isCreating ? 'University set up successfully.' : 'University details updated.', 'success')
    } catch (err: any) {
        saving.value = false
        if (err?.response?.status === 422) {
            applyServerErrors(err.response.data?.errors)
            uiStore.showToast('Please fix the errors below.', 'error')
        } else {
            uiStore.showToast('Failed to save university details.', 'error')
        }
    }
}

onMounted(() => loadUniversity())

//full screen
const isFullscreen = ref(false)
const handleFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement
}
const toggleFullscreen = async () => {
    try {
        if (!document.fullscreenElement) {
            await document.documentElement.requestFullscreen()
        } else {
            await document.exitFullscreen()
        }
    } catch (error) {
        console.error('Fullscreen error:', error)
    }
}
</script>

<template>
    <div class="mx-auto w-full max-w-160 space-y-6 px-6 py-8 min-h-[calc(100vh-68px)]">
        <div class="flex items-end justify-between">
            <div>
                <h1 class="text-2xl font-bold font-display text-text">Institution Settings</h1>
                <p class="mt-1.5 text-sm text-text/60">Manage your university's basic information.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" :disabled="refreshing"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-border bg-surface text-text/60 hover:border-accent/40 hover:text-accent transition-colors disabled:opacity-50"
                    title="Refresh" @click="loadUniversity(true)">
                    <RefreshCw class="h-4 w-4" :class="refreshing ? 'animate-spin' : ''" />
                </button>
                <button type="button"
                    class="mr-2 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-text/60 transition-colors hover:border-accent/40 hover:text-accent"
                    :title="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'" @click="toggleFullscreen">
                    <Minimize2 v-if="isFullscreen" class="h-4 w-4" />
                    <Maximize2 v-else class="h-4 w-4" />
                </button>
            </div>
        </div>

        <BaseCard v-if="loading">
            <div class="space-y-4">
                <div class="h-5 w-48 animate-pulse rounded bg-text/5" />
                <div class="h-4 w-64 animate-pulse rounded bg-text/5" />
                <div class="h-4 w-40 animate-pulse rounded bg-text/5" />
            </div>
        </BaseCard>

        <div v-else-if="error" class="flex items-start gap-3 rounded-lg border border-error/30 bg-error/5 p-4">
            <p class="flex-1 text-sm font-medium text-error">{{ error }}</p>
            <button type="button" class="text-sm font-medium text-error hover:underline"
                @click="loadUniversity()">Retry</button>
        </div>
        <BaseCard v-else-if="!university">
            <div v-if="permissionsStore.hasPermission('university.create')"
                class="flex flex-col items-center py-6 text-center">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-accent/10 text-accent">
                    <Building2 class="h-6 w-6" />
                </div>
                <h3 class="text-sm font-semibold text-text">No university set up yet</h3>
                <p class="mt-1 max-w-sm text-sm text-text/55">Add your institution's name, code, and address to get
                    started.</p>
                <BaseButton class="mt-4" @click="openEdit">
                    <template #icon>
                        <Building2 class="h-4 w-4" />
                    </template>
                    Set up university
                </BaseButton>
            </div>
            <PermissionDenied v-else title="University setup unavailable" />
        </BaseCard>

        <BaseCard v-else>
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md border border-border bg-bg text-sm font-semibold text-text">
                        {{ (university.code?.slice(0, 3) || '—').toUpperCase() }}
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-text">{{ university.name || '—' }}</h2>
                        <p class="text-xs text-text/50">University</p>
                    </div>
                </div>
                <BaseButton variant="secondary" @click="openEdit" v-can="'university.update'">
                    <template #icon>
                        <Edit class="h-4 w-4" />
                    </template>
                    Edit
                </BaseButton>
            </div>

            <div class="mt-6 space-y-4 border-t border-border pt-5">
                <div class="flex items-start gap-3">
                    <Hash class="mt-0.5 h-4 w-4 text-text/40" />
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-text/50">Code</p>
                        <p class="mt-0.5 font-mono text-sm text-text">{{ university.code || '—' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <MapPin class="mt-0.5 h-4 w-4 text-text/40" />
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-text/50">Address</p>
                        <p class="mt-0.5 text-sm text-text">{{ university.address || '—' }}</p>
                    </div>
                </div>
            </div>
        </BaseCard>
    </div>

    <BaseDialog :model-value="showFormModal" :title="university ? 'Edit university details' : 'Set up your university'"
        @update:model-value="closeFormModal">
        <div class="space-y-4">
            <BaseInput v-model="form.name" label="University name" placeholder="e.g. Addis Ababa University"
                :error="errors.name" />
            <BaseInput v-model="form.code" label="University code" placeholder="e.g. AAU" :error="errors.code" />
            <BaseInput v-model="form.address" label="Address" placeholder="University address"
                :error="errors.address" />
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton variant="secondary" @click="closeFormModal">Cancel</BaseButton>
                <BaseButton :loading="saving" @click="submitForm">{{ university ? 'Save changes' : 'Create university'
                    }}</BaseButton>
            </div>
        </template>
    </BaseDialog>
</template>
