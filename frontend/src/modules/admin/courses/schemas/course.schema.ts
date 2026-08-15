import { z } from 'zod'

export const courseSchema = z.object({
  department_id: z.coerce.number().int().positive({ message: 'Please select a department' }),
  code: z.string().min(3, 'Code must be at least 3 characters').max(255),
  name: z.string().min(3, 'Name must be at least 3 characters').max(255),
  credit_hours: z.coerce.number().int().min(1, 'Credit hours must be at least 1').max(100, 'Credit hours cannot exceed 100'),
})
