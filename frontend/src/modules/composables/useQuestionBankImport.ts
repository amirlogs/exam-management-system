import * as api from '@/modules/teaching/pages/question-bank/api'
import type {
  QuestionBankImport,
  QuestionRowData,
} from '@/modules/teaching/pages/question-bank/types'
import { onUnmounted, ref, type Ref } from 'vue'

const POLL_INTERVAL_MS = 2500

export function useQuestionBankImport(courseId: Ref<number>) {
  const importRecord = ref<QuestionBankImport | null>(null)
  const isUploading = ref(false)
  const uploadError = ref<string | null>(null)
  let pollTimer: ReturnType<typeof setInterval> | null = null

  function stopPolling() {
    if (pollTimer) {
      clearInterval(pollTimer)
      pollTimer = null
    }
  }

  async function refresh(importId: number) {
    const record = await api.getImport(importId)
    importRecord.value = record
    if (record.status !== 'pending') stopPolling() // was: !== 'processing'
    return record
  }

  function startPolling(importId: number) {
    stopPolling()
    refresh(importId)
    pollTimer = setInterval(() => refresh(importId), POLL_INTERVAL_MS)
  }

  // useQuestionBankImport.ts
  // useQuestionBankImport.ts
  async function upload(file: File) {
    isUploading.value = true
    uploadError.value = null
    try {
      const record = await api.uploadImport(courseId.value, file)
      if (!record || typeof record.id !== 'number') {
        // This will show up ON SCREEN via uploadError, not just console
        uploadError.value = `Unexpected response shape: ${JSON.stringify(record)}`
        return null
      }
      importRecord.value = record
      return record
    } catch (err: any) {
      uploadError.value =
        err?.response?.data?.message ?? err?.message ?? 'Upload failed. Please try again.'
      return null
    } finally {
      isUploading.value = false
    }
  }

  async function updateRow(rowIndex: number, data: QuestionRowData) {
    if (!importRecord.value) return
    importRecord.value = await api.updateImportRow(importRecord.value.id, rowIndex, data)
  }

  async function confirm() {
    if (!importRecord.value) return
    importRecord.value = await api.confirmImport(importRecord.value.id)
  }

  onUnmounted(stopPolling)

  return { importRecord, isUploading, uploadError, upload, startPolling, updateRow, confirm }
}
