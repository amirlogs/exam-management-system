import api from '@/api/axios'
import type {
  CreateUniversityData,
  PaginatedResponse,
  University,
  UpdateUniversityData,
} from '../types/university'
interface ApiResponse<T> {
  success: boolean
  message: string
  data: T
  errors: unknown
}

export async function getUniversities(
  page = 1,
  perPage = 12,
): Promise<PaginatedResponse<University>> {
  const response = await api.get('/universities', {
    params: {
      page,
      per_page: perPage,
    },
  })

  return response.data.data
}

export async function getUniversity(id: number) {
  const response = await api.get<ApiResponse<University>>(`/universities/${id}`)

  return response.data.data
}

export async function createUniversity(data: CreateUniversityData) {
  const response = await api.post<ApiResponse<University>>('/universities', data)

  return response.data.data
}

export async function updateUniversity(id: number, data: UpdateUniversityData) {
  const response = await api.patch<ApiResponse<University>>(`/universities/${id}`, data)

  return response.data.data
}

export async function deleteUniversity(id: number) {
  const response = await api.delete<ApiResponse<string>>(`/universities/${id}`)

  return response.data
}

export async function restoreUniversity(id: number) {
  const response = await api.post<ApiResponse<University>>(`/universities/${id}/restore`)

  return response.data.data
}

export async function getArchivedUniversities(
  page = 1,
  perPage = 12,
): Promise<PaginatedResponse<University>> {
  const response = await api.get('/universities/archived', {
    params: {
      page,
      per_page: perPage,
    },
  })

  return response.data.data
}
