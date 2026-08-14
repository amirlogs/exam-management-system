<script setup lang="ts">
import { onMounted, onUnmounted, reactive, ref } from 'vue'
import {
    Archive, Building2, Edit, MoreVertical, Plus, RefreshCw,
    RotateCcw, Search, X,
} from 'lucide-vue-next'

import BaseDialog from '@/shared/components/ui/BaseDialog.vue'
import AppPagination from '@/shared/components/AppPagination.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseInput from '@/shared/components/ui/BaseInput.vue'
import type { Pagination } from '@/shared/components/AppPagination.vue'

import {
    getUniversities,
    getArchivedUniversities,
    restoreUniversity,
    deleteUniversity,
    createUniversity,
    updateUniversity,
} from '../api/universities'

import type { University } from '../types/university'
import { useUiStore } from '@/stores/ui'
import { computed } from 'vue'
import ConfirmModal from '@/shared/components/ConfirmModal.vue'
import { required, minLength, maxLength, runValidators } from '@/shared/utils/validate'

const formErrors = reactive({ name: '', code: '', address: '' })

function resetFormErrors() {
    formErrors.name = ''
    formErrors.code = ''
    formErrors.address = ''
}

function validateForm(): boolean {
    formErrors.name = runValidators(form.name, [
        required('University name'), minLength(3, 'University name'), maxLength(255, 'University name'),
    ])
    formErrors.code = runValidators(form.code, [
        required('University code'), minLength(2, 'University code'), maxLength(255, 'University code'),
    ])
    formErrors.address = runValidators(form.address, [
        required('Address'), minLength(3, 'Address'), maxLength(255, 'Address'),
    ])
    return !formErrors.name && !formErrors.code && !formErrors.address
}
const emptyStateTitle = computed(() => {
    if (search.value) return 'No universities found'
    return activeTab.value === 'archived' ? 'No archived universities' : 'No universities yet'
})

const emptyStateSubtext = computed(() => {
    if (search.value) return 'Search is not connected to the server yet.'
    return activeTab.value === 'archived'
        ? 'Archived universities will appear here.'
        : 'Add your first university to get started.'
})
type Tab = 'active' | 'archived'

const uiStore = useUiStore()

const universities = ref<University[]>([])
const archivedUniversities = ref<University[]>([])
const activeTab = ref<Tab>('active')

const search = ref('')
const loading = ref(false)
const saving = ref(false)
const archiving = ref(false)
const restoring = ref(false)
const error = ref<string | null>(null)

const openMenuId = ref<number | null>(null)
const menuPosition = ref({ top: 0, left: 0 })

const showFormModal = ref(false)
const showArchiveModal = ref(false)
const showRestoreModal = ref(false)

const selectedUniversity = ref<University | null>(null)

const form = reactive({ name: '', code: '', address: '' })

function resetForm() {
    form.name = ''
    form.code = ''
    form.address = ''
    resetFormErrors()
}

function openEdit(university: University) {
    selectedUniversity.value = university
    form.name = university.name
    form.code = university.code
    form.address = university.address || ''
    resetFormErrors()
    showFormModal.value = true
    openMenuId.value = null
}

const activePagination = ref<Pagination>({
    current_page: 1, last_page: 1, from: null, to: null, total: 0, per_page: 12,
})
const archivedPagination = ref<Pagination>({
    current_page: 1, last_page: 1, from: null, to: null, total: 0, per_page: 12,
})

const currentPagination = () =>
    activeTab.value === 'active' ? activePagination.value : archivedPagination.value
const currentPage = () => currentPagination().current_page

async function loadUniversities(page = 1) {
    loading.value = true
    error.value = null
    openMenuId.value = null

    try {
        const response =
            activeTab.value === 'active'
                ? await getUniversities(page)
                : await getArchivedUniversities(page)

        const meta: Pagination = {
            current_page: response.current_page,
            last_page: response.last_page,
            from: response.from,
            to: response.to,
            total: response.total,
            per_page: response.per_page,
        }

        if (activeTab.value === 'active') {
            universities.value = response.data
            activePagination.value = meta
        } else {
            archivedUniversities.value = response.data
            archivedPagination.value = meta
        }
    } catch (err: any) {
        error.value =
            err?.response?.status === 401 || err?.response?.status === 403
                ? 'You are not authorized to view universities.'
                : 'Failed to load universities. Please try again.'
    } finally {
        loading.value = false
    }
}

