import { z } from 'zod'

export const curriculumVersionSchema = z.object({
  program_id: z.coerce.number().int().positive({ message: 'Please select a program' }),
  version: z.string().min(1, 'Version is required').max(255),
})

export const curriculumCourseSchema = z.object({
  course_id: z.coerce.number().int().positive({ message: 'Please select a course' }),
  year_level: z.coerce.number().int().min(1).max(10),
  semester_number: z.coerce.number().int().min(1).max(2),
})
