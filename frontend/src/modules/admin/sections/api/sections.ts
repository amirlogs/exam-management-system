import api from '@/api/axios';
import type { Pagination } from '@/shared/composables/useCrudResource';
import type { SaveSectionData, Section } from '../types/section';

interface ListResponse<T> {
  data: T[];
  pagination: Pagination;
}

export interface SectionFilters {
  semester_id?: number | null;
  program_id?: number | null;
  year_level?: number | null;
}

export async function getSections(page = 1, perPage = 12, search = '', filters: SectionFilters = {}): Promise<ListResponse<Section>> {
  const res = await api.get('/sections', {
    params: {
      page,
      per_page: perPage,
      search: search || undefined,
      semester_id: filters.semester_id || undefined,
      program_id: filters.program_id || undefined,
      year_level: filters.year_level || undefined,
    },
  });

  return res.data;
}

export async function getArchivedSections(page = 1, perPage = 12, search = '', filters: SectionFilters = {}): Promise<ListResponse<Section>> {
  const res = await api.get('/sections/archived', {
    params: {
      page,
      per_page: perPage,
      search: search || undefined,
      semester_id: filters.semester_id || undefined,
      program_id: filters.program_id || undefined,
      year_level: filters.year_level || undefined,
    },
  });

  return res.data;
}

export async function createSection(data: SaveSectionData) {
  const res = await api.post('/sections', data);

  return res.data.data as Section;
}

export async function updateSection(id: number, data: Partial<SaveSectionData>) {
  const res = await api.patch(`/sections/${id}`, data);

  return res.data.data as Section;
}

export async function deleteSection(id: number) {
  await api.delete(`/sections/${id}`);
}

export async function restoreSection(id: number) {
  const res = await api.post(`/sections/${id}/restore`);

  return res.data.data as Section;
}
