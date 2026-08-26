import apiClient from '@/api/axios'
import type { ImportHistory } from '../../exams/types/exam'

function unwrap<T>(res: { data: any }): T {
  if (res.data?.success === false) throw new Error(res.data?.message || 'Request failed')
  return res.data?.data ?? res.data
}

export function startImport(type: string, file: File, context: Record<string, any>) {
  const formData = new FormData()
  formData.append('file', file)
  formData.append('context', JSON.stringify(context))
  return apiClient.post(`/imports/${type}`, formData, { headers: { 'Content-Type': 'multipart/form-data' } }).then(unwrap<ImportHistory>)
}
export function getImport(id: number) {
  return apiClient.get(`/imports/${id}`).then(unwrap<ImportHistory>)
}
export function updateImportRow(importId: number, rowNumber: string | number, data: Record<string, any>) {
  return apiClient.patch(`/imports/${importId}/rows/${rowNumber}`, data).then(unwrap<ImportHistory>)
}
export function deleteImportRow(importId: number, rowNumber: string | number) {
  return apiClient.delete(`/imports/${importId}/rows/${rowNumber}`).then(unwrap<ImportHistory>)
}
export function confirmImport(importId: number) {
  return apiClient.post(`/imports/${importId}/confirm`).then(unwrap<ImportHistory>)
}
export function cancelImport(importId: number) {
  return apiClient.delete(`/imports/${importId}`).then(unwrap<null>)
}
