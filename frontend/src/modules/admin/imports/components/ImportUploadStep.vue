<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { UploadCloud, FileText, X } from 'lucide-vue-next'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseInput from '@/shared/components/ui/BaseInput.vue'
import BaseSelect from '@/shared/components/ui/BaseSelect.vue'
import type { ImportType } from '../types/import'
import { IMPORT_TYPE_CONFIG } from '../config/importTypes'
import { getSemesters } from '@/modules/admin/semesters/api/semesters'
import { getCourses } from '@/modules/admin/courses/api/courses'

const props = defineProps<{ type: ImportType; uploading: boolean; error: string | null }>()
const emit = defineEmits<{ upload: [{ file: File; context: Record<string, any> }] }>()

const config = computed(() => IMPORT_TYPE_CONFIG[props.type])
const context = reactive<Record<string, any>>({})

const semesterOptions = ref<{ value: string; label: string }[]>([])
const courseOptions = ref<{ value: string; label: string }[]>([])

onMounted(async () => {
    if (config.value.contextFields.some((f) => f.kind === 'semester_select')) {
        const res = await getSemesters(1, 100)
        semesterOptions.value = res.data.map((s) => ({ value: String(s.id), label: `Semester ${s.name} · ${s.academic_year}` }))
    }
    if (config.value.contextFields.some((f) => f.kind === 'course_select')) {
        const res = await getCourses(1, 200)
        courseOptions.value = res.data.map((c) => ({ value: String(c.id), label: `${c.code} — ${c.name}` }))
    }
})

const isDragging = ref(false)
const fileInput = ref<HTMLInputElement>()
const localError = ref<string | null>(null)
const selectedFile = ref<File | null>(null)

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
    if (!selectedFile.value) return false
    return config.value.contextFields.filter((f) => f.required).every((f) => context[f.key] !== undefined && context[f.key] !== '')
})

function submit() {
    if (!selectedFile.value || !canSubmit.value) return
    emit('upload', { file: selectedFile.value, context: { ...context } })
}
</script>

<template>
    <div class="rounded-md border border-border bg-surface p-8 space-y-8">
        <div v-if="config.contextFields.length"
            class="grid grid-cols-1 gap-6 rounded-md border border-border bg-bg p-6 md:grid-cols-2">
            <div v-for="field in config.contextFields" :key="field.key">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/60">{{ field.label
                    }}</label>
                <BaseSelect v-if="field.kind === 'semester_select'" v-model="context[field.key]"
                    :options="semesterOptions" placeholder="Select semester" />
                <BaseSelect v-else-if="field.kind === 'course_select'" v-model="context[field.key]"
                    :options="courseOptions" placeholder="Select course" />
                <BaseInput v-else v-model="context[field.key]" :type="field.kind === 'number' ? 'number' : 'text'" />
            </div>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/60">Source file</label>

            <div v-if="!selectedFile"
                class="flex flex-col items-center justify-center rounded-md border-2 border-dashed p-12 text-center transition-colors"
                :class="isDragging ? 'border-accent bg-accent/5' : 'border-border'"
                @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false" @drop.prevent="onDrop">
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

        <p v-if="error || localError" class="text-sm text-error">{{ error || localError }}</p>

        <div class="flex justify-end gap-2 border-t border-border pt-6">
            <BaseButton :loading="uploading" :disabled="!canSubmit" @click="submit">Upload and process</BaseButton>
        </div>
    </div>
</template>
