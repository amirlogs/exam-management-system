import { handleApiError } from '@/shared/utils/apiError'
import { useUiStore } from '@/stores/ui'
import { computed, onUnmounted, ref } from 'vue'
import { cancelImport, confirmImport, deleteImportRow, getImport, startImport, updateImportRow } from '../api/imports'
import type { ImportHistory, ImportType } from '../types/import'

export function useImportWizard(type: ImportType) {
  const uiStore = useUiStore()

  const record = ref<ImportHistory | null>(null)
  const isUploading = ref(false)
  const isConfirming = ref(false)
  const isCancelling = ref(false)
  const savingRow = ref<string | null>(null)
  const uploadError = ref<string | null>(null)

  let pollTimer: ReturnType<typeof setTimeout> | null = null

  function stopPolling() {
    if (pollTimer) clearTimeout(pollTimer)
    pollTimer = null
  }

  function startPolling(id: number) {
    stopPolling()
    const poll = async () => {
      try {
        const res = await getImport(id)
        record.value = res
        if (res.status === 'pending' || res.status === 'processing') {
          pollTimer = setTimeout(poll, 1500)
        }
      } catch (err) {
        handleApiError(err, uiStore, undefined, 'Failed to check import status.')
      }
    }
    poll()
  }

  async function upload(file: File, context: Record<string, any>) {
    isUploading.value = true
    uploadError.value = null
    try {
      const res = await startImport(type, file, context)
      record.value = res
      startPolling(res.id)
      return res
    } catch (err: any) {
      uploadError.value = err?.response?.data?.message || 'Failed to upload file.'
      return null
    } finally {
      isUploading.value = false
    }
  }

  async function updateRow(rowIndex: string, data: Record<string, any>) {
    if (!record.value) return
    savingRow.value = rowIndex
    try {
      record.value = await updateImportRow(record.value.id, rowIndex, data)
      uiStore.showToast('Row updated.', 'success')
    } catch (err) {
      handleApiError(err, uiStore, undefined, 'Failed to update row.')
    } finally {
      savingRow.value = null
    }
  }

  async function deleteRow(rowIndex: string) {
    if (!record.value) return
    savingRow.value = rowIndex
    try {
      record.value = await deleteImportRow(record.value.id, rowIndex)
      uiStore.showToast('Row removed.', 'success')
    } catch (err) {
      handleApiError(err, uiStore, undefined, 'Failed to delete row.')
    } finally {
      savingRow.value = null
    }
  }

  async function confirm() {
    if (!record.value) return
    isConfirming.value = true
    try {
      record.value = await confirmImport(record.value.id)
      uiStore.showToast('Import confirmed.', 'success')
    } catch (err) {
      handleApiError(err, uiStore, undefined, 'Failed to confirm import.')
      throw err
    } finally {
      isConfirming.value = false
    }
  }

  async function cancel() {
    if (!record.value) return
    isCancelling.value = true
    try {
      await cancelImport(record.value.id)
      uiStore.showToast('Import cancelled.', 'success')
      record.value = null
    } catch (err) {
      handleApiError(err, uiStore, undefined, 'Failed to cancel import.')
    } finally {
      isCancelling.value = false
    }
  }

  onUnmounted(stopPolling)

  const rowEntries = computed(() => (record.value?.validated_data ? Object.entries(record.value.validated_data) : []))
  async function loadExisting(id: number) {
    try {
      const res = await getImport(id)
      record.value = res
      if (res.status === 'pending' || res.status === 'processing') {
        startPolling(id)
      }
    } catch (err) {
      handleApiError(err, uiStore, undefined, 'Failed to load import.')
    }
  }
  return {
    record,
    isUploading,
    isConfirming,
    isCancelling,
    savingRow,
    uploadError,
    rowEntries,
    upload,
    startPolling,
    updateRow,
    deleteRow,
    confirm,
    cancel,
    loadExisting,
  }
}
