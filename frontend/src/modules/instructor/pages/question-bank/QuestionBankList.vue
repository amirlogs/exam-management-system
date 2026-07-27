<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Upload, ChevronRight } from 'lucide-vue-next'
import * as api from './api'
import BaseCard from '@/shared/components/ui/BaseCard.vue'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import type { QuestionBankImport } from './types'
import { SEEDED_COURSES } from '../../data/coursesSeed'
import BackButton from '@/shared/components/ui/BackButton.vue'

const route = useRoute()
const router = useRouter()
const imports = ref<QuestionBankImport[]>([])
const isLoading = ref(true)
const loadError = ref<string | null>(null)

const filterCourseId = computed(() => (route.params.courseId ? Number(route.params.courseId) : null))
const visibleImports = computed(() =>
    filterCourseId.value ? imports.value.filter(i => i.course_id === filterCourseId.value) : imports.value
)

const statusVariant: Record<string, 'info' | 'danger' | 'success' | 'dark' | 'neutral'> = {
    pending: 'dark', processing: 'dark', ready_for_review: 'info', confirmed: 'success', approved: 'success', failed: 'danger',
}
const statusLabel: Record<string, string> = {
    pending: 'Processing', processing: 'Processing', ready_for_review: 'Ready for Review', confirmed: 'Confirmed', approved: 'Approved', failed: 'Failed',
}

async function load() {
    isLoading.value = true
    loadError.value = null
    try {
        imports.value = await api.listImports()
    } catch (err: any) {
        loadError.value = err?.response?.data?.message || 'Could not load imports.'
    } finally {
        isLoading.value = false
    }
}

function openImport(imp: QuestionBankImport) {
    router.push(`/instructor/courses/${imp.course_id}/question-bank/import/${imp.import_id}`)
}

function startNewImport() {
    const courseId = filterCourseId.value ?? SEEDED_COURSES[0].id
    router.push(`/instructor/courses/${courseId}/question-bank/import`)
}

onMounted(load)
</script>

<template>
    <BackButton fallback="/instructor/courses" />
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-display text-2xl text-accent">Question Bank</h1>
                <p class="text-sm text-text/60 mt-1">All question imports across your courses.</p>
            </div>
            <BaseButton @click="startNewImport">
                <template #icon>
                    <Upload class="w-4 h-4" />
                </template>
                New Import
            </BaseButton>
        </div>

        <div v-if="isLoading" class="bg-surface border border-border rounded-lg p-12 text-center text-text/50">Loading
            imports…
        </div>
        <div v-else-if="loadError" class="bg-surface border border-border rounded-lg p-12 text-center text-red-600">{{
            loadError
            }}</div>
        <div v-else-if="visibleImports.length === 0"
            class="bg-surface border border-border rounded-lg p-12 text-center">
            <p class="text-text/60 mb-4">No question imports yet.</p>
            <BaseButton @click="startNewImport">Start your first import</BaseButton>
        </div>

        <BaseCard v-else :padded="false">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-bg border-b border-border">
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-text/60">Import
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-text/60">Course
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-text/60">Questions
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-text/60">Status
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-text/60">Uploaded
                        </th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="imp in visibleImports" :key="imp.import_id"
                        class="border-b border-border last:border-0 hover:bg-bg/50 cursor-pointer transition-colors"
                        @click="openImport(imp)">
                        <td class="px-4 py-4 font-mono text-text/70">#{{ imp.import_id }}</td>
                        <td class="px-4 py-4 text-text">Course {{ imp.course_id }}</td>
                        <td class="px-4 py-4 text-text/70">
                            <span class="text-emerald-600 font-medium">{{ imp.valid_count }} valid</span>
                            <span v-if="imp.error_count > 0" class="text-red-600 font-medium ml-2">{{ imp.error_count }}
                                errors</span>
                        </td>
                        <td class="px-4 py-4">
                            <BaseBadge :variant="statusVariant[imp.status] || 'neutral'">{{ statusLabel[imp.status] ||
                                imp.status }}</BaseBadge>
                        </td>
                        <td class="px-4 py-4 text-text/50 text-xs">{{ new Date(imp.created_at).toLocaleDateString() }}
                        </td>
                        <td class="px-4 py-4 text-right">
                            <ChevronRight class="w-4 h-4 text-text/30 inline-block" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </BaseCard>
    </div>
</template>
