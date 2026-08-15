export interface University {
  id: number
  name: string
  code: string
  address: string
  created_at: string
  updated_at: string
}

export interface CreateUniversityData {
  name: string
  code: string
  address: string
}
export interface UpdateUniversityData {
  name?: string
  code?: string
  address?: string
}

export interface CreateUniversityData {
  name: string
  code: string
  address: string
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export interface PaginatedResponse<T> {
  current_page: number
  data: T[]
  first_page_url: string
  from: number | null
  last_page: number
  last_page_url: string
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number | null
  total: number
}

export interface UniversityPagination {
  current_page: number
  data: University[]
  first_page_url: string
  from: number | null
  last_page: number
  last_page_url: string
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number | null
  total: number
}

