import api from '@/api/axios'
import type { Pagination } from '@/shared/composables/useCrudResource'
import type { Role } from '../types/role'

interface ListResponse<T> {
  data: T[]
  pagination: Pagination
}

export async function getRoles(page = 1, perPage = 12): Promise<ListResponse<Role>> {
  const res = await api.get('/roles', { params: { page, per_page: perPage } })
  return res.data
}
export async function getArchivedRoles(page = 1, perPage = 12): Promise<ListResponse<Role>> {
  const res = await api.get('/roles/archived', { params: { page, per_page: perPage } }) // flagged earlier: confirm this is GET not POST
  return res.data
}
export async function createRole(data: { name: string; description: string }) {
  const res = await api.post('/roles', data)
  return res.data.data as Role
}
export async function updateRole(id: number, data: Partial<{ name: string; description: string }>) {
  const res = await api.patch(`/roles/${id}`, data)
  return res.data.data as Role
}
export async function deleteRole(id: number) {
  await api.delete(`/roles/${id}`)
}
export async function restoreRole(id: number) {
  const res = await api.post(`/roles/${id}/restore`)
  return res.data.data as Role
}
export async function assignPermissions(roleId: number, permissionIds: number[]) {
  const res = await api.post(`/roles/${roleId}/permissions`, { permission_ids: permissionIds })
  return res.data.data as Role
}
export async function removePermissions(roleId: number, permissionIds: number[]) {
  const res = await api.post(`/roles/${roleId}/permissions/remove`, { permission_ids: permissionIds })
  return res.data.data as Role
}
