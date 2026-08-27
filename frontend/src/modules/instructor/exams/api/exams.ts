import apiClient from '@/api/axios';

function unwrap<T>(res: { data: any }): T {
  if (res.data?.success === false) throw new Error(res.data?.message || 'Request failed');
  return res.data?.data ?? res.data;
}
function unwrapPaginated<T>(res: { data: any }) {
  if (res.data?.success === false) throw new Error(res.data?.message || 'Request failed');
  return { data: res.data.data as T[], pagination: res.data.pagination };
}

export function getExams(courseOfferingId: number, page = 1, filters: Record<string, any> = {}) {
  return apiClient.get(`/course-offerings/${courseOfferingId}/exams`, { params: { page, ...filters } }).then(unwrapPaginated<import('../types/exam').Exam>);
}
export function getExam(examId: number) {
  return apiClient.get(`/exams/${examId}`).then(unwrap<import('../types/exam').Exam>);
}
export function createExam(courseOfferingId: number, payload: { title: string; type: string; duration_minutes: number; composition: Record<string, { marks_each?: number }> }) {
  return apiClient.post(`/course-offerings/${courseOfferingId}/exams`, payload).then(unwrap<import('../types/exam').Exam>);
}
export function updateComposition(examId: number, composition: Record<string, { marks_each?: number }>) {
  return apiClient.patch(`/exams/${examId}/composition`, { composition }).then(unwrap<import('../types/exam').Exam>);
}
export function addQuestion(examId: number, questionId: number, marks?: number) {
  return apiClient.post(`/exams/${examId}/questions`, { question_id: questionId, ...(marks ? { marks } : {}) }).then(unwrap<any>);
}

export function getExamQuestions(examId: number, page = 1, filters: Record<string, any> = {}) {
  return apiClient.get(`/exams/${examId}/questions`, { params: { page, ...filters } }).then(unwrapPaginated<import('../types/exam').Question>);
}
export function removeExamQuestion(examId: number, examQuestionId: number) {
  return apiClient.delete(`/exams/${examId}/questions/${examQuestionId}`).then(unwrap<null>);
}
export function submitApproval(examId: number) {
  return apiClient.post(`/exams/${examId}/submit-approval`).then(unwrap<import('../types/exam').Exam>);
}
export function revertToDraft(examId: number) {
  return apiClient.post(`/exams/${examId}/revert-to-draft`).then(unwrap<import('../types/exam').Exam>);
}
export function approveExam(examId: number) {
  return apiClient.post(`/exams/${examId}/approve`).then(unwrap<import('../types/exam').Exam>);
}
export function rejectExam(examId: number, reason: string) {
  return apiClient.post(`/exams/${examId}/reject`, { reason }).then(unwrap<import('../types/exam').Exam>);
}
export function scheduleExam(examId: number, scheduledStart: string) {
  return apiClient.post(`/exams/${examId}/schedule`, { scheduled_start: scheduledStart }).then(unwrap<import('../types/exam').Exam>);
}
export function updateSchedule(examId: number, payload: { scheduled_start?: string; duration_minutes?: number }) {
  return apiClient.patch(`/exams/${examId}/schedule`, payload).then(unwrap<import('../types/exam').Exam>);
}
export function extendTime(examId: number, minutes: number) {
  return apiClient.post(`/exams/${examId}/extend-time`, { duration_minutes: minutes }).then(unwrap<import('../types/exam').Exam>);
}
export function publishExam(examId: number) {
  return apiClient.post(`/exams/${examId}/publish`).then(unwrap<import('../types/exam').Exam>);
}
export function endExam(examId: number) {
  return apiClient.post(`/exams/${examId}/end`).then(unwrap<import('../types/exam').Exam>);
}
export function cancelExam(examId: number) {
  return apiClient.post(`/exams/${examId}/cancel`).then(unwrap<import('../types/exam').Exam>);
}
export function archiveExam(examId: number) {
  return apiClient.delete(`/exams/${examId}`).then(unwrap<import('../types/exam').Exam>);
}

export function addQuestionsBulk(examId: number, questions: { question_id: number; marks?: number }[]) {
  return apiClient.post(`/exams/${examId}/questions/bulk`, { questions }).then(unwrap<{ created: any[]; errors: Record<number, string[]> }>);
}
