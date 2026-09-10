import api from '@/api/axios';
import type { AdminResult, ResultFilters } from '../types/result';
import type { ApiResponse, PaginatedResponse } from '@/modules/instructor/exams/types/exam';

export async function getResults(page = 1, perPage = 15, filters: ResultFilters = {}) {
  const response = await api.get<PaginatedResponse<AdminResult>>('/results', {
    params: {
      page,
      per_page: perPage,
      ...filters,
    },
  });
  return response.data;
}

export async function getResultDetail(id: number) {
  const response = await api.get<ApiResponse<AdminResult>>(`/results/${id}`);
  return response.data;
}

export async function publishResults(courseOfferingId: number) {
  const response = await api.post<ApiResponse<null>>('/results/publish', {
    course_offering_id: courseOfferingId,
  });
  return response.data;
}
