import api from '@/api/axios';

import type { Question, QuestionFilters, QuestionListResponse, QuestionPayload } from '../types/question';

export async function listQuestions(page = 1, perPage = 12, archived = false, filters: QuestionFilters = {}): Promise<QuestionListResponse> {
  const endpoint = archived ? '/questions/archived' : '/questions';

  const res = await api.get(endpoint, {
    params: {
      page,
      per_page: perPage,
      ...filters,
    },
  });

  return {
    data: res.data.data,
    pagination: res.data.pagination,
  };
}

export async function getQuestion(id: number): Promise<Question> {
  const res = await api.get(`/questions/${id}`);

  return res.data.data;
}

export async function createQuestion(courseId: number, payload: QuestionPayload): Promise<Question> {
  const res = await api.post(`/courses/${courseId}/questions`, payload);

  return res.data.data;
}

export async function updateQuestion(id: number, payload: Partial<QuestionPayload>): Promise<Question> {
  const res = await api.patch(`/questions/${id}`, payload);

  return res.data.data;
}

export async function archiveQuestion(id: number): Promise<void> {
  await api.delete(`/questions/${id}`);
}

export async function restoreQuestion(id: number): Promise<Question> {
  const res = await api.post(`/questions/${id}/restore`);

  return res.data.data;
}
