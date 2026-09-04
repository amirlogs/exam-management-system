export interface Student {
  id: number;
  student_number: string;
  program_id: number;
  curriculum_id: number;
  entry_year: number;
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

  section: {
    id: number;
    name: string;
    year_level: number;
    program_id: number;
    semester_id: number;
    created_at: string;
    updated_at: string;
  } | null;

  created_at: string;
  updated_at: string;
}
