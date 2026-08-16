import api from '@/api/axios'
import type { Pagination } from '@/shared/composables/useCrudResource'
import type { SaveSectionData, Section } from '../types/section'

interface ListResponse<T> {
  data: T[]
  pagination: Pagination
}

export async function getSections(semesterId: number, programId: number, page = 1, perPage = 12): Promise<ListResponse<Section>> {
  // ASSUMPTION: server-side filtering by semester_id/program_id — see backend gap #5
  const res = await api.get('/sections', { params: { semester_id: semesterId, program_id: programId, page, per_page: perPage } })
  return res.data
}
export async function getArchivedSections(semesterId: number, programId: number, page = 1, perPage = 12): Promise<ListResponse<Section>> {
  // ASSUMPTION: /sections/archived — see backend gap #4
  const res = await api.get('/sections/archived', { params: { semester_id: semesterId, program_id: programId, page, per_page: perPage } })
  return res.data
}
export async function createSection(data: SaveSectionData) {
  const res = await api.post('/sections', data)
  return res.data.data as Section
}
export async function updateSection(id: number, data: Partial<SaveSectionData>) {
  // ASSUMPTION — see backend gap #4
  const res = await api.patch(`/sections/${id}`, data)
  return res.data.data as Section
}
export async function archiveSection(id: number) {
  // ASSUMPTION — see backend gap #4
  await api.delete(`/sections/${id}`)
}
export async function restoreSection(id: number) {
  // ASSUMPTION — see backend gap #4
  const res = await api.post(`/sections/${id}/restore`)
  return res.data.data as Section
}
