import api from '@/api/axios';
import type { ApiResponse, Exam, PaginatedResponse } from '../../exams/types/exam';
import type { ExamAttemptSubmission, GradeAnswerPayload } from '../types/grading';

export async function listSubmissions(
  examId: number,
  page = 1,
  perPage = 15,
  filters: {
    status?: string;
    search?: string;
  } = {},
) {
  const response = await api.get<PaginatedResponse<ExamAttemptSubmission>>(`/exams/${examId}/submissions`, {
    params: {
      page,
      per_page: perPage,
      ...filters,
    },
  });

  return response.data;
}

export async function getSubmissionDetail(examId: number, attemptId: number) {
  const response = await api.get<ApiResponse<ExamAttemptSubmission>>(`/exams/${examId}/submissions/${attemptId}`);
  return response.data;
}

export async function autoGradeExam(examId: number) {
  const response = await api.post<ApiResponse<null>>(`/exams/${examId}/auto-grade`);
  return response.data;
}

export async function gradeAnswer(answerId: number, payload: GradeAnswerPayload) {
  const response = await api.patch<ApiResponse<any>>(`/answers/${answerId}/grade`, payload);
  return response.data;
}

export async function submitVerification(courseOfferingId: number, examId?: number) {
  const response = await api.post<ApiResponse<null>>(
    `/course-offerings/${courseOfferingId}/grades/submit-verification`,
    examId ? { exam_id: examId } : {},
  );
  return response.data;
}
