import api from '@/api/axios';
import type { Pagination } from '@/shared/composables/useCrudResource';
import type { CreateDepartmentData, Department, UpdateDepartmentData } from '../types/department';

interface ListResponse<T> {
  data: T[];
  pagination: Pagination;
}

export async function getDepartments(page = 1, perPage = 10, search = '', filters?: { college_id?: number | null; type?: string }): Promise<ListResponse<Department>> {
  const res = await api.get('/departments', {
    params: { page, per_page: perPage, search, ...filters },
  });
  return res.data;
}

export async function getArchivedDepartments(page = 1, perPage = 10, search = '', filters?: { college_id?: number | null; type?: string }): Promise<ListResponse<Department>> {
  const res = await api.get('/departments/archived', {
    params: { page, per_page: perPage, search, ...filters },
  });
  return res.data;
}
export async function createDepartment(data: CreateDepartmentData) {
  const res = await api.post('/departments', data);
  return res.data.data as Department;
}
export async function updateDepartment(id: number, data: UpdateDepartmentData) {
  const res = await api.patch(`/departments/${id}`, data);
  return res.data.data as Department;
}
export async function deleteDepartment(id: number) {
  await api.delete(`/departments/${id}`);
}
export async function restoreDepartment(id: number) {
  const res = await api.post(`/departments/${id}/restore`);
  return res.data.data as Department;
}
