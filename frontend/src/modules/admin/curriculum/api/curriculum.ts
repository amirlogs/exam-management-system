import api from '@/api/axios'
import type { Curriculum, CurriculumCourse } from '../types/curriculum'

export async function getCurriculums(programId?: number): Promise<{ data: Curriculum[] }> {
  // ASSUMPTION: ?program_id filter — see backend gap #5
  const res = await api.get('/curriculums', { params: { per_page: 100, program_id: programId } })
  return res.data
}
export async function createCurriculum(data: { program_id: number; version: string }) {
  const res = await api.post('/curriculums', data)
  return res.data.data as Curriculum
  // NOTE: relies on backend gap #1 (CurriculumResource wrap) being fixed
}
export async function activateCurriculum(id: number) {
  const res = await api.post(`/curriculums/${id}/activate`)
  return res.data.data as Curriculum
}
export async function deactivateCurriculum(id: number) {
  const res = await api.post(`/curriculums/${id}/deactivate`)
  return res.data.data as Curriculum
}
export async function addCourseToCurriculum(curriculumId: number, data: { course_id: number; year_level: number; semester_number: number }) {
  const res = await api.post(`/curriculums/${curriculumId}/courses`, data)
  return res.data.data as CurriculumCourse
}
export async function removeCurriculumCourse(id: number) {
  await api.delete(`/curriculums/courses/${id}`)
}
