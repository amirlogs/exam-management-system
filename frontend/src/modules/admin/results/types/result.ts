export interface ResultStudent {
  id: number;
  student_number: string;
  user?: {
    id: number;
    full_name: string;
    first_name: string;
    last_name: string;
    email: string;
  } | null;
  section?: {
    id: number;
    name: string;
  } | null;
}

export interface ResultCourseOffering {
  id: number;
  course?: {
    id: number;
    code: string;
    title: string;
  } | null;
  semester?: {
    id: number;
    name: string;
  } | null;
}

export interface ResultPublisher {
  id: number;
  full_name: string;
  email: string;
}

export interface AdminResult {
  id: number;
  student_id: number;
  course_offering_id: number;
  total_score: number;
  letter_grade: string;
  status: 'draft' | 'published' | 'archived';
  published_by?: number | null;
  published_at?: string | null;
  student?: ResultStudent;
  course_offering?: ResultCourseOffering;
  publisher?: ResultPublisher | null;
  created_at?: string;
  updated_at?: string;
}

export interface ResultFilters {
  course_offering_id?: number;
  semester_id?: number;
  letter_grade?: string;
  status?: string;
  search?: string;
}
