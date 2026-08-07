export type SemesterStatus = 'upcoming' | 'active' | 'completed'
export interface Semester {
  id: number
  name: number | string
  academic_year: string
  start_date: string
  end_date: string
  status: SemesterStatus
  created_at: string
  updated_at: string
}
export interface SaveSemesterData {
  academic_year: number
  name: number
  start_date: string
  end_date: string
}
