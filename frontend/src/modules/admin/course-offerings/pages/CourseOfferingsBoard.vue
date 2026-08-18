<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { Sparkles } from 'lucide-vue-next'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseSelect from '@/shared/components/ui/BaseSelect.vue'
import OfferingCard from '../components/OfferingCard.vue'
import SuggestionsPanel from '../components/SuggestionsPanel.vue'
import OfferingDetailModal from '../components/OfferingDetailModal.vue'
import { listOfferings, generateSuggestions, createOffering } from '../api/courseOfferings'
import { getSemesters } from '@/modules/admin/semesters/api/semesters'
import type { CourseOffering, OfferingSuggestion } from '../types/courseOffering'
import type { Semester } from '@/modules/admin/semesters/types/semester'
import { useUiStore } from '@/stores/ui'

const uiStore = useUiStore()

const semesters = ref<Semester[]>([])
const selectedSemesterId = ref<number | null>(null)
const semesterOptions = computed(() => semesters.value.map((s) => ({ value: String(s.id), label: `Semester ${s.name} · ${s.academic_year}` })))

const offerings = ref<CourseOffering[]>([])
const loading = ref(false)

const showSuggestions = ref(false)
const suggestions = ref<OfferingSuggestion[]>([])
const loadingSuggestions = ref(false)
const creatingKey = ref<string | null>(null)

const detailOffering = ref<CourseOffering | null>(null)
const showDetail = ref(false)

const columns: { key: CourseOffering['status']; label: string }[] = [
    { key: 'draft', label: 'Draft' },
    { key: 'approved', label: 'Approved' },
    { key: 'rejected', label: 'Rejected' },
    { key: 'cancelled', label: 'Cancelled' },
]
function offeringsFor(status: CourseOffering['status']) {
    return offerings.value.filter((o) => o.status === status)
}

onMounted(async () => {
    const res = await getSemesters(1, 100)
    semesters.value = res.data
    selectedSemesterId.value = semesters.value.find((s) => s.status === 'active')?.id ?? semesters.value[0]?.id ?? null
    if (selectedSemesterId.value) load()
})

async function load() {
    if (!selectedSemesterId.value) return
    loading.value = true
    try {
        const res = await listOfferings(selectedSemesterId.value)
        offerings.value = res.data
    } catch (err: any) {
        uiStore.showToast('Failed to load course offerings.', 'error')
    } finally {
        loading.value = false
    }
}
function onSemesterChange(id: string) {
    selectedSemesterId.value = Number(id)
    showSuggestions.value = false
    load()
}

async function openSuggestions() {
    if (!selectedSemesterId.value) return
    loadingSuggestions.value = true
    showSuggestions.value = true
    try {
        suggestions.value = await generateSuggestions(selectedSemesterId.value)
    } catch (err: any) {
        uiStore.showToast('Failed to generate suggestions.', 'error')
    } finally {
        loadingSuggestions.value = false
    }
}
async function createFromSuggestion(s: OfferingSuggestion) {
    if (!selectedSemesterId.value) return
    const key = `${s.course_id}-${s.program_id}`
    creatingKey.value = key
    try {
        await createOffering(s.course_id, selectedSemesterId.value)
        suggestions.value = suggestions.value.filter((x) => !(x.course_id === s.course_id && x.program_id === s.program_id))
        uiStore.showToast(`${s.course_code} offering created.`, 'success')
        await load()
    } catch (err: any) {
        uiStore.showToast(err?.response?.data?.message || 'Failed to create offering.', 'error')
    } finally {
        creatingKey.value = null
    }
}

function openDetail(offering: CourseOffering) {
    detailOffering.value = offering
    showDetail.value = true
}
function onUpdated(updated: CourseOffering) {
    const idx = offerings.value.findIndex((o) => o.id === updated.id)
    if (idx !== -1) offerings.value[idx] = updated
}
</script>

<template>
    <div class="mx-auto flex h-full w-full max-w-360 flex-col gap-6 px-6 py-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-text">Course offerings</h1>
                <p class="mt-1 text-sm text-text/60">Staff and approve course sections for the semester.</p>
            </div>
            <div class="flex items-end gap-3">
                <BaseSelect :model-value="String(selectedSemesterId ?? '')" :options="semesterOptions"
                    placeholder="Select semester" @update:model-value="onSemesterChange" />
                <BaseButton @click="openSuggestions">
                    <template #icon>
                        <Sparkles class="h-4 w-4" />
                    </template>Generate suggestions
                </BaseButton>
            </div>
        </div>

        <SuggestionsPanel v-if="showSuggestions" :suggestions="suggestions" :creating-key="creatingKey"
            @create="createFromSuggestion" @close="showSuggestions = false" />

        <div v-if="loading" class="py-16 text-center text-sm text-text/50">Loading offerings...</div>

        <div v-else class="grid flex-1 grid-cols-1 gap-4 overflow-x-auto sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="col in columns" :key="col.key" class="flex min-w-70 flex-col gap-3">
                <div class="flex items-center justify-between border-b border-border pb-2">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-text/60">{{ col.label }}</h3>
                    <span class="rounded bg-bg px-2 py-0.5 font-mono text-xs text-text/50">{{
                        offeringsFor(col.key).length
                        }}</span>
                </div>
                <div class="flex flex-1 flex-col gap-3 overflow-y-auto">
                    <OfferingCard v-for="o in offeringsFor(col.key)" :key="o.id" :offering="o" @click="openDetail(o)" />
                    <p v-if="!offeringsFor(col.key).length" class="py-6 text-center text-xs text-text/35">Nothing here.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <OfferingDetailModal v-model="showDetail" :offering="detailOffering" @updated="onUpdated" />
</template>
