<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { Upload, ChevronRight } from 'lucide-vue-next'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'
import BaseSelect from '@/shared/components/ui/BaseSelect.vue'
import AppPagination from '@/shared/components/AppPagination.vue'
import { listImports } from '../api/imports'
import { IMPORT_TYPE_CONFIG } from '../config/importTypes'
import type { ImportHistory, ImportType } from '../types/import'
import type { Pagination } from '@/shared/composables/useCrudResource'
import { useRouter } from 'vue-router'
import { handleApiError } from '@/shared/utils/apiError'
import { useUiStore } from '@/stores/ui'

const router = useRouter()
const uiStore = useUiStore()

const imports = ref<ImportHistory[]>([])
const loading = ref(false)
const filterType = ref<ImportType | ''>('')
const emptyPagination = (): Pagination => ({ current_page: 1, last_page: 1, per_page: 12, total: 0, from: null, to: null })
const pagination = ref<Pagination>(emptyPagination())

const typeOptions = Object.entries(IMPORT_TYPE_CONFIG).map(([value, cfg]) => ({ value, label: cfg.label }))

const statusVariant: Record<string, 'info' | 'danger' | 'success' | 'neutral'> = {
    pending: 'neutral', processing: 'neutral', ready_for_review: 'info', confirmed: 'success', failed: 'danger',
}
const statusLabel: Record<string, string> = {
    pending: 'Processing', processing: 'Processing', ready_for_review: 'Ready for review', confirmed: 'Confirmed', failed: 'Failed',
}

async function load(page = 1) {
    loading.value = true
    try {
        const res = await listImports(filterType.value || undefined, page)
        imports.value = res.data
        pagination.value = res.pagination
    } catch (err) {
        handleApiError(err, uiStore, undefined, 'Failed to load imports.')
    } finally {
        loading.value = false
    }
}
onMounted(() => load(1))

function openImport(imp: ImportHistory) {
    router.push({ name: 'admin-import-detail', params: { id: imp.id } })
}
</script>

<template>
    <div class="mx-auto w-full max-w-260 space-y-6 px-6 py-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-text">Data imports</h1>
                <p class="mt-1 text-sm text-text/60">Bulk import students, instructors, sections, and more from CSV.</p>
            </div>
            <div class="flex items-end gap-3">
                <BaseSelect v-model="filterType as any" :options="typeOptions" placeholder="All types"
                    @update:model-value="() => load(1)" />
                <BaseButton variant="secondary" :disabled="loading" @click="load(pagination.current_page)">
                    <template #icon>
                        <RefreshCw :class="['h-4 w-4', loading && 'animate-spin']" />
                    </template>
                    Refresh
                </BaseButton>
                <BaseButton @click="router.push({ name: 'admin-import-new' })">
                    <template #icon>
                        <Upload class="h-4 w-4" />
                    </template>New import
                </BaseButton>
            </div>
        </section>

        <div class="rounded-md border border-border bg-surface">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b border-border bg-text/2.5">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                            Import</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Type
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Rows
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                            Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">
                            Uploaded by
                        </th>
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody v-if="loading" class="divide-y divide-border">
                    <tr v-for="row in 4" :key="row">
                        <td colspan="6" class="px-4 py-4">
                            <div class="h-3.5 w-64 animate-pulse rounded bg-text/5" />
                        </td>
                    </tr>
                </tbody>
                <tbody v-else-if="imports.length" class="divide-y divide-border">
                    <tr v-for="imp in imports" :key="imp.id" class="cursor-pointer hover:bg-text/2"
                        @click="openImport(imp)">
                        <td class="px-4 py-3 font-mono text-xs text-text/70">#{{ imp.id }}</td>
                        <td class="px-4 py-3 text-sm text-text">{{ IMPORT_TYPE_CONFIG[imp.type].label }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="font-medium text-success">{{ imp.valid_count }} valid</span>
                            <span v-if="imp.error_count > 0" class="ml-2 font-medium text-error">{{ imp.error_count }}
                                errors</span>
                        </td>
                        <td class="px-4 py-3">
                            <BaseBadge :variant="statusVariant[imp.status] || 'neutral'">{{ statusLabel[imp.status] ||
                                imp.status }}</BaseBadge>
                        </td>
                        <td class="px-4 py-3 text-xs text-text/50">{{ imp.uploaded_by.name }}</td>
                        <td class="px-4 py-3 text-right">
                            <ChevronRight class="inline h-4 w-4 text-text/30" />
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-text/55">No imports yet.</td>
                    </tr>
                </tbody>
            </table>
            <AppPagination :pagination="pagination" @change-page="load" />
        </div>
    </div>
</template>
