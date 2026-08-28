import api from '@/api/axios';
import type { Pagination, ResourceQuery } from '@/shared/composables/useCrudResource';
import type { College, CreateCollegeData, UpdateCollegeData } from '../types/college';

interface ListResponse<T> {
  data: T[];
  pagination: Pagination;
}

export async function getColleges(params: ResourceQuery = {}): Promise<ListResponse<College>> {
  const res = await api.get('/colleges', {
    params: {
      page: params.page ?? 1,
      per_page: params.per_page ?? 10,
      search: params.search || undefined,
    },
  });

  return res.data;
}

export async function getArchivedColleges(params: ResourceQuery = {}): Promise<ListResponse<College>> {
  const res = await api.get('/colleges/archived', {
    params: {
      page: params.page ?? 1,
      per_page: params.per_page ?? 10,
      search: params.search || undefined,
    },
  });

  return res.data;
}

export async function createCollege(data: CreateCollegeData) {
  const res = await api.post('/colleges', data);
  return res.data.data as College;
}

export async function updateCollege(id: number, data: UpdateCollegeData) {
  const res = await api.patch(`/colleges/${id}`, data);
  return res.data.data as College;
}

export async function deleteCollege(id: number) {
  await api.delete(`/colleges/${id}`);
}

export async function restoreCollege(id: number) {
  const res = await api.post(`/colleges/${id}/restore`);
  return res.data.data as College;
}
