import { z } from 'zod';

export const sectionSchema = z.object({
  semester_id: z.coerce.number().int().positive({ message: 'Please select a semester' }),
  program_id: z.coerce.number().int().positive({ message: 'Please select a program' }),
  year_level: z.coerce.number().int().min(1).max(10),
  name: z.coerce.number().int().min(1).max(100),
});
