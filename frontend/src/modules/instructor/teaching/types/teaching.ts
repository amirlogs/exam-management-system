export interface TeachingCourse {
  id: number;
  code: string;
  name: string;
  credit_hours: number;
}

export interface TeachingSemester {
  id: number;
  name: string;
  academic_year: string;
  status: string;
}

export interface TeachingProgram {
  id: number;
  name: string;
  code: string;
}

export interface TeachingSection {
  id: number;
  name: string;
  year_level: number;
  program?: TeachingProgram | null;
}

export interface TeachingAssignment {
  id: number;
  type: string;
  assigned_at: string | null;
  section: TeachingSection | null;
}

export interface TeachingStudentUser {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
}

export interface TeachingStudentSection {
  id: number;
  name: string;
  year_level: number;
}

export interface TeachingStudent {
  id: number;
  student_number: string;
  status: string;
  user: TeachingStudentUser | null;
  section: TeachingStudentSection | null;
}

export interface TeachingExam {
  id: number;
  title: string;
  type: string;
  duration_minutes: number;
  status: string;
  total_marks: number;
  total_questions: number;
  scheduled_start: string | null;
  scheduled_end: string | null;
}

export interface Teaching {
  id: number;
  course: TeachingCourse;
  semester: TeachingSemester;
  status: string;
  assignments: TeachingAssignment[];
}

export interface TeachingDetail extends Teaching {
  students: TeachingStudent[];
  exams: TeachingExam[];
}
