import apiClient from '@/api/axios'
import type { CourseOffering } from '../types/courseOffering'

function unwrap<T>(res: { data: any }): T {
  if (res.data?.success === false) throw new Error(res.data?.message || 'Request failed')
  return res.data?.data ?? res.data
}
function unwrapPaginated<T>(res: { data: any }) {
  if (res.data?.success === false) throw new Error(res.data?.message || 'Request failed')
  return { data: res.data.data as T[], pagination: res.data.pagination }
}

export function getCourseOfferings(page = 1, filters: Record<string, any> = {}) {
  return apiClient.get('/course-offerings', { params: { page, ...filters } }).then(unwrapPaginated<CourseOffering>)
}
export function getCourseOffering(id: number) {
  return apiClient.get(`/course-offerings/${id}`).then(unwrap<CourseOffering>)
}
