import api from '@/api/axios'
import type { Pagination } from '@/shared/composables/useCrudResource'

export async function getQuestions(page = 1, perPage = 12, filters: Record<string, any> = {}) {
  const res = await api.get('/questions', { params: { page, per_page: perPage, ...filters } })
  return res.data as { data: any[]; pagination: Pagination }
}
export async function getArchivedQuestions(page = 1, perPage = 12, filters: Record<string, any> = {}) {
  const res = await api.get('/questions/archived', { params: { page, per_page: perPage, ...filters } })
  return res.data as { data: any[]; pagination: Pagination }
}
export async function archiveQuestion(id: number) {
  await api.delete(`/questions/${id}`)
}
export async function restoreQuestion(id: number) {
  const res = await api.post(`/questions/${id}/restore`)
  return res.data.data
}
