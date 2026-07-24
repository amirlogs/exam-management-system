import { onUnmounted, ref, type Ref } from 'vue'
import * as api from './api'
import type { QuestionBankImport, QuestionRowData } from './types'

const POLL_INTERVAL_MS = 2500

export function useQuestionBankImport(courseId: Ref<number>) {
  const importRecord = ref<QuestionBankImport | null>(null)
  const isUploading = ref(false)
  const isConfirming = ref(false)
  const isSavingRow = ref(false)
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
    if (record.status !== 'pending' && record.status !== 'processing') stopPolling()
    return record
  }

  function startPolling(importId: number) {
    stopPolling()
    refresh(importId)
    pollTimer = setInterval(() => refresh(importId), POLL_INTERVAL_MS)
  }

  async function upload(file: File) {
    isUploading.value = true
    uploadError.value = null
    try {
      const record = await api.uploadImport(courseId.value, file)
      if (!record || typeof record.import_id !== 'number') {
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

  async function updateRow(rowNumber: number, data: QuestionRowData) {
    if (!importRecord.value) return
    isSavingRow.value = true
    try {
      importRecord.value = await api.updateImportRow(importRecord.value.import_id, rowNumber, data)
    } finally {
      isSavingRow.value = false
    }
  }

  async function deleteRow(rowNumber: number) {
    if (!importRecord.value) return
    isSavingRow.value = true
    try {
      importRecord.value = await api.deleteImportRow(importRecord.value.import_id, rowNumber)
    } finally {
      isSavingRow.value = false
    }
  }

  async function confirm() {
    if (!importRecord.value) return
    isConfirming.value = true
    try {
      const result = await api.confirmImport(importRecord.value.import_id)
      // confirm() only returns { count, status } — merge into existing record, don't replace it
      importRecord.value = { ...importRecord.value, status: result.status as any }
      return result
    } finally {
      isConfirming.value = false
    }
  }

  onUnmounted(stopPolling)

  return {
    importRecord,
    isUploading,
    isConfirming,
    isSavingRow,
    uploadError,
    upload,
    startPolling,
    updateRow,
    deleteRow,
    confirm,
  }
}
