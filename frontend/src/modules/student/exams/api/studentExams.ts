import api from '@/api/axios';
import type {
  StudentExam,
  StudentExamQuestion,
  SubmitAnswerPayload,
  ExamAttempt,
} from '../types/studentExam';

export interface Pagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface PaginatedResponse<T> {
  success: boolean;
  message: string;
  data: T[];
  pagination: Pagination;
  errors: unknown;
}

export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
  errors: unknown;
}

export async function getStudentExams(
  page = 1,
  perPage = 15,
  filters: {
    status?: string;
    type?: string;
    search?: string;
  } = {}
) {
  const response = await api.get<PaginatedResponse<StudentExam>>('/student/exams', {
    params: {
      page,
      per_page: perPage,
      ...filters,
    },
  });
  return response.data;
}

export async function getStudentExam(examId: number) {
  const response = await api.get<ApiResponse<StudentExam>>(`/student/exams/${examId}`);
  return response.data;
}

export async function startStudentExam(examId: number) {
  const response = await api.post<ApiResponse<ExamAttempt>>(`/student/exams/${examId}/start`);
  return response.data;
}

export async function getStudentExamQuestions(examId: number) {
  const response = await api.get<ApiResponse<StudentExamQuestion[]>>(`/student/exams/${examId}/questions`);
  return response.data;
}

export async function saveStudentAnswer(attemptId: number, payload: SubmitAnswerPayload) {
  const response = await api.post<ApiResponse<null>>(`/student/attempts/${attemptId}/answers`, payload);
  return response.data;
}

export async function submitStudentExam(attemptId: number) {
  const response = await api.post<ApiResponse<null>>(`/student/attempts/${attemptId}/submit`);
  return response.data;
}
