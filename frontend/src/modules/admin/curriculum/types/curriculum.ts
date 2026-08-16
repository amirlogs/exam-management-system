export interface CurriculumCourseCourse {
  id: number
  code: string
  name: string
  credit_hours: number
}
export interface CurriculumCourse {
  id: number
  curriculum_id: number
  course_id: number
  year_level: number
  semester_number: number
  created_at: string
  course: CurriculumCourseCourse
}
export interface Curriculum {
  id: number
  program_id: number
  version: string
  is_active?: boolean // ASSUMPTION — see backend gap #2 above
  created_at: string
  updated_at: string
  courses: CurriculumCourse[]
}
