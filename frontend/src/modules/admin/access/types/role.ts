import type { Permission } from './permission';
export interface Role {
  id: number;
  name: string;
  description: string;
  created_at: string;
  updated_at: string;
  permissions: Permission[];
}
