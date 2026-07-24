<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Loader2, CheckCircle2 } from 'lucide-vue-next'
import { useQuestionBankImport } from './useQuestionBankImport'
import ImportUploadZone from './components/ImportUploadZone.vue'
import ImportReviewTable from './components/ImportReviewTable.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import AlertBanner from '@/shared/components/ui/AlertBanner.vue'

const route = useRoute()
const router = useRouter()

const courseId = computed(() => Number(route.params.courseId))
const importId = computed(() => (route.params.importId ? Number(route.params.importId) : null))

const { importRecord, isUploading, isConfirming, isSavingRow, uploadError, upload, startPolling, updateRow, deleteRow, confirm } =
    useQuestionBankImport(courseId)

onMounted(() => {
    if (importId.value) startPolling(importId.value)
})

async function handleUpload(file: File) {
    const record = await upload(file)
    if (record) {
        router.replace({ params: { ...route.params, importId: String(record.import_id) } })
        startPolling(record.import_id)
    }
}

const confirmBlockedMessage = ref<string | null>(null)
import { ref } from 'vue'

async function handleConfirm() {
    confirmBlockedMessage.value = null
    try {
        await confirm()
    } catch (err: any) {
        const data = err?.response?.data
        confirmBlockedMessage.value =
            typeof data?.message === 'string' ? data.message
                : typeof data?.errors?.message === 'string' ? data.errors.message
                    : 'Could not confirm import.'
    }
}
</script>

<template>
    <div class="space-y-8">
        <nav class="text-sm text-text/60 flex items-center gap-2">
            <span>Question Bank</span><span>/</span><span class="text-text font-medium">Import</span>
        </nav>
        <h1 class="font-display text-2xl text-accent">Import Questions</h1>

        <ImportUploadZone v-if="!importRecord || importRecord.status === 'failed'" :uploading="isUploading"
            :error="uploadError || importRecord?.failure_reason" @upload="handleUpload" />

        <div v-else-if="importRecord.status === 'pending' || importRecord.status === 'processing'"
            class="bg-surface border border-border rounded-lg p-12 flex flex-col items-center text-center">
            <Loader2 class="w-8 h-8 text-accent animate-spin mb-4" />
            <h3 class="font-semibold text-text mb-1">Processing your file…</h3>
            <p class="text-sm text-text/60">This page will update automatically once it's ready to review.</p>
        </div>

        <div v-else-if="importRecord.status === 'ready_for_review'" class="space-y-6">
            <div class="flex items-center gap-6 bg-surface border border-border rounded-lg p-5">
                <div class="flex items-center gap-2 text-emerald-600">
                    <CheckCircle2 class="w-5 h-5" /><span class="font-semibold">{{ importRecord.valid_count }}
                        valid</span>
                </div>
                <div v-if="importRecord.error_count > 0" class="flex items-center gap-2 text-red-600">
                    <span class="font-semibold">{{ importRecord.error_count }} need fixing</span>
                </div>
            </div>

            <ImportReviewTable :rows="importRecord.validated_data ?? []" :is-saving="isSavingRow"
                @update-row="({ row, data }) => updateRow(row, data)" @delete-row="(row) => deleteRow(row)" />

            <AlertBanner v-if="confirmBlockedMessage" variant="danger">{{ confirmBlockedMessage }}</AlertBanner>

            <div class="flex justify-end">
                <BaseButton :disabled="importRecord.error_count > 0 || isConfirming" @click="handleConfirm">
                    {{ isConfirming ? 'Confirming…' : 'Confirm Import' }}
                </BaseButton>
            </div>
        </div>

        <div v-else-if="importRecord.status === 'confirmed' || importRecord.status === 'approved'"
            class="bg-surface border border-border rounded-lg p-12 flex flex-col items-center text-center">
            <CheckCircle2 class="w-10 h-10 text-emerald-600 mb-4" />
            <h3 class="font-semibold text-text mb-1">
                {{ importRecord.status === 'approved' ? 'Import approved' : 'Import confirmed' }}
            </h3>
            <p class="text-sm text-text/60">{{ importRecord.valid_count }} questions added to the question bank.</p>
        </div>
    </div>
</template>
