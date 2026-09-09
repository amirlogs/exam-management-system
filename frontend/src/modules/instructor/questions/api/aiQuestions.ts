import api from '@/api/axios';
import type { GenerateQuestionsPayload, GeneratedQuestionsHistory } from '../types/aiQuestion';
import type { ApiResponse, PaginatedResponse } from '@/modules/instructor/exams/types/exam';

export async function generateQuestions(payload: GenerateQuestionsPayload) {
  const response = await api.post<ApiResponse<GeneratedQuestionsHistory>>('/ai/questions/generate', payload);
  return response.data;
}

export async function getGenerationHistory(
  page = 1,
  perPage = 15,
  filters: { type?: string; status?: string } = {}
) {
  const response = await api.get<PaginatedResponse<GeneratedQuestionsHistory>>('/ai/questions/generate', {
    params: {
      page,
      per_page: perPage,
      ...filters,
    },
  });
  return response.data;
}

export async function getGenerationSession(id: number) {
  const response = await api.get<ApiResponse<GeneratedQuestionsHistory>>(`/ai/questions/${id}`);
  return response.data;
}

export async function confirmGeneratedQuestions(id: number) {
  const response = await api.post<ApiResponse<GeneratedQuestionsHistory>>(`/ai/questions/${id}/confirm`);
  return response.data;
}

export async function cancelGeneratedQuestions(id: number) {
  const response = await api.post<ApiResponse<null>>(`/ai/questions/${id}/cancel`);
  return response.data;
}

export async function deleteGeneratedQuestion(historyId: number, index: number) {
  const response = await api.delete<ApiResponse<GeneratedQuestionsHistory>>(
    `/ai/questions/${historyId}/questions/${index}`
  );
  return response.data;
}
