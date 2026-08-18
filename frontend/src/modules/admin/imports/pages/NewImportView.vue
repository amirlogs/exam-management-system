<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { UploadCloud, FileText, X, Users, GraduationCap, Layers, HelpCircle, ClipboardList, Check } from 'lucide-vue-next'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseInput from '@/shared/components/ui/BaseInput.vue'
import BaseSelect from '@/shared/components/ui/BaseSelect.vue'
import { IMPORT_TYPE_CONFIG } from '../config/importTypes'
import { startImport } from '../api/imports'
import { getSemesters } from '@/modules/admin/semesters/api/semesters'
import { getCourses } from '@/modules/admin/courses/api/courses'
import type { ImportType } from '../types/import'
import { useUiStore } from '@/stores/ui'

const router = useRouter()
const uiStore = useUiStore()

const TYPE_ICONS: Record<ImportType, any> = {
    students: Users,
    instructors: GraduationCap,
    sections: Layers,
    questions: HelpCircle,
    enrollments: ClipboardList,
}

const type = ref<ImportType | null>(null)
const config = computed(() => (type.value ? IMPORT_TYPE_CONFIG[type.value] : null))

const context = reactive<Record<string, any>>({})
const semesterOptions = ref<{ value: string; label: string }[]>([])
const courseOptions = ref<{ value: string; label: string }[]>([])

watch(type, () => {
    Object.keys(context).forEach((k) => delete context[k])
})

onMounted(async () => {
    const [semRes, courseRes] = await Promise.all([getSemesters(1, 100), getCourses(1, 200)])
    semesterOptions.value = semRes.data.map((s) => ({ value: String(s.id), label: `Semester ${s.name} · ${s.academic_year}` }))
    courseOptions.value = courseRes.data.map((c) => ({ value: String(c.id), label: `${c.code} — ${c.name}` }))
})

const isDragging = ref(false)
const fileInput = ref<HTMLInputElement>()
const selectedFile = ref<File | null>(null)
const localError = ref<string | null>(null)
const uploading = ref(false)

function validate(file: File) {
    if (!file.name.toLowerCase().endsWith('.csv')) {
        localError.value = 'Only CSV files are supported.'
        selectedFile.value = null
        return
    }
    localError.value = null
    selectedFile.value = file
}
function onDrop(e: DragEvent) {
    isDragging.value = false
    const file = e.dataTransfer?.files?.[0]
    if (file) validate(file)
}
function onFileSelected(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (file) validate(file)
}
function clearSelection() {
    selectedFile.value = null
    if (fileInput.value) fileInput.value.value = ''
}

const canSubmit = computed(() => {
    if (!type.value || !selectedFile.value) return false
    return config.value!.contextFields.filter((f) => f.required).every((f) => context[f.key] !== undefined && context[f.key] !== '')
})

async function submit() {
    if (!canSubmit.value || !type.value || !selectedFile.value) return
    uploading.value = true
    try {
        const record = await startImport(type.value, selectedFile.value, { ...context })
        uiStore.showToast('Import started — processing your file.', 'success')
        router.push({ name: 'admin-import-detail', params: { id: record.id } })
    } catch (err: any) {
        uiStore.showToast(err?.response?.data?.message || 'Failed to start import.', 'error')
    } finally {
        uploading.value = false
    }
}
</script>

<template>
    <div class="mx-auto w-full max-w-260 space-y-6 px-6 py-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-text">New import</h1>
            <p class="mt-1 text-sm text-text/60">Choose what you're importing, then upload a CSV file.</p>
        </div>

        <!-- Step 1: type — real cards, not pills -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <button v-for="(cfg, key) in IMPORT_TYPE_CONFIG" :key="key" type="button"
                class="relative flex flex-col items-start gap-3 rounded-md border p-5 text-left transition-colors"
                :class="type === key ? 'border-accent bg-accent/5' : 'border-border bg-surface hover:border-accent/50'"
                @click="type = key as ImportType">
                <div class="flex w-full items-start justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-md bg-accent/10 text-accent">
                        <component :is="TYPE_ICONS[key as ImportType]" class="h-5 w-5" />
                    </div>
                    <Check v-if="type === key" class="h-5 w-5 text-accent" />
                </div>
                <div>
                    <h3 class="font-semibold text-text">{{ cfg.label }}</h3>
                    <p class="mt-1 text-xs leading-relaxed text-text/55">{{ cfg.description }}</p>
                </div>
            </button>
        </div>

        <!-- Step 2: context + file, only once a type is chosen -->
        <div v-if="type" class="space-y-6 rounded-md border border-border bg-surface p-8">
            <div class="flex items-start justify-between gap-4 rounded-md border border-border bg-bg p-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-text/50">Expected CSV columns for {{
                        config!.label }}</p>
                    <p class="mt-1.5 font-mono text-xs text-text/70">{{ config!.expectedColumns.join(', ') || '—' }}</p>
                </div>
            </div>

            <div v-if="config!.contextFields.length"
                class="grid grid-cols-1 gap-6 rounded-md border border-border bg-bg p-6 md:grid-cols-2">
                <div v-for="field in config!.contextFields" :key="field.key">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/60">{{ field.label
                        }}</label>
                    <BaseSelect v-if="field.kind === 'semester_select'" v-model="context[field.key]"
                        :options="semesterOptions" placeholder="Select semester" />
                    <BaseSelect v-else-if="field.kind === 'course_select'" v-model="context[field.key]"
                        :options="courseOptions" placeholder="Select course" />
                    <BaseInput v-else v-model="context[field.key]"
                        :type="field.kind === 'number' ? 'number' : 'text'" />
                </div>
            </div>
            <p v-else class="text-sm text-text/50">This import type needs no additional context — just upload the file.
            </p>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/60">Source file</label>
                <div v-if="!selectedFile"
                    class="flex flex-col items-center justify-center rounded-md border-2 border-dashed p-12 text-center transition-colors"
                    :class="isDragging ? 'border-accent bg-accent/5' : 'border-border'"
                    @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                    @drop.prevent="onDrop">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-accent/10 text-accent">
                        <UploadCloud class="h-6 w-6" />
                    </div>
                    <h3 class="mb-1 font-semibold text-text">Drag & drop your CSV here</h3>
                    <p class="mb-6 text-sm text-text/60">Supports .csv files up to 50MB.</p>
                    <input ref="fileInput" type="file" accept=".csv" class="hidden" @change="onFileSelected" />
                    <BaseButton variant="secondary" @click="fileInput?.click()">Select file</BaseButton>
                </div>
                <div v-else class="flex items-center justify-between gap-4 rounded-md border border-border bg-bg p-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-accent/10 text-accent">
                            <FileText class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-text">{{ selectedFile.name }}</p>
                            <p class="text-xs text-text/50">{{ (selectedFile.size / 1024).toFixed(1) }} KB</p>
                        </div>
                    </div>
                    <button type="button" :disabled="uploading"
                        class="rounded-md p-1.5 text-text/50 hover:bg-text/5 hover:text-text disabled:opacity-40"
                        @click="clearSelection">
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <p v-if="localError" class="text-sm text-error">{{ localError }}</p>

            <div class="flex justify-end border-t border-border pt-6">
                <BaseButton :loading="uploading" :disabled="!canSubmit" @click="submit">Upload and process</BaseButton>
            </div>
        </div>
    </div>
</template>
