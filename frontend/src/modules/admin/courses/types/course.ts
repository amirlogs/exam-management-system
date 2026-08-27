export interface Course {
  id: number;
  code: string;
  name: string;
  credit_hours: number;
  department?: { id: number; college_id: number; name: string; type: string };
  created_at: string;
  updated_at: string;
}
export interface CreateCourseData {
  department_id: number;
  code: string;
  name: string;
  credit_hours: number;
}
export interface UpdateCourseData {
  department_id?: number;
  code?: string;
  name?: string;
  credit_hours?: number;
}
