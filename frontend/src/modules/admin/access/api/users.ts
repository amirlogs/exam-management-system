import api from '@/api/axios';
import type { Pagination } from '@/shared/composables/useCrudResource';
import type { User } from '../types/user';

interface ListResponse<T> {
  data: T[];
  pagination: Pagination;
}

export async function getUsers(page = 1, perPage = 12, search = ''): Promise<ListResponse<User>> {
  const res = await api.get('/users', { params: { page, per_page: perPage, search } });

  return res.data;
}
export async function createUser(data: { first_name: string; last_name: string; email: string; password: string }) {
  const res = await api.post('/users', data);
  return res.data.data as User;
}
export async function updateUser(id: number, data: Partial<{ first_name: string; last_name: string }>) {
  const res = await api.patch(`/users/${id}`, data);
  return res.data.data as User;
}
export async function assignRole(userId: number, payload: { role_id: number; university_id?: number; college_id?: number; department_id?: number }) {
  const res = await api.post(`/users/${userId}/roles`, payload);
  return res.data.data as User;
}
export async function removeRole(userId: number, roleId: number) {
  const res = await api.delete(`/users/${userId}/roles`, { data: { role_id: roleId } });
  return res.data.data as User;
}
export async function disableUser(id: number) {
  const res = await api.post(`/users/${id}/disable`);
  return res.data.data as User;
}
export async function activateUser(id: number) {
  const res = await api.post(`/users/${id}/activate`);
  return res.data.data as User;
}
