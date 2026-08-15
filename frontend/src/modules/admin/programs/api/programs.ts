import api from '@/api/axios'
import type { Pagination } from '@/shared/composables/useCrudResource'
import type { CreateProgramData, Program, UpdateProgramData } from '../types/program'

interface ListResponse<T> {
  data: T[]
  pagination: Pagination
}

export async function getPrograms(page = 1, perPage = 10): Promise<ListResponse<Program>> {
  const res = await api.get('/programs', { params: { page, per_page: perPage } })
  return res.data
}
export async function getArchivedPrograms(page = 1, perPage = 10): Promise<ListResponse<Program>> {
  const res = await api.get('/programs/archived', { params: { page, per_page: perPage } })
  return res.data
}
export async function createProgram(data: CreateProgramData) {
  const res = await api.post('/programs', data)
  return res.data.data as Program
}
export async function updateProgram(id: number, data: UpdateProgramData) {
  const res = await api.patch(`/programs/${id}`, data)
  return res.data.data as Program
}
export async function deleteProgram(id: number) {
  await api.delete(`/programs/${id}`)
}
// ASSUMPTION: your doc shows "post /programs/3" for restore with no /restore suffix —
// treating that as a copy-paste typo and matching every other module's convention.
// Confirm the real endpoint if this 404s.
export async function restoreProgram(id: number) {
  const res = await api.post(`/programs/${id}/restore`)
  return res.data.data as Program
}
