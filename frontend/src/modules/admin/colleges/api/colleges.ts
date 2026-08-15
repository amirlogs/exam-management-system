import api from '@/api/axios'
import type { Pagination } from '@/shared/composables/useCrudResource'
import type { College, CreateCollegeData, UpdateCollegeData } from '../types/college'

interface ListResponse<T> {
  data: T[]
  pagination: Pagination
}

export async function getColleges(page = 1, perPage = 10): Promise<ListResponse<College>> {
  const res = await api.get('/colleges', { params: { page, per_page: perPage } })
  return res.data
}
export async function getArchivedColleges(page = 1, perPage = 10): Promise<ListResponse<College>> {
  const res = await api.get('/colleges/archived', { params: { page, per_page: perPage } })
  return res.data
}
export async function createCollege(data: CreateCollegeData) {
  const res = await api.post('/colleges', data)
  return res.data.data as College
}
export async function updateCollege(id: number, data: UpdateCollegeData) {
  const res = await api.patch(`/colleges/${id}`, data)
  return res.data.data as College
}
export async function deleteCollege(id: number) {
  await api.delete(`/colleges/${id}`)
}
export async function restoreCollege(id: number) {
  const res = await api.post(`/colleges/${id}/restore`)
  return res.data.data as College
}
