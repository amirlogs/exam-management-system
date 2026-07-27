export interface CourseSeed {
  id: number
  name: string
  code: string
  department: string
}

export const SEEDED_COURSES: CourseSeed[] = [
  { id: 1, name: 'Calculus I', code: 'MATH101', department: 'Mathematics' },
  { id: 2, name: 'Data Structures', code: 'CS201', department: 'Computer Science' },
]
