import api from '@/api/axios'
import type { Pagination } from '@/shared/composables/useCrudResource'
import type { CourseOffering, OfferingSuggestion } from '../types/courseOffering'

interface ListResponse<T> {
  data: T[]
  pagination: Pagination
}

export async function listOfferings(semesterId: number, page = 1, perPage = 100): Promise<ListResponse<CourseOffering>> {
  const res = await api.get('/course-offerings', { params: { semester_id: semesterId, page, per_page: perPage } })
  return res.data
}
export async function generateSuggestions(semesterId: number): Promise<OfferingSuggestion[]> {
  const res = await api.post('/course-offerings/suggestions/generate', { semester_id: semesterId })
  return res.data.data
}
export async function createOffering(courseId: number, semesterId: number): Promise<CourseOffering> {
  const res = await api.post('/course-offerings', { course_id: courseId, semester_id: semesterId })
  return res.data.data
}
export async function attachSection(offeringId: number, sectionId: number): Promise<CourseOffering> {
  const res = await api.post(`/course-offerings/${offeringId}/sections`, { section_id: sectionId })
  return res.data.data
}
export async function detachSection(offeringId: number, sectionId: number): Promise<CourseOffering> {
  const res = await api.delete(`/course-offerings/${offeringId}/sections/${sectionId}`)
  return res.data.data
}
export async function attachInstructor(offeringId: number, instructorId: number, type: 'lead_instructor' | 'instructor'): Promise<CourseOffering> {
  const res = await api.post(`/course-offerings/${offeringId}/instructors`, { instructor_id: instructorId, type })
  return res.data.data
}
export async function removeInstructor(offeringId: number, instructorId: number): Promise<CourseOffering> {
  const res = await api.delete(`/course-offerings/${offeringId}/instructors/${instructorId}`)
  return res.data.data
}
export async function approveOffering(id: number): Promise<CourseOffering> {
  const res = await api.post(`/course-offerings/${id}/approve`)
  return res.data.data
}
export async function rejectOffering(id: number, reason: string): Promise<CourseOffering> {
  const res = await api.post(`/course-offerings/${id}/reject`, { reason })
  return res.data.data
}
export async function cancelOffering(id: number): Promise<CourseOffering> {
  const res = await api.post(`/course-offerings/${id}/cancel`)
  return res.data.data
}
export async function enrollOfferingSection(id: number): Promise<{ enrolled_count: number }> {
  const res = await api.post(`/course-offerings/${id}/enroll`)
  return res.data.data
}
