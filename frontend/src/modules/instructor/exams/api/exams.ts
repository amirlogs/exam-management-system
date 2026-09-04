import api from '@/api/axios';

import type {
  AddExamQuestionPayload,
  ApiResponse,
  BulkExamQuestionPayload,
  CreateExamPayload,
  Exam,
  ExamQuestion,
  Pagination,
  PaginatedResponse,
  ScheduleExamPayload,
  UpdateSchedulePayload,
} from '../types/exam';

interface RawResponse<T> {
  success: boolean;
  message: string;
  data: T;
  pagination?: Pagination;
  errors: unknown;
}

export async function listExams(
  courseOfferingId: number,
  page = 1,
  perPage = 100,
  filters: {
    type?: string;
    status?: string;
  } = {},
) {
  const response = await api.get<PaginatedResponse<Exam>>(`/course-offerings/${courseOfferingId}/exams`, {
    params: {
      page,
      per_page: perPage,
      ...filters,
    },
  });

  return response.data;
}

export async function getExam(examId: number) {
  const response = await api.get<ApiResponse<Exam>>(`/exams/${examId}`);

  return response.data;
}

export async function createExam(courseOfferingId: number, payload: CreateExamPayload) {
  const response = await api.post<ApiResponse<Exam>>(`/course-offerings/${courseOfferingId}/exams`, payload);

  return response.data;
}

export async function updateExamComposition(examId: number, composition: Record<string, { marks_each?: number }>) {
  const response = await api.patch<ApiResponse<Exam>>(`/exams/${examId}/composition`, {
    composition,
  });

  return response.data;
}

export async function addExamQuestion(examId: number, payload: AddExamQuestionPayload) {
  const response = await api.post<ApiResponse<ExamQuestion>>(`/exams/${examId}/questions`, payload);

  return response.data;
}

export async function addExamQuestionsBulk(examId: number, payload: BulkExamQuestionPayload) {
  const response = await api.post<
    ApiResponse<{
      created: ExamQuestion[];
      errors: Record<string, string[]>;
    }>
  >(`/exams/${examId}/questions/bulk`, payload);

  return response.data;
}

export async function listExamQuestions(
  examId: number,
  page = 1,
  perPage = 100,
  filters: {
    type?: string;
    status?: string;
  } = {},
) {
  const response = await api.get<PaginatedResponse<any>>(`/exams/${examId}/questions`, {
    params: {
      page,
      per_page: perPage,
      ...filters,
    },
  });

  return response.data;
}

export async function removeExamQuestion(examId: number, examQuestionId: number) {
  const response = await api.delete<ApiResponse<null>>(`/exams/${examId}/questions/${examQuestionId}`);

  return response.data;
}

export async function submitExamForApproval(examId: number) {
  const response = await api.post<ApiResponse<Exam>>(`/exams/${examId}/submit-approval`);

  return response.data;
}

export async function revertExamToDraft(examId: number) {
  const response = await api.post<ApiResponse<Exam>>(`/exams/${examId}/revert-to-draft`);

  return response.data;
}

export async function approveExam(examId: number) {
  const response = await api.post<ApiResponse<Exam>>(`/exams/${examId}/approve`);

  return response.data;
}

export async function rejectExam(examId: number, reason: string) {
  const response = await api.post<ApiResponse<Exam>>(`/exams/${examId}/reject`, {
    reason,
  });

  return response.data;
}

export async function scheduleExam(examId: number, payload: ScheduleExamPayload) {
  const response = await api.post<ApiResponse<Exam>>(`/exams/${examId}/schedule`, payload);

  return response.data;
}

export async function updateExamSchedule(examId: number, payload: UpdateSchedulePayload) {
  const response = await api.patch<ApiResponse<Exam>>(`/exams/${examId}/schedule`, payload);

  return response.data;
}

export async function extendExamTime(examId: number, durationMinutes: number) {
  const response = await api.post<ApiResponse<Exam>>(`/exams/${examId}/extend-time`, {
    duration_minutes: durationMinutes,
  });

  return response.data;
}

export async function publishExam(examId: number) {
  const response = await api.post<ApiResponse<Exam>>(`/exams/${examId}/publish`);

  return response.data;
}

export async function endExam(examId: number) {
  const response = await api.post<ApiResponse<Exam>>(`/exams/${examId}/end`);

  return response.data;
}

export async function cancelExam(examId: number) {
  const response = await api.post<ApiResponse<Exam>>(`/exams/${examId}/cancel`);

  return response.data;
}

export async function archiveExam(examId: number) {
  const response = await api.delete<ApiResponse<Exam>>(`/exams/${examId}`);

  return response.data;
}
