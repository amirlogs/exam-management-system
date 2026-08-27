export type OfferingStatus = 'draft' | 'approved' | 'rejected' | 'cancelled';

export type InstructorAssignmentType = 'lead_instructor' | 'instructor';

export interface OfferingCourse {
  id: number;
  code: string;
  name: string;
  credit_hours: number;
}

export interface OfferingSemester {
  id: number;
  name: string;
  academic_year: string;
  status: string;
  start_date?: string;
  end_date?: string;
}

export interface OfferingProgram {
  id: number;
  name: string;
  code?: string;
  duration_years?: number;
}

export interface OfferingSection {
  id: number;
  name: string;
  year_level: number;
  program_id: number;
  semester_id: number;
  program?: OfferingProgram;
}

export interface InstructorUser {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  is_active: boolean;
}

export interface OfferingInstructor {
  id: number;
  employee_number: string;
  academic_rank?: string | null;
  status: string;
  user?: InstructorUser;
}

export interface InstructorAssignment {
  id: number;
  course_offering_id: number;
  section_id: number;
  instructor_id: number;
  type: InstructorAssignmentType;
  assigned_at: string | null;
  instructor?: OfferingInstructor;
  section?: OfferingSection;
}

export interface CourseOffering {
  id: number;
  course_id: number;
  semester_id: number;
  created_by: string | null;
  status: OfferingStatus;
  rejection_reason: string | null;
  created_at: string;
  updated_at: string;

  course?: OfferingCourse;
  semester?: OfferingSemester;
  sections?: OfferingSection[];
  instructor_assignments?: InstructorAssignment[];
}

export interface OfferingSuggestion {
  course_id: number;
  course_code: string;
  course_name: string;
  program_id: number;
  program_name: string;
  year_level: number;
}

export interface OfferingInstructorOption {
  id: number;
  employee_number: string;
  academic_rank?: string | null;
  status: string;
  user?: InstructorUser;
}
