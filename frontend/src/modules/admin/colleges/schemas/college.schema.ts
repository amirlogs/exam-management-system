import { z } from 'zod'

export const collegeSchema = z.object({
  name: z.string().min(3, 'Name must be at least 3 characters').max(255),
})