function changeTab(tab: Tab) {
    if (activeTab.value === tab) return
    activeTab.value = tab
    search.value = ''
    loadUniversities(1)
}

function changePage(page: number) {
    loadUniversities(page)
}

// ---------------- Action menu (Teleported — fixes clipping) ----------------
function toggleMenu(id: number, event: MouseEvent) {
    if (openMenuId.value === id) {
        openMenuId.value = null
        return
    }
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
    const menuHeight = 140
    const openUpward = window.innerHeight - rect.bottom < menuHeight

    menuPosition.value = {
        top: openUpward ? rect.top - menuHeight - 4 : rect.bottom + 4,
        left: rect.right - 160,
    }
    openMenuId.value = id
}

function closeMenus() {
    openMenuId.value = null
}

function handleClickOutside(e: MouseEvent) {
    const target = e.target as HTMLElement
    if (!target.closest('[data-action-menu]') && !target.closest('[data-action-trigger]')) {
        closeMenus()
    }
}

onMounted(() => {
    loadUniversities(1)
    document.addEventListener('click', handleClickOutside)
    window.addEventListener('scroll', closeMenus, true)
})
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('scroll', closeMenus, true)
})


function openCreate() {
    selectedUniversity.value = null
    resetForm()
    showFormModal.value = true
}

function closeFormModal() {
    showFormModal.value = false
    selectedUniversity.value = null
    resetForm()
}

async function submitForm() {
    if (!validateForm()) return

    saving.value = true
    try {
        if (selectedUniversity.value) {
            await updateUniversity(selectedUniversity.value.id, { ...form })
            uiStore.showToast('University updated.', 'success')
        } else {
            await createUniversity({ ...form })
            uiStore.showToast('University created.', 'success')
        }
        closeFormModal()
        await loadUniversities(currentPage())
    } catch (err: any) {
        if (err?.response?.status === 422) {
            // ASSUMED shape, based on your ApiResponse interface: { errors: { name: [...], code: [...] } }
            const serverErrors = err.response.data?.errors
            if (serverErrors) {
                for (const field of ['name', 'code', 'address'] as const) {
                    if (serverErrors[field]) {
                        formErrors[field] = Array.isArray(serverErrors[field])
                            ? serverErrors[field][0]
                            : serverErrors[field]
                    }
                }
            }
            uiStore.showToast('Please fix the errors below.', 'error')
        } else {
            uiStore.showToast('Failed to save university.', 'error')
        }
    } finally {
        saving.value = false
    }
}

// ---------------- Archive / Restore (optimistic, both tabs sync) ----------------
function openArchive(university: University) {
    selectedUniversity.value = university
    showArchiveModal.value = true
    openMenuId.value = null
}

function openRestore(university: University) {
    selectedUniversity.value = university
    showRestoreModal.value = true
    openMenuId.value = null
}

async function confirmArchive() {
    if (!selectedUniversity.value) return
    const target = selectedUniversity.value
    archiving.value = true
    try {
        await deleteUniversity(target.id)

        universities.value = universities.value.filter(u => u.id !== target.id)
        activePagination.value.total -= 1
        archivedPagination.value.total += 1

        showArchiveModal.value = false
        selectedUniversity.value = null
        archiving.value = false

        uiStore.showToast(`${target.name} archived.`, 'success')
    } catch (err: any) {
        archiving.value = false
        uiStore.showToast('Failed to archive university.', 'error')
    }
}

async function confirmRestore() {
    if (!selectedUniversity.value) return
    const target = selectedUniversity.value
    restoring.value = true
    try {
        await restoreUniversity(target.id)
        archivedUniversities.value = archivedUniversities.value.filter(u => u.id !== target.id)
        archivedPagination.value.total -= 1
        activePagination.value.total += 1
        uiStore.showToast(`${target.name} restored.`, 'success')
        showRestoreModal.value = false
        selectedUniversity.value = null
    } catch (err: any) {
        uiStore.showToast('Failed to restore university.', 'error')
    } finally {
        restoring.value = false
    }
}

function retry() {
    loadUniversities(currentPage())
}
</script>

