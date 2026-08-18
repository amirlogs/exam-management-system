export type OfferingStatus = 'draft' | 'approved' | 'rejected' | 'cancelled'

export interface OfferingCourse {
  id: number
  code: string
  name: string
  credit_hours: number
}
export interface OfferingSemester {
  id: number
  name: string
  academic_year: string
  status: string
}
export interface OfferingSection {
  id: number
  name: string
  year_level: number
  program_id: number
}
export interface OfferingInstructor {
  id: number
  first_name: string
  last_name: string
  email: string
  type?: 'lead_instructor' | 'instructor'
}

export interface CourseOffering {
  id: number
  course_id: number
  semester_id: number
  status: OfferingStatus
  rejection_reason: string | null
  created_at: string
  updated_at: string
  course?: OfferingCourse
  semester?: OfferingSemester
  sections?: OfferingSection[]
  instructors?: OfferingInstructor[]
}

export interface OfferingSuggestion {
  course_id: number
  course_code: string
  course_name: string
  program_id: number
  program_name: string
  year_level: number
}
