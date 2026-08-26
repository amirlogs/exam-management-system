import { onUnmounted, ref } from 'vue'
import type { ImportHistory } from '../../exams/types/exam'
import * as importsApi from '../../imports/api/imports'

export function useImportPolling() {
  const importRecord = ref<ImportHistory | null>(null)
  const loadError = ref<string | null>(null)
  let timer: ReturnType<typeof setInterval> | null = null

  function stop() {
    if (timer) {
      clearInterval(timer)
      timer = null
    }
  }

  async function refresh(id: number) {
    try {
      const record = await importsApi.getImport(id)
      importRecord.value = record
      if (record.status !== 'pending') stop()
      return record
    } catch (err: any) {
      stop()
      loadError.value = err?.response?.data?.message || 'Could not load import.'
      throw err
    }
  }

  function start(id: number) {
    stop()
    refresh(id)
    timer = setInterval(() => refresh(id), 2500)
  }

  onUnmounted(stop)
  return { importRecord, loadError, start, stop, refresh }
}
