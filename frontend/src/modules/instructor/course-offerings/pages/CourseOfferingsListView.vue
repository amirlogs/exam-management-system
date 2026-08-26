<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { BookOpen } from 'lucide-vue-next'
import ResourceToolbar from '@/shared/components/ResourceToolbar.vue'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'
import BaseSelect from '@/shared/components/ui/BaseSelect.vue'
import AppPagination from '@/shared/components/AppPagination.vue'
import * as api from '../api/courseOfferings'
import { useUiStore } from '@/stores/ui'
import type { CourseOffering } from '../types/courseOffering'

const router = useRouter()
const uiStore = useUiStore()

const offerings = ref<CourseOffering[]>([])
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0, from: null, to: null })
const loading = ref(false)
const refreshing = ref(false)
const search = ref('')
const filterStatus = ref('')

const statusOptions = [
    { value: '', label: 'All statuses' },
    { value: 'approved', label: 'Approved' },
    { value: 'pending_approval', label: 'Pending Approval' },
    { value: 'draft', label: 'Draft' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'cancelled', label: 'Cancelled' },
]
const statusVariant: Record<string, any> = {
    approved: 'success', pending_approval: 'warning', draft: 'neutral', rejected: 'danger', cancelled: 'danger',
}

async function load(page = 1) {
    loading.value = true
    try {
        const filters: Record<string, any> = {}
        if (filterStatus.value) filters.status = filterStatus.value
        if (search.value) filters.search = search.value
        const res = await api.getCourseOfferings(page, filters)
        offerings.value = res.data
        pagination.value = res.pagination
    } catch (err: any) {
        uiStore.showToast(err?.response?.data?.message || 'Failed to load course offerings.', 'error')
    } finally {
        loading.value = false
    }
}
async function handleRefresh() { refreshing.value = true; await load(pagination.value.current_page); refreshing.value = false }
watch([search, filterStatus], () => load(1))
onMounted(() => load(1))

function openOfferingExams(offering: CourseOffering) {
    router.push(`/instructor/course-offerings/${offering.id}/exams`)
}
</script>

<template>
    <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-8 min-h-[calc(100vh-68px)]">
        <ResourceToolbar title="Exams" description="Select a course offering to manage its exams."
            search-placeholder="Search by course name or code…" show-search show-filter show-refresh
            :refreshing="refreshing" @update:search="v => search = v" @refresh="handleRefresh">
            <template #filters>
                <BaseSelect v-model="filterStatus" label="Status" :options="statusOptions" />
            </template>
        </ResourceToolbar>

        <div class="rounded-xl border border-border bg-surface overflow-hidden shadow-sm">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-bg/40 border-b border-border">
                        <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Course
                        </th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">
                            Semester</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Role
                        </th>
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
                <tbody v-else-if="offerings.length" class="divide-y divide-border">
                    <tr v-for="offering in offerings" :key="offering.id"
                        class="cursor-pointer hover:bg-bg/40 transition-colors" @click="openOfferingExams(offering)">
                        <td class="px-6 py-5">
                            <p class="text-sm font-medium text-text">{{ offering.course?.name || '—' }}</p>
                            <p class="text-xs text-text/50 font-mono mt-0.5">{{ offering.course?.code || '—' }}</p>
                        </td>
                        <td class="px-6 py-5 text-sm text-text/70">{{ offering.semester?.name || '—' }}</td>
                        <td class="px-6 py-5 text-sm text-text/70 capitalize">{{ offering.my_role || '—' }}</td>
                        <td class="px-6 py-5">
                            <BaseBadge :variant="statusVariant[offering.status] || 'neutral'">
                                {{ offering.status.replace('_', '') }}
                            </BaseBadge>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <BookOpen class="w-4 h-4 text-text/30 inline-block" />
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center text-sm text-text/50">No course offerings assigned
                            yet.
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="border-t border-border px-6 py-4">
                <AppPagination :pagination="pagination" @change-page="load" />
            </div>
        </div>
    </div>
</template>
