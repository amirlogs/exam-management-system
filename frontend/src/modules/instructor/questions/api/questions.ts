import apiClient from '@/api/axios'
import type { Question } from '../../exams/types/exam'

function unwrap<T>(res: { data: any }): T {
  if (res.data?.success === false) throw new Error(res.data?.message || 'Request failed')
  return res.data?.data ?? res.data
}
function unwrapPaginated<T>(res: { data: any }) {
  if (res.data?.success === false) throw new Error(res.data?.message || 'Request failed')
  return { data: res.data.data as T[], pagination: res.data.pagination }
}

export function getQuestions(filters: Record<string, any> = {}, page = 1) {
  return apiClient.get('/questions', { params: { page, ...filters } }).then(unwrapPaginated<Question>)
}
export function getArchivedQuestions(filters: Record<string, any> = {}, page = 1) {
  return apiClient.get('/questions/archived', { params: { page, ...filters } }).then(unwrapPaginated<Question>)
}
export function getQuestion(id: number) {
  return apiClient.get(`/questions/${id}`).then(unwrap<Question>)
}
export function createQuestion(courseId: number, payload: any) {
  return apiClient.post(`/courses/${courseId}/questions`, payload).then(unwrap<Question>)
}
export function updateQuestion(id: number, payload: any) {
  return apiClient.patch(`/questions/${id}`, payload).then(unwrap<Question>)
}
export function archiveQuestion(id: number) {
  return apiClient.delete(`/questions/${id}`).then(unwrap<null>)
}
export function restoreQuestion(id: number) {
  return apiClient.post(`/questions/${id}/restore`).then(unwrap<Question>)
}
