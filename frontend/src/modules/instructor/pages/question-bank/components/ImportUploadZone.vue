<script setup lang="ts">
import { ref } from 'vue'
import BaseCard from '@/shared/components/ui/BaseCard.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import AlertBanner from '@/shared/components/ui/AlertBanner.vue'
import { UploadCloud, FileText, X, Download } from 'lucide-vue-next'

defineProps<{ uploading: boolean; error: string | null }>()
const emit = defineEmits<{ upload: [file: File] }>()

const isDragging = ref(false)
const fileInput = ref<HTMLInputElement>()
const localError = ref<string | null>(null)
const selectedFile = ref<File | null>(null)

function onDrop(e: DragEvent) {
    isDragging.value = false
    const file = e.dataTransfer?.files?.[0]
    if (file) validate(file)
}

function onFileSelected(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (file) validate(file)
}

function validate(file: File) {
    if (!file.name.toLowerCase().endsWith('.csv')) {
        localError.value = 'Only CSV files are supported.'
        selectedFile.value = null
        return
    }
    localError.value = null
    selectedFile.value = file
}

function clearSelection() {
    selectedFile.value = null
    if (fileInput.value) fileInput.value.value = ''
}

function confirmUpload() {
    if (selectedFile.value) emit('upload', selectedFile.value)
}

function downloadSampleCsv() {
    const headers = 'type,text,difficulty,points,options,correct_answer\n'
    const sampleData =
        'mcq,"What is the capital of France?",easy,1,"Paris, London, Berlin, Madrid",Paris\n' +
        'true_false,"The sun rises in the east.",easy,1,"True, False",True\n' +
        'essay,"Explain the process of photosynthesis.",hard,5,,\n'
    const blob = new Blob([headers + sampleData], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.setAttribute('href', url)
    link.setAttribute('download', 'question_bank_template.csv')
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
}
</script>

<template>
    <BaseCard class="p-10">
        <div class="flex items-center justify-between pb-6 mb-6 border-b border-border">
            <div>
                <h2 class="text-lg font-semibold text-text">Upload CSV File</h2>
                <p class="text-sm text-text/60">Import questions directly from a structured spreadsheet.</p>
            </div>
            <BaseButton variant="secondary" @click="downloadSampleCsv">
                <template #icon>
                    <Download :size="16" />
                </template>
                Download Template
            </BaseButton>
        </div>

        <div v-if="!selectedFile"
            class="border-2 border-dashed rounded-lg p-12 flex flex-col items-center justify-center text-center transition-colors"
            :class="isDragging ? 'border-accent bg-accent/5' : 'border-border'" @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false" @drop.prevent="onDrop">
            <div class="h-14 w-14 rounded-full bg-accent/10 text-accent flex items-center justify-center mb-4">
                <UploadCloud :size="28" />
            </div>
            <h3 class="font-semibold text-text mb-1">Drag file here or browse</h3>
            <p class="text-sm text-text/60 mb-6">Supports CSV (max 20MB)</p>
            <input ref="fileInput" type="file" accept=".csv" class="hidden" @change="onFileSelected" />
            <BaseButton @click="fileInput?.click()">Choose File</BaseButton>
        </div>

        <div v-else class="border border-border rounded-lg p-6 flex items-center justify-between gap-4 bg-surface">
            <div class="flex items-center gap-3 min-w-0">
                <div class="h-10 w-10 rounded-lg bg-accent/10 text-accent flex items-center justify-center shrink-0">
                    <FileText :size="20" />
                </div>
                <div class="min-w-0">
                    <div class="font-medium text-text truncate">{{ selectedFile.name }}</div>
                    <div class="text-xs text-text/50">{{ (selectedFile.size / 1024).toFixed(1) }} KB</div>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" :disabled="uploading" @click="clearSelection"
                    class="w-9 h-9 rounded-lg flex items-center justify-center text-text/50 hover:bg-bg hover:text-text transition-colors disabled:opacity-40">
                    <X :size="18" />
                </button>
                <BaseButton :disabled="uploading" @click="confirmUpload">
                    {{ uploading ? 'Uploading…' : 'Upload' }}
                </BaseButton>
            </div>
        </div>

        <AlertBanner v-if="error || localError" variant="danger" class="mt-6">
            {{ error || localError }}
        </AlertBanner>
    </BaseCard>
</template>
