import api from '@/api/axios';
import type { Pagination } from '@/shared/composables/useCrudResource';
import type { Teaching, TeachingDetail } from '../types/teaching';

interface ListResponse<T> {
  data: T[];
  pagination: Pagination;
}

export async function getTeaching(page = 1, perPage = 15, params: Record<string, any> = {}): Promise<ListResponse<Teaching>> {
  const res = await api.get('/me/teaching', {
    params: {
      page,
      per_page: perPage,
      ...params,
    },
  });

  return {
    ...res.data,
    data: res.data.data,
  };
}

export async function getTeachingDetail(courseOfferingId: number): Promise<TeachingDetail> {
  const res = await api.get(`/me/teaching/${courseOfferingId}`);

  return res.data.data;
}
