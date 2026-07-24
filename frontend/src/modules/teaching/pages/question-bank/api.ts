import apiClient from '@/api/axios'
import type { QuestionBankImport, QuestionRowData } from './types'

function unwrap<T>(res: { data: any }): T {
  if (res.data?.success === false) throw new Error(res.data?.message || 'Request failed')
  return res.data?.data ?? res.data
}

// Backend uses `import_id` on upload, `id` on getImport — normalize both to `import_id`.
function normalizeImport(raw: any): QuestionBankImport {
  return { ...raw, import_id: raw.import_id ?? raw.id }
}

export async function uploadImport(courseId: number, file: File) {
  const formData = new FormData()
  formData.append('file', file)
  const res = await apiClient.post(`/courses/${courseId}/question-bank-imports`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return normalizeImport(unwrap<any>(res))
}

export async function getImport(importId: number) {
  const res = await apiClient.post(`/question-bank-imports/${importId}`)
  return normalizeImport(unwrap<any>(res))
}

// rowNumber = the row's `row` field value (e.g. 9), NOT its array index
export async function updateImportRow(importId: number, rowNumber: number, data: QuestionRowData) {
  const res = await apiClient.patch(
    `/question-bank-imports/${importId}/questions/${rowNumber}`,
    data,
  )
  return normalizeImport(unwrap<any>(res))
}

export async function deleteImportRow(importId: number, rowNumber: number) {
  const res = await apiClient.delete(`/question-bank-imports/${importId}/questions/${rowNumber}`)
  return normalizeImport(unwrap<any>(res))
}

// Returns { count, status } — NOT a full import record
export async function confirmImport(importId: number) {
  const res = await apiClient.post(`/question-bank-imports/${importId}/confirm`)
  return unwrap<{ count: number; status: string }>(res)
}

export async function flagQuestion(questionId: number, comment: string) {
  const res = await apiClient.post(`/questions/${questionId}/flags`, { comment })
  return unwrap<any>(res)
}

export async function getQuestionFlags(questionId: number) {
  const res = await apiClient.get(`/questions/${questionId}/flags`)
  return unwrap<any[]>(res)
}

export async function resolveFlag(flagId: number) {
  const res = await apiClient.patch(`/question-flags/${flagId}/resolve`)
  return unwrap<any>(res)
}
