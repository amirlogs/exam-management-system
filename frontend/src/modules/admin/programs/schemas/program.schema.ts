import { z } from 'zod'

export const programSchema = z.object({
  department_id: z.coerce.number().int().positive({ message: 'Please select a department' }),
  code: z.string().min(2, 'Code must be at least 2 characters').max(255),
  name: z.string().min(3, 'Name must be at least 3 characters').max(255),
  duration_years: z.coerce.number().int().min(1, 'Duration must be at least 1 year').max(10, 'Duration must be at most 10 years'),
})
 