<template>
    <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
        <!-- Header -->
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-text">Universities</h1>
                <p class="mt-1 text-sm text-text/60">
                    Manage universities registered in the examination system.
                </p>
            </div>
            <BaseButton @click="openCreate">
                <template #icon>
                    <Plus class="h-4 w-4" />
                </template>
                Add university
            </BaseButton>
        </section>

        <!-- Tabs -->
        <div class="border-b border-border">
            <div class="flex gap-6">
                <button type="button" :class="['relative flex h-10 items-center gap-2 text-sm font-medium transition-colors',
                    activeTab === 'active' ? 'text-accent' : 'text-text/60 hover:text-text']"
                    @click="changeTab('active')">
                    <Building2 class="h-4 w-4" />
                    Active
                    <span class="rounded-full bg-accent/10 px-2 py-0.5 text-xs tabular-nums">{{ activePagination.total
                    }}</span>
                    <span v-if="activeTab === 'active'" class="absolute inset-x-0 bottom-0 h-0.5 bg-accent" />
                </button>
                <button type="button" :class="['relative flex h-10 items-center gap-2 text-sm font-medium transition-colors',
                    activeTab === 'archived' ? 'text-accent' : 'text-text/60 hover:text-text']"
                    @click="changeTab('archived')">
                    <Archive class="h-4 w-4" />
                    Archived
                    <span class="rounded-full bg-text/5 px-2 py-0.5 text-xs tabular-nums">{{ archivedPagination.total
                    }}</span>
                    <span v-if="activeTab === 'archived'" class="absolute inset-x-0 bottom-0 h-0.5 bg-accent" />
                </button>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full max-w-md">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text/40" />
                <input v-model="search" type="search" placeholder="Search universities..."
                    class="h-9 w-full rounded-md border border-border bg-surface pl-9 pr-9 text-sm text-text outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20" />
                <button v-if="search" type="button"
                    class="absolute right-2 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded text-text/50 hover:bg-text/5 hover:text-text"
                    @click="search = ''">
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>
            <BaseButton variant="secondary" :disabled="loading" @click="retry">
                <template #icon>
                    <RefreshCw :class="['h-4 w-4', loading && 'animate-spin']" />
                </template>
                Refresh
            </BaseButton>
        </div>

        <!-- Error -->
        <div v-if="error" class="flex items-start gap-3 rounded-md border border-error/30 bg-error/5 p-4">
            <div class="flex-1">
                <p class="text-sm font-medium text-error">{{ error }}</p>
            </div>
            <button type="button" class="text-sm font-medium text-error hover:underline" @click="retry">Retry</button>
        </div>

        <!-- Table card -->
        <div class="rounded-md border border-border bg-surface">
            <div class="overflow-x-auto">
                <table class="w-full min-w-190 border-collapse">
                    <thead>
                        <tr class="border-b border-border bg-text/2.5">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                                University</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                                Code</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                                Address
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                                Created
                            </th>
                            <th
                                class="w-16 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-text/50">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody v-if="loading" class="divide-y divide-border">
                        <tr v-for="row in 6" :key="row">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 animate-pulse rounded-md bg-text/5" />
                                    <div class="space-y-2">
                                        <div class="h-3.5 w-52 animate-pulse rounded bg-text/5" />
                                        <div class="h-3 w-24 animate-pulse rounded bg-text/5" />
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="h-6 w-20 animate-pulse rounded bg-text/5" />
                            </td>
                            <td class="px-4 py-4">
                                <div class="h-3.5 w-48 animate-pulse rounded bg-text/5" />
                            </td>
                            <td class="px-4 py-4">
                                <div class="h-3.5 w-24 animate-pulse rounded bg-text/5" />
                            </td>
                            <td />
                        </tr>
                    </tbody>

                    <tbody v-else-if="(activeTab === 'active' ? universities : archivedUniversities).length"
                        class="divide-y divide-border">
                        <tr v-for="university in activeTab === 'active' ? universities : archivedUniversities"
                            :key="university.id" class="group transition-colors hover:bg-text/2">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-border bg-bg text-xs font-semibold text-text">
                                        {{ university.code?.slice(0, 3).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-text">{{ university.name }}</p>
                                        <p class="mt-0.5 text-xs text-text/50">University</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5"><span class="font-mono text-xs tabular-nums text-text/80">{{
                                university.code
                                    }}</span></td>
                            <td class="px-4 py-3.5"><span class="block max-w-75 truncate text-sm text-text/60">{{
                                university.address || '—' }}</span></td>
                            <td class="px-4 py-3.5"><span class="font-mono text-xs tabular-nums text-text/60">{{
                                university.created_at }}</span></td>

                            <td class="px-4 py-3.5 text-right">
                                <button type="button" data-action-trigger
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-text/50 transition hover:bg-text/5 hover:text-text focus:outline-none focus:ring-2 focus:ring-accent/20"
                                    aria-label="University actions" @click.stop="toggleMenu(university.id, $event)">
                                    <MoreVertical class="h-4 w-4" />
                                </button>

                                <Teleport to="body">
                                    <div v-if="openMenuId === university.id" data-action-menu
                                        class="fixed z-[100] w-40 rounded-md border border-border bg-surface py-1 shadow-lg"
                                        :style="{ top: menuPosition.top + 'px', left: menuPosition.left + 'px' }"
                                        @click.stop>
                                        <button v-if="activeTab === 'active'" type="button"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-text hover:bg-text/5"
                                            @click="openEdit(university)">
                                            <Edit class="h-4 w-4 text-text/50" /> Edit
                                        </button>
                                        <div v-if="activeTab === 'active'" class="my-1 border-t border-border" />
                                        <button v-if="activeTab === 'active'" type="button"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-error hover:bg-error/5"
                                            @click="openArchive(university)">
                                            <Archive class="h-4 w-4" /> Archive
                                        </button>
                                        <button v-else type="button"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-accent hover:bg-accent/5"
                                            @click="openRestore(university)">
                                            <RotateCcw class="h-4 w-4" /> Restore
                                        </button>
                                    </div>
                                </Teleport>
                            </td>
                        </tr>
                    </tbody>

                    <tbody v-else>
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div
                                        class="mb-4 flex h-10 w-10 items-center justify-center rounded-md border border-border bg-bg text-text/50">
                                        <Archive class="h-5 w-5" />
                                    </div>
                                    <h3 class="text-sm font-semibold text-text">{{ emptyStateTitle }}</h3>
                                    <p class="mt-1 text-sm text-text/55">{{ emptyStateSubtext }}</p>
                                    <BaseButton v-if="!search && activeTab === 'active'" class="mt-4"
                                        @click="openCreate">
                                        <template #icon>
                                            <Plus class="h-4 w-4" />
                                        </template>
                                        Add university
                                    </BaseButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <AppPagination :pagination="currentPagination()" @change-page="changePage" />
        </div>
    </div>

    <!-- Create / Edit — now BaseDialog -->
    <BaseDialog :model-value="showFormModal" :title="selectedUniversity ? 'Edit university' : 'Add university'"
        @update:model-value="closeFormModal">
        <div class="space-y-4">
            <BaseInput v-model="form.name" label="University name" placeholder="e.g. Addis Ababa University"
                error="formErrors.name" />
            <BaseInput v-model="form.code" label="University code" placeholder="e.g. AAU" :error="formErrors.code" />
            <div>
                <label class="block text-xs font-bold tracking-wide uppercase text-text/70 mb-2">Address</label>
                <textarea v-model="form.address" rows="3"
                    class="w-full resize-none rounded-md border bg-bg px-4 py-3 text-sm text-text outline-none focus:border-accent transition-colors"
                    :class="formErrors.address ? 'border-error' : 'border-border'" placeholder="University address" />
                <p v-if="formErrors.address" class="mt-1 text-xs text-error">{{ formErrors.address }}</p>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <BaseButton variant="secondary" @click="closeFormModal">Cancel</BaseButton>
                <BaseButton :loading="saving" @click="submitForm">
                    {{ selectedUniversity ? 'Save changes' : 'Create university' }}
                </BaseButton>
            </div>
        </template>
    </BaseDialog>

    <!-- Archive / Restore — now ConfirmModal -->
    <ConfirmModal :show="showArchiveModal" title="Archive university?"
        :description="`This will remove ${selectedUniversity?.name} from active university views. It can be restored later.`"
        confirm-text="Archive university" variant="danger" :icon="Archive" :loading="archiving"
        @close="showArchiveModal = false" @confirm="confirmArchive" />

    <ConfirmModal :show="showRestoreModal" title="Restore university?"
        :description="`This will return ${selectedUniversity?.name} to the active university list.`"
        confirm-text="Restore university" variant="accent" :icon="RotateCcw" :loading="restoring"
        @close="showRestoreModal = false" @confirm="confirmRestore" />
</template>
