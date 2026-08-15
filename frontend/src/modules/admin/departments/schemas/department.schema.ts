import { z } from 'zod'

export const departmentSchema = z.object({
  college_id: z.coerce.number().int().positive({ message: 'Please select a college' }),
  name: z.string().min(3, 'Name must be at least 3 characters').max(255),
  type: z.enum(['service_only', 'degree_granting'], { required_error: 'Please select a type' }),
})
