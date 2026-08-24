import api from '@/api/axios'
import type { Pagination } from '@/shared/composables/useCrudResource'
import type { Course, CreateCourseData, UpdateCourseData } from '../types/course'

interface ListResponse<T> {
  data: T[]
  pagination: Pagination
}

interface CourseFilters {
  department_id?: number | null
  credit_hours?: number | null
}

export async function getCourses(page = 1, perPage = 10, search = '', filters: CourseFilters = {}): Promise<ListResponse<Course>> {
  const res = await api.get('/courses', {
    params: {
      page,
      per_page: perPage,
      search: search || undefined,
      department_id: filters.department_id ?? undefined,
      credit_hours: filters.credit_hours ?? undefined,
    },
  })

  return res.data
}

export async function getArchivedCourses(page = 1, perPage = 10, search = '', filters: CourseFilters = {}): Promise<ListResponse<Course>> {
  const res = await api.get('/courses/archived', {
    params: {
      page,
      per_page: perPage,
      search: search || undefined,
      department_id: filters.department_id ?? undefined,
      credit_hours: filters.credit_hours ?? undefined,
    },
  })

  return res.data
}

export async function createCourse(data: CreateCourseData) {
  const res = await api.post('/courses', data)

  return res.data.data as Course
}

export async function updateCourse(id: number, data: UpdateCourseData) {
  const res = await api.patch(`/courses/${id}`, data)

  return res.data.data as Course
}

export async function deleteCourse(id: number) {
  await api.delete(`/courses/${id}`)
}

export async function restoreCourse(id: number) {
  const res = await api.post(`/courses/${id}/restore`)

  return res.data.data as Course
}
