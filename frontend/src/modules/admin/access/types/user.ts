export interface UserRoleAssignment {
  role_id: number;
  role_name: string;
  university_id?: number | null;
  college_id?: number | null;
  department_id?: number | null;
}
export interface User {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  is_first_login: boolean;
  is_active?: boolean; // ASSUMPTION — see disable/activate note
  role_name: string[];
  created_at: string;
  updated_at: string;
}
