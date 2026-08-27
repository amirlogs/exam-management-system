import api from '@/api/axios';
import type { Pagination } from '@/shared/composables/useCrudResource';
import type { SaveSemesterData, Semester } from '../types/semester';

interface ListResponse<T> {
  data: T[];
  pagination: Pagination;
}

export async function getSemesters(page = 1, perPage = 12): Promise<ListResponse<Semester>> {
  const res = await api.get('/semesters', { params: { page, per_page: perPage } });
  return res.data;
}
export async function getArchivedSemesters(page = 1, perPage = 12): Promise<ListResponse<Semester>> {
  const res = await api.get('/semesters/archived', { params: { page, per_page: perPage } });
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
