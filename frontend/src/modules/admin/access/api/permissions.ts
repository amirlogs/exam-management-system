import api from '@/api/axios';
import type { Permission } from '../types/permission';
export async function getPermissions(): Promise<Permission[]> {
  const res = await api.get('/permissions');
  return res.data.data;
}
