export interface Program {
  id: number;
  code: string;
  name: string;
  duration_years: number;
  department?: { id: number; name: string; college: string; college_id: number };
  created_at: string;
  updated_at: string;
}
export interface CreateProgramData {
  department_id: number;
  code: string;
  name: string;
  duration_years: number;
}
export interface UpdateProgramData {
  department_id?: number;
  code?: string;
  name?: string;
  duration_years?: number;
}
