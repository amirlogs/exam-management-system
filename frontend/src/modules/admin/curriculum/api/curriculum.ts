import api from '@/api/axios'
import type { Curriculum, CurriculumCourse } from '../types/curriculum'

interface CurriculumListResponse {
  data: Curriculum[]
  pagination: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
  }
}

export async function getCurriculums(programId: number): Promise<CurriculumListResponse> {
  const res = await api.get('/curriculums', {
    params: {
      program_id: programId,
      per_page: 100,
    },
  })

  return res.data
}

export async function createCurriculum(data: { program_id: number; version: string }) {
  const res = await api.post('/curriculums', data)

  return res.data.data as Curriculum
}

export async function activateCurriculum(id: number) {
  const res = await api.post(`/curriculums/${id}/activate`)

  return res.data.data as Curriculum
}

export async function deactivateCurriculum(id: number) {
  const res = await api.post(`/curriculums/${id}/deactivate`)

  return res.data.data as Curriculum
}

export async function addCourseToCurriculum(
  curriculumId: number,
  data: {
    course_id: number
    year_level: number
    semester_number: number
  },
) {
  const res = await api.post(`/curriculums/${curriculumId}/courses`, data)

  return res.data.data as CurriculumCourse
}

export async function removeCurriculumCourse(id: number) {
  await api.delete(`/curriculums/courses/${id}`)
}
export async function updateCurriculum(
    id: number,
    data: { version: string },
) {
    const res = await api.patch(
        `/curriculums/${id}`,
        data,
    )

    return res.data.data as Curriculum
}