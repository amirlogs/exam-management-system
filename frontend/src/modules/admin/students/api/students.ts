import api from '@/api/axios';
import type { Pagination } from '@/shared/composables/useCrudResource';
import type { Student } from '../types/student';

export interface StudentFilters {
  search?: string;
  section_id?: number | null;
  program_id?: number | null;
  status?: string | null;
}

export async function getStudents(
  page = 1,
  perPage = 12,
  filters: StudentFilters = {},
) {
  const res = await api.get('/students', {
    params: {
      page,
      per_page: perPage,
      ...filters,
    },
  });

  return res.data as {
    data: Student[];
    pagination: Pagination;
  };
}
