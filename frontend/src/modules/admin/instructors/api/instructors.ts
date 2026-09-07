import api from '@/api/axios';
import type { Pagination } from '@/shared/composables/useCrudResource';
import type { Instructor } from '../types/instructor';

export interface InstructorFilters {
  search?: string;
  department_id?: number | null;
  status?: string | null;
}

export async function getInstructors(page = 1, perPage = 12, filters: InstructorFilters = {}) {
  const res = await api.get('/instructors', {
    params: {
      page,
      per_page: perPage,
      ...filters,
    },
  });

  return res.data as {
    data: Instructor[];
    pagination: Pagination;
  };
}
