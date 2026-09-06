import api from '@/api/axios';
import type { Pagination } from '@/shared/composables/useCrudResource';
import type { CourseOffering, InstructorAssignment, InstructorAssignmentType, OfferingInstructorOption, OfferingStatus, OfferingSuggestion } from '../types/courseOffering';

interface ListResponse<T> {
  data: T[];
  pagination: Pagination;
}

export async function listOfferings(semesterId: number, page = 1, perPage = 10, status?: OfferingStatus, search?: string): Promise<ListResponse<CourseOffering>> {
  const res = await api.get('/course-offerings', {
    params: {
      semester_id: semesterId,
      page,
      per_page: perPage,
      status,
      search,
    },
  });

  return res.data;
}

export async function getArchivedOfferings(semesterId: number | null, page = 1, perPage = 10, status?: OfferingStatus, search?: string): Promise<ListResponse<CourseOffering>> {
  const res = await api.get('/course-offerings/archived', {
    params: {
      semester_id: semesterId ?? undefined,
      page,
      per_page: perPage,
      status,
      search,
    },
  });

  return res.data;
}

export async function getOffering(offeringId: number): Promise<CourseOffering> {
  const res = await api.get(`/course-offerings/${offeringId}`);

  return res.data.data;
}

export async function generateSuggestions(semesterId: number): Promise<OfferingSuggestion[]> {
  const res = await api.post('/course-offerings/suggestions/generate', {
    semester_id: semesterId,
  });

  return res.data.data;
}

export async function createOffering(courseId: number, semesterId: number): Promise<CourseOffering> {
  const res = await api.post('/course-offerings', {
    course_id: courseId,
    semester_id: semesterId,
  });

  return res.data.data;
}

export async function listInstructors(page = 1, perPage = 100): Promise<ListResponse<OfferingInstructorOption>> {
  const res = await api.get('/instructors', {
    params: {
      page,
      per_page: perPage,
    },
  });

  return res.data;
}

export async function assignInstructor(offeringId: number, instructorId: number, sectionId: number, type: InstructorAssignmentType): Promise<InstructorAssignment> {
  const res = await api.post(`/course-offerings/${offeringId}/instructors`, {
    instructor_id: instructorId,
    section_id: sectionId,
    type,
  });

  return res.data.data;
}

export async function updateInstructorAssignment(
  offeringId: number,
  assignmentId: number,
  payload: {
    instructor_id?: number;
    section_id?: number;
    type?: InstructorAssignmentType;
  },
): Promise<InstructorAssignment> {
  const res = await api.patch(`/course-offerings/${offeringId}/instructor-assignments/${assignmentId}`, payload);

  return res.data.data;
}

export async function removeInstructorAssignment(offeringId: number, assignmentId: number): Promise<void> {
  await api.delete(`/course-offerings/${offeringId}/instructor-assignments/${assignmentId}`);
}

export async function approveOffering(id: number): Promise<CourseOffering> {
  const res = await api.post(`/course-offerings/${id}/approve`);

  return res.data.data;
}

export async function rejectOffering(id: number, reason: string): Promise<CourseOffering> {
  const res = await api.post(`/course-offerings/${id}/reject`, {
    reason,
  });

  return res.data.data;
}

export async function cancelOffering(id: number): Promise<CourseOffering> {
  const res = await api.post(`/course-offerings/${id}/cancel`);

  return res.data.data;
}

export async function reopenOffering(id: number): Promise<CourseOffering> {
  const res = await api.post(`/course-offerings/${id}/reopen`);

  return res.data.data;
}

export async function archiveOffering(id: number): Promise<void> {
  await api.delete(`/course-offerings/${id}`);
}

export async function restoreOffering(id: number): Promise<CourseOffering> {
  const res = await api.post(`/course-offerings/${id}/restore`);

  return res.data.data;
}
