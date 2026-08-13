import apiClient from '@/api/axios'
import type { ImportFlagsSummary, QuestionBankImport, QuestionFlag } from './types'

function unwrap<T>(res: { data: any }): T {
  if (res.data?.success === false) throw new Error(res.data?.message || 'Request failed')
  return res.data?.data ?? res.data
}

function normalizeImport(raw: any): QuestionBankImport {
  return { ...raw, import_id: raw.import_id ?? raw.id }
}


// ── Import lifecycle ──────────────────────────────────────────────
export async function listImports() {
  const res = await apiClient.get('/question-bank-imports')
  const raw = unwrap<any[]>(res)
  return raw.map(normalizeImport)
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
  const res = await apiClient.get(`/question-bank-imports/${importId}`)
  return normalizeImport(unwrap<any>(res))
}

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

export async function confirmImport(importId: number) {
  const res = await apiClient.post(`/question-bank-imports/${importId}/confirm`)
  return unwrap<{ count: number; status: string }>(res)
}

// ── Flags ──────────────────────────────────────────────────────────

export async function flagQuestion(questionId: number, comment: string) {
  const res = await apiClient.post(`/questions/${questionId}/flags`, { comment })
  return unwrap<QuestionFlag>(res)
}

export async function getQuestionFlags(questionId: number) {
  const res = await apiClient.get(`/questions/${questionId}/flags`)
  return unwrap<QuestionFlag[]>(res)
}

export async function getImportQuestions(importId: number) {
  const res = await apiClient.get(`/questions/${importId}`)
  return unwrap<any[]>(res)
}
export async function getImportFlagsSummary(importId: number) {
  const res = await apiClient.get(`/question-bank-imports/${importId}/falg`) // yes, "falg" — matches the backend's actual route
  return unwrap<ImportFlagsSummary>(res)
}

// Resolves a flag without editing the question
export async function resolveFlag(flagId: number) {
  const res = await apiClient.patch(`/question-flags/${flagId}/resolve`)
  return unwrap<QuestionFlag>(res)
}

export async function updateQuestionAndResolveFlag(
  questionId: number,
  flagId: number,
  data: {
    type: string
    text: string
    options?: string[]
    correct_answer?: string
    difficulty: string
    points: number
  },
) {
  const res = await apiClient.patch(`/questions/${questionId}/flags/${flagId}`, data)
  return unwrap<any>(res)
}
