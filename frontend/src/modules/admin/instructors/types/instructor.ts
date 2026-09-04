export interface Instructor {
  id: number;

  employee_number: string;

  academic_rank: string | null;

  status: string;

  user: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    is_first_login: boolean;
    is_active: boolean;
    default_workspace: string | null;
    created_at: string;
    updated_at: string;
    role_name: string[];
  };

  department: {
    id: number;
    name: string;
  } | null;

  created_at: string;
  updated_at: string;
}
