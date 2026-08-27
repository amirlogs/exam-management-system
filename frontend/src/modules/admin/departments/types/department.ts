export type DepartmentType = 'service_only' | 'degree_granting';
export interface Department {
  id: number;
  college_id: number;
  name: string;
  type: DepartmentType;
  college?: { id: number; name: string };
  created_at: string;
  updated_at: string;
}
export interface CreateDepartmentData {
  college_id: number;
  name: string;
  type: DepartmentType;
}
export interface UpdateDepartmentData {
  college_id?: number;
  name?: string;
  type?: DepartmentType;
}
