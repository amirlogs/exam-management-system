import api from '@/api/axios'
import type { Pagination } from '@/shared/composables/useCrudResource'
import type { ImportHistory, ImportType } from '../types/import'

interface ListResponse<T> {
  data: T[]
  pagination: Pagination
}

export async function listImports(type?: ImportType, page = 1, perPage = 12): Promise<ListResponse<ImportHistory>> {
  const res = await api.get('/imports', { params: { type, page, per_page: perPage } })
  return res.data
}
export async function getImport(id: number): Promise<ImportHistory> {
  const res = await api.get(`/imports/${id}`)
  return res.data.data
}
export async function startImport(type: ImportType, file: File, context: Record<string, any>): Promise<ImportHistory> {
  const form = new FormData()
  form.append('file', file)
  form.append('context', JSON.stringify(context))
  const res = await api.post(`/imports/${type}`, form, { headers: { 'Content-Type': 'multipart/form-data' } })
  return res.data.data
}
export async function updateImportRow(importId: number, rowIndex: string | number, data: Record<string, any>): Promise<ImportHistory> {
  const res = await api.patch(`/imports/${importId}/rows/${rowIndex}`, data)
  return res.data.data
}
export async function deleteImportRow(importId: number, rowIndex: string | number): Promise<ImportHistory> {
  const res = await api.delete(`/imports/${importId}/rows/${rowIndex}`)
  return res.data.data
}
export async function confirmImport(importId: number): Promise<ImportHistory> {
  const res = await api.post(`/imports/${importId}/confirm`)
  return res.data.data
}
export async function cancelImport(importId: number): Promise<void> {
  // DELETE /imports/{id} 
  await api.delete(`/imports/${importId}`)
}
