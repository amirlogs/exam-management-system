import api from '@/api/axios';
import type { Pagination } from '@/shared/composables/useCrudResource';
import type { SaveSemesterData, Semester } from '../types/semester';

interface ListResponse<T> {
  data: T[];
  pagination: Pagination;
}

export interface SemesterFilters {
  academic_year?: string | number;
  status?: string;
  search?: string;
}

export async function getSemesters(page = 1, perPage = 12, filters?: SemesterFilters): Promise<ListResponse<Semester>> {
  const params: Record<string, any> = { page, per_page: perPage };
  if (filters?.academic_year) params.academic_year = filters.academic_year;
  if (filters?.status) params.status = filters.status;
  if (filters?.search) params.search = filters.search;

  const res = await api.get('/semesters', { params });
  return res.data;
}

export async function getArchivedSemesters(page = 1, perPage = 12, filters?: SemesterFilters): Promise<ListResponse<Semester>> {
  const params: Record<string, any> = { page, per_page: perPage };
  if (filters?.academic_year) params.academic_year = filters.academic_year;
  if (filters?.status) params.status = filters.status;
  if (filters?.search) params.search = filters.search;

  const res = await api.get('/semesters/archived', { params });
  return res.data;
}
export async function createSemester(data: SaveSemesterData) {
  const res = await api.post('/semesters', data);
  return res.data.data as Semester;
}
export async function updateSemester(id: number, data: Partial<SaveSemesterData>) {
  const res = await api.patch(`/semesters/${id}`, data);
  return res.data.data as Semester;
}
export async function openSemester(id: number) {
  const res = await api.post(`/semesters/${id}/open`);
  return res.data.data as Semester;
}
export async function closeSemester(id: number) {
  const res = await api.post(`/semesters/${id}/close`);
  return res.data.data as Semester;
}
export async function archiveSemester(id: number) {
  await api.delete(`/semesters/${id}`);
}
export async function restoreSemester(id: number) {
  const res = await api.post(`/semesters/${id}/restore`);
  return res.data.data as Semester;
}
