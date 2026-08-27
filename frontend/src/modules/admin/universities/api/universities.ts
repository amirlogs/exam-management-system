import api from '@/api/axios';
import type { Pagination } from '@/shared/composables/useCrudResource';
import type { CreateUniversityData, University, UpdateUniversityData } from '../types/university';

interface ListResponse<T> {
  data: T[];
  pagination: Pagination;
}

export async function getUniversities(page = 1, perPage = 10): Promise<ListResponse<University>> {
  const res = await api.get('/universities', { params: { page, per_page: perPage } });
  return res.data;
}
export async function getArchivedUniversities(page = 1, perPage = 10): Promise<ListResponse<University>> {
  const res = await api.get('/universities/archived', { params: { page, per_page: perPage } });
  return res.data;
}
export async function createUniversity(data: CreateUniversityData) {
  const res = await api.post('/universities', data);
  return res.data.data as University;
}
export async function updateUniversity(id: number, data: UpdateUniversityData) {
  const res = await api.patch(`/universities/${id}`, data);
  return res.data.data as University;
}
export async function deleteUniversity(id: number) {
  await api.delete(`/universities/${id}`);
}
export async function restoreUniversity(id: number) {
  const res = await api.post(`/universities/${id}/restore`);
  return res.data.data as University;
}